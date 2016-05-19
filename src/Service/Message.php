<?php
namespace Acme\Service;

use Acme\Model\Message as MessageModel;
use Acme\Model\Repository\Message as MessageRepository;
use Acme\Model\User as UserModel;
use Acme\Service\Halite as HaliteService;
use Acme\Service\User as UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;

/**
 * Class Message
 * @package Acme\Service
 */
class Message
{
    /**
     * Derivation constant for subject encryption
     */
    const TARGET_DERIVE_SUBJECT = 'subject';

    /**
     * Derivation constant for message encryption
     */
    const TARGET_DERIVE_MESSAGE = 'message';

    /**
     * Message model
     */
    const REPOSITORY_MESSAGE = 'Acme\Model\Message';

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var HaliteService
     */
    private $haliteService;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Message constructor.
     * @param EntityManager $entityManager
     * @param User $userService
     * @param Halite $halite
     */
    public function __construct(EntityManager $entityManager, UserService $userService, HaliteService $halite)
    {
        $this->setUserService($userService)
            ->setHaliteService($halite)
            ->setEm($entityManager);
    }

    /**
     * @param EntityManager $em
     * @return $this
     */
    public function setEm(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * @param Halite $haliteService
     * @return $this
     */
    public function setHaliteService(HaliteService $haliteService)
    {
        $this->haliteService = $haliteService;
        return $this;
    }

    /**
     * @param User $userService
     * @return $this
     */
    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
        return $this;
    }

    /**
     * @param int $userId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getList($userId)
    {
        if (($user = $this->userService->findById($userId)) == true) {
            $messageList = $this->getMessageRepository()->getList($user->getId());
            $salt = $user->getSalt();
            $arrResults = [];

            foreach ($messageList as $message) {
                $encryptionKeySubject = $this->deriveCipherKeyFromSalt($salt,
                    self::TARGET_DERIVE_SUBJECT . $message['id']);
                $plainSubject = $this->haliteService->decrypt($message['subject'], $encryptionKeySubject);
                $message['subject'] = $plainSubject;
                array_push($arrResults, $message);
            }
            return $arrResults;
        }

        throw new \InvalidArgumentException('User does not exists');
    }

    /**
     * @return MessageRepository
     */
    protected function getMessageRepository()
    {
        return $this->em->getRepository(self::REPOSITORY_MESSAGE);
    }

    /**
     * @param $salt
     * @param string $target
     * @return EncryptionKey
     */
    protected function deriveCipherKeyFromSalt($salt, $target = null)
    {
        return $this->haliteService->deriveKey(base64_decode($salt) . $target);
    }

    /**
     * @param $userId
     * @param $messageId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function get($userId, $messageId)
    {
        $repository = $this->getMessageRepository();
        /** @var MessageModel $message */
        if (($message = $repository->find($messageId)) == true) {
            $toUser = $message->getToUser();

            /*
             * Verify that the message belongs to the intended user
             */
            if ($toUser->getId() == $userId) {
                $toUserSalt = $toUser->getSalt();
                $fromUser = $message->getFromUser();

                $encryptionKeySubject = $this->deriveCipherKeyFromSalt($toUserSalt,
                    self::TARGET_DERIVE_SUBJECT . $message->getId());
                $encryptionKeyMessage = $this->deriveCipherKeyFromSalt($toUserSalt,
                    self::TARGET_DERIVE_MESSAGE . $message->getId());

                $authenticationKey = $this->deriveAuthenticationKeyFromSalt($fromUser->getSalt());

                $plainSubject = $this->haliteService->decrypt($message->getSubject(),
                    $encryptionKeySubject);

                $messageAuthenticated = $this->haliteService->verify($message->getMessage(), $authenticationKey,
                    $message->getMac());

                $plainMessage = '';

                if ($messageAuthenticated) {
                    $plainMessage = $this->haliteService->decrypt($message->getMessage(), $encryptionKeyMessage);
                }

                return [
                    'id' => $message->getId(),
                    'subject' => $plainSubject,
                    'message' => $plainMessage,
                    'name' => $fromUser->getName(),
                    'authenticated' => $messageAuthenticated,
                ];

            }
        }

        throw new \InvalidArgumentException('Message not found');
    }

    /**
     * @param $salt
     * @return AuthenticationKey
     */
    protected function deriveAuthenticationKeyFromSalt($salt)
    {
        return $this->haliteService->deriveAuthenticationKey(base64_decode($salt));
    }

    /**
     * @param $from
     * @param $to
     * @param $subject
     * @param $message
     * @return string
     * @throws \ParagonIE\Halite\Alerts\InvalidSalt
     */
    public function save($from, $to, $subject, $message)
    {
        /*
         * create a new model object to hold the message
         */
        $messageModel = new MessageModel;

        /** @var EntityRepository $userRepository */
        $userRepository = $this->em->getRepository('Acme\Model\User');

        /*
         * search for the sender and recipient users
         */
        /** @var UserModel $fromUserModel */
        if (($fromUserModel = $userRepository->find($from)) == true) {
            /** @var UserModel $toUserModel */
            if (($toUserModel = $userRepository->find($to)) == true) {

                /*
                 * create a placeholder for data, in order to generate a message id, used later to encrypt data.
                 */
                $messageModel->setFromUser($fromUserModel)
                    ->setToUser($toUserModel);

                $this->em->persist($messageModel);
                $this->em->flush();

                /*
                 * Retrieve the salts for both the sender and the recipient
                 */
                $toUserSalt = $toUserModel->getSalt();
                $fromUserSalt = $fromUserModel->getSalt();

                /*
                 * create encryption keys concatenating user's salt, a string representing the target field to be
                 * encrypted, the message unique id, and a system wide salt.
                 */
                $encryptionKeySubject = KeyFactory::deriveEncryptionKey(
                    base64_decode($toUserSalt) . 'subject' . $messageModel->getId(),
                    base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
                $encryptionKeyMessage = KeyFactory::deriveEncryptionKey(
                    base64_decode($toUserSalt) . 'message' . $messageModel->getId(),
                    base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));

                /*
                 * encrypt the subject and the message, each with their own encryption key
                 */
                $cipherSubject = Crypto::encrypt($subject, $encryptionKeySubject, true);
                $cipherMessage = Crypto::encrypt($message, $encryptionKeyMessage, true);

                /*
                 * create an authentication key based on the sender's salt and the system wide salt.
                 */
                $authenticationKey = KeyFactory::deriveAuthenticationKey(
                    base64_decode($fromUserSalt), base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));

                $mac = Crypto::authenticate($cipherMessage, $authenticationKey, true);

                $messageModel->setSubject(base64_encode($cipherSubject))
                    ->setMessage(base64_encode($cipherMessage))
                    ->setMac(base64_encode($mac));

                $this->em->persist($messageModel);
                $this->em->flush();

                return $messageModel->getId();
            }
        }

        throw new \InvalidArgumentException('From or to user does not exist');
    }
}
