<?php
namespace Acme\Service;

use Acme\Model\Message as MessageModel;
use Acme\Model\Repository\Message as MessageRepository;
use Acme\Model\User as UserModel;
use Acme\Service\Halite as HaliteService;
use Acme\Service\User as UserService;
use Doctrine\ORM\EntityManager;
use ParagonIE\Halite\Symmetric\AuthenticationKey;
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
            $encryptionKeySubject = $this->deriveCipherKeyFromSalt($user->getSalt(), self::TARGET_DERIVE_SUBJECT);
            $arrResults = [];

            foreach ($messageList as $message) {
                $plainSubject = $this->haliteService->decrypt($message['subject'],
                    $encryptionKeySubject);
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

                $encryptionKeySubject = $this->deriveCipherKeyFromSalt($toUserSalt, self::TARGET_DERIVE_SUBJECT);
                $encryptionKeyMessage = $this->deriveCipherKeyFromSalt($toUserSalt, self::TARGET_DERIVE_MESSAGE);

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
        $messageModel = $this->getNewMessageModel();

        /** @var UserModel $fromUserModel */
        if (($fromUserModel = $this->userService->findById($from)) == true) {
            /** @var UserModel $toUserModel */
            if (($toUserModel = $this->userService->findById($to)) == true) {
                $toUserSalt = $toUserModel->getSalt();

                $encryptionKeySubject = $this->deriveCipherKeyFromSalt($toUserSalt, self::TARGET_DERIVE_SUBJECT);
                $encryptionKeyMessage = $this->deriveCipherKeyFromSalt($toUserSalt, self::TARGET_DERIVE_MESSAGE);

                $cipherSubject = $this->haliteService->encrypt($subject, $encryptionKeySubject);
                $cipherMessage = $this->haliteService->encrypt($message, $encryptionKeyMessage);

                $authenticationKey = $this->deriveAuthenticationKeyFromSalt($fromUserModel->getSalt());

                $mac = $this->haliteService->authenticate($cipherMessage, $authenticationKey);

                $messageModel->setFromUser($fromUserModel)
                    ->setToUser($toUserModel)
                    ->setSubject($cipherSubject)
                    ->setMessage($cipherMessage)
                    ->setMac($mac);

                $this->em->persist($messageModel);
                $this->em->flush();

                return $messageModel->getId();
            }
        }

        throw new \InvalidArgumentException('From or to user does not exist');
    }

    /**
     * @return MessageModel
     */
    protected function getNewMessageModel()
    {
        return new MessageModel;
    }
}
