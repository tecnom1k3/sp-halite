<?php
namespace Acme\Service;

use Doctrine\DBAL\Connection;

class User
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * User constructor.
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $rs = $this->db->fetchAll('select id, name from users');
        return $rs;
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getSalt($userId)
    {
        $sql = 'SELECT salt FROM users where id = ?;';
        $rs = $this->db->fetchAssoc($sql, [$userId]);
        return $rs['salt'];
    }
}
