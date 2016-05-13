<?php
namespace Acme\Service;

use Acme\Service\Halite as HaliteService;
use Acme\Service\User as UserService;
use Doctrine\DBAL\Connection;
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
     * @var Connection
     */
    private $db;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var HaliteService
     */
    private $haliteService;

    public function __construct(Connection $db, UserService $userService, HaliteService $halite)
    {
        $this->setDb($db);
        $this->setUserService($userService);
        $this->setHaliteService($halite);
    }

    /**
     * @param Connection $db
     */
    public function setDb(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param UserService $userService
     */
    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param HaliteService $haliteService
     */
    public function setHaliteService(HaliteService $haliteService)
    {
        $this->haliteService = $haliteService;
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

        $encryptionKeySubject = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_SUBJECT);

        $arrResults = [];

        foreach ($rs as $message) {
            $plainSubject = $this->haliteService->decrypt($message['subject'], $encryptionKeySubject);
            $message['subject'] = $plainSubject;
            array_push($arrResults, $message);
        }

        return $arrResults;
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
     */
    public function get($userId, $messageId)
    {
        $sql = <<<EOF
SELECT     m.id, 
           m.subject, 
           m.message, 
           u.name 
FROM       messages AS m 
INNER JOIN users    AS u 
where      m.users_id = ?
AND        m.id = ?
EOF;
        $rs = $this->db->fetchAll($sql, [$userId, $messageId]);

        if ((is_array($rs)) && (count($rs) > 0)) {
            $result = array_pop($rs);
            $encryptionKeySubject = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_SUBJECT);
            $encryptionKeyMessage = $this->deriveKeyFromUserId($userId, self::TARGET_DERIVE_MESSAGE);
            $plainSubject = $this->haliteService->decrypt($result['subject'], $encryptionKeySubject);
            $plainMessage = $this->haliteService->decrypt($result['message'], $encryptionKeyMessage);
            $result['subject'] = $plainSubject;
            $result['message'] = $plainMessage;
            return $result;
        }

        return [];
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

        $this->db->insert('messages',
            ['users_id' => $to, 'fromUserId' => $from, 'subject' => $cipherSubject, 'message' => $cipherMessage]);
        return $this->db->lastInsertId();
    }
}
