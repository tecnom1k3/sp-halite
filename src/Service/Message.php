<?php
namespace Acme\Service;

use Acme\Service\User as UserService;
use Doctrine\DBAL\Connection;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;

/**
 * Class Message
 * @package Acme\Service
 *
 * @TODO: create a wrapper for Halite methods
 */
class Message
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * Derivation constant for subject encryption
     */
    const TARGET_DERIVE_SUBJECT = 'subject';

    /**
     * Derivation constant for message encryption
     */
    const TARGET_DERIVE_MESSAGE = 'message';

    public function __construct(Connection $db, UserService $userService)
    {
        $this->db = $db;
        $this->userService = $userService;
    }

    /**
     * @param int $userId
     * @return array
     */
    public function getList($userId)
    {
        $sql = <<<EOF
SELECT messages.id, 
       users.name, 
       messages.subject 
FROM   messages 
       INNER JOIN users 
               ON messages.fromUserId = users.id 
WHERE  messages.users_id = ? 
EOF;
        $rs = $this->db->fetchAll($sql, [$userId]);

        $encryptionKeySubject = $this->deriveKey($userId, self::TARGET_DERIVE_SUBJECT);

        $arrResults = [];

        foreach ($rs as $message) {
            $plainSubject = Crypto::decrypt($message['subject'], $encryptionKeySubject, true);
            $message['subject'] = $plainSubject;
            array_push($arrResults, $message);
        }

        return $arrResults;
    }

    protected function deriveKey($userId, $target=NULL)
    {
        /* TODO: this should be in its own class */
        $targetUserSalt = $this->userService->getSalt($userId);
        return KeyFactory::deriveEncryptionKey(base64_decode($targetUserSalt) . $target, //different from docs
            base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
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
        /* TODO: this should be in its own class */
        $encryptionKeySubject = $this->deriveKey($to, self::TARGET_DERIVE_SUBJECT);
        $encryptionKeyMessage = $this->deriveKey($to, self::TARGET_DERIVE_MESSAGE);

        $cipherSubject = Crypto::encrypt($subject, $encryptionKeySubject, true); //different from docs
        $cipherMessage = Crypto::encrypt($message, $encryptionKeyMessage, true); //different from docs

        $this->db->insert('messages',
            ['users_id' => $to, 'fromUserId' => $from, 'subject' => $cipherSubject, 'message' => $cipherMessage]);
        return $this->db->lastInsertId();
    }
}
