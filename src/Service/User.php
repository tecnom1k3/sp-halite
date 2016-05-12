<?php
namespace Acme\Service;

class User
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getList()
    {
	$rs = $this->db->fetchAll('select id, name from users');
	return $rs;
    }

    public function getSalt($userId)
    {
        $sql = 'SELECT salt FROM users where id = ?;';
        $rs = $this->db->fetchAssoc($sql, [$userId]);
        return $rs['salt'];   
    }
}
