<?php
namespace Acme\Service;

use Acme\Model\Message as MessageModel;
use Acme\Model\Repository\Message as MessageRepository;
use Acme\Model\User as UserModel;
use Acme\Service\Halite as HaliteService;
use Acme\Service\User as UserService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
     * User model
     */
    const REPOSITORY_USER = 'Acme\Model\User';

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
     */
    public function getList($userId)
    {

        $messageList = $this->getMessageRepository()->getList($userId);
        $encryptionKeySubject = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_SUBJECT);
        $arrResults = [];

        foreach ($messageList as $message) {
            $plainSubject = $this->haliteService->decrypt(stream_get_contents($message['subject']),
                $encryptionKeySubject);
            $message['subject'] = $plainSubject;
            array_push($arrResults, $message);
        }

        return $arrResults;
    }

    /**
     * @return MessageRepository
     */
    protected function getMessageRepository()
    {
        return $this->getRepository(self::REPOSITORY_MESSAGE);
    }

    /**
     * @param $repository
     * @return EntityRepository
     */
    protected function getRepository($repository)
    {
        return $this->em->getRepository($repository);
    }

    /**
     * @param $userId
     * @param string $target
     * @return EncryptionKey
     */
    protected function deriveKeyFromUserId($userId, $target = null)
    {
        $targetUserSalt = $this->userService->getSalt($userId);
        return $this->haliteService->deriveKey(base64_decode($targetUserSalt) . $target);
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
                $encryptionKeySubject = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_SUBJECT);
                $encryptionKeyMessage = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_MESSAGE);

                $plainSubject = $this->haliteService->decrypt(stream_get_contents($message->getSubject()),
                    $encryptionKeySubject);
                $plainMessage = $this->haliteService->decrypt(stream_get_contents($message->getMessage()),
                    $encryptionKeyMessage);

                $fromUser = $message->getFromUser();

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
        $encryptionKeySubject = $this->deriveKeyFromUserId($to, self::TARGET_DERIVE_SUBJECT);
        $encryptionKeyMessage = $this->deriveKeyFromUserId($to, self::TARGET_DERIVE_MESSAGE);

        $cipherSubject = $this->haliteService->encrypt($subject, $encryptionKeySubject);
        $cipherMessage = $this->haliteService->encrypt($message, $encryptionKeyMessage);

        $messageModel = $this->getNewMessageModel();
        $fromUserRepository = $this->getUserRepository();
        $toUserRepository = clone $fromUserRepository;

        /** @var UserModel $fromUserModel */
        if (($fromUserModel = $fromUserRepository->find($from)) == true) {
            /** @var UserModel $toUserModel */
            if (($toUserModel = $toUserRepository->find($to)) == true) {
                $messageModel->setFromUser($fromUserModel)
                    ->setToUser($toUserModel)
                    ->setSubject($cipherSubject)
                    ->setMessage($cipherMessage);

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

    /**
     * @return EntityRepository
     */
    protected function getUserRepository()
    {
        return $this->getRepository(self::REPOSITORY_USER);
    }
}
