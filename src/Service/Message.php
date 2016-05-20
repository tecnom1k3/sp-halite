<?php
namespace Acme\Service;

use Acme\Model\Message as MessageModel;
use Acme\Model\Repository\Message as MessageRepository;
use Acme\Model\User as UserModel;
use Acme\Service\User as UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;

/**
 * Class Message
 * @package Acme\Service
 */
class Message
{
    /**
     * @var UserService
     */
    private $userService;


    /**
     * @var EntityManager
     */
    private $em;

    /**
     * Message constructor.
     * @param EntityManager $entityManager
     * @param User $userService
     */
    public function __construct(EntityManager $entityManager, UserService $userService)
    {
        $this->setUserService($userService)
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
            $messageList = $this->em->getRepository('Acme\Model\Message')->getList($user->getId());
            $salt = $user->getSalt();
            $arrResults = [];

            foreach ($messageList as $message) {
                $encryptionKeySubject = KeyFactory::deriveEncryptionKey(base64_decode($salt) . 'subject' . $message['id'],
                    base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
                $plainSubject = Crypto::decrypt(base64_decode($message['subject']), $encryptionKeySubject, true);
                $message['subject'] = $plainSubject;
                array_push($arrResults, $message);
            }
            return $arrResults;
        }

        throw new \InvalidArgumentException('User does not exists');
    }

    /**
     * @param $userId
     * @param $messageId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function get($userId, $messageId)
    {
        /** @var MessageRepository $repository */
        $repository = $this->em->getRepository('Acme\Model\Message');
        /** @var MessageModel $message */
        if (($message = $repository->find($messageId)) == true) {
            $toUser = $message->getToUser();

            /*
             * Verify that the message belongs to the intended user
             */
            if ($toUser->getId() == $userId) {
                $toUserSalt = $toUser->getSalt();
                $fromUser = $message->getFromUser();

                $encryptionKeySubject = KeyFactory::deriveEncryptionKey(base64_decode($toUserSalt) . 'subject' . $message->getId(),
                    base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));

                $encryptionKeyMessage = KeyFactory::deriveEncryptionKey(base64_decode($toUserSalt) . 'message' . $message->getId(),
                    base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));

                $plainSubject = Crypto::decrypt(base64_decode($message->getSubject()), $encryptionKeySubject, true);

                $plainMessage = Crypto::decrypt(base64_decode($message->getMessage()), $encryptionKeyMessage, true);

                return [
                    'id' => $message->getId(),
                    'subject' => $plainSubject,
                    'message' => $plainMessage,
                    'name' => $fromUser->getName(),
                ];

            }
        }

        throw new \InvalidArgumentException('Message not found');
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

                $messageModel->setSubject(base64_encode($cipherSubject))->setMessage(base64_encode($cipherMessage));

                $this->em->persist($messageModel);
                $this->em->flush();

                return $messageModel->getId();
            }
        }

        throw new \InvalidArgumentException('From or to user does not exist');
    }
}
