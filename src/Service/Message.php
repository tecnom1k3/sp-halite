<?php
namespace Acme\Service;

class Message
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getList($userId)
    {
	$sql = <<<EOF
SELECT messages.id, 
       users.name, 
       messages.subject 
FROM   messages 
       INNER JOIN users 
               ON messages.`from` = users.id 
WHERE  messages.users_id = ? 
EOF;
	$rs = $this->db->fetchAll($sql, [$userId]);
	return $rs;
    }
}
