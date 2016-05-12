<?php
namespace Acme\Service;

use Acme\Service\User as UserService;
use \ParagonIE\Halite\Symmetric\EncryptionKey;
use \ParagonIE\Halite\Primitive\Symmetric;

class Message
{
    private $db;
    private $userService;

    public function __construct($db, UserService $userService)
    {
        $this->db = $db;
        $this->userService = $userService;
    }

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
	return $rs;
    }
    
    public function save($from, $to, $subject, $message)
    {
        /* TODO: this should be in its own class */
        $targetUserSalt = $this->userService->getSalt($to);
        $encryption_key = EncryptionKey::deriveFromPassword(base64_decode($targetUserSalt), base64_decode(getenv('HALITE_ENCRYPTION_KEY_SALT')));
        
        $cipherSubject = Symmetric::encrypt($subject, $encryption_key);
        $cipherMessage = Symmetric::encrypt($message, $encryption_key);	
 
        $this->db->insert('messages', ['users_id'=>$to, 'fromUserId'=>$from, 'subject'=>$cipherSubject, 'message'=>$cipherMessage]);
   	return $this->db->lastInsertId();
    }
}
