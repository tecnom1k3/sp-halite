<?php
namespace Acme\Service;

use Acme\Model\User as UserModel;
use Doctrine\ORM\EntityManager;

/**
 * Class User
 * @package Acme\Service
 */
class User
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * User constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->setEm($em);
    }

    /**
     * @param EntityManager $em
     */
    public function setEm(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $userRepository = $this->em->getRepository('Acme\Model\User');

        $result = $userRepository->findAll();

        $arrUsers = [];

        /** @var UserModel $record */
        foreach ($result as $record) {
            array_push($arrUsers, [
                'id' => $record->getId(),
                'name' => $record->getName(),
            ]);
        }

        return $arrUsers;
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getSalt($userId)
    {
        $sql = 'SELECT salt FROM users where id = ?;';
        $rs = $this->em->getConnection()->fetchAssoc($sql, [$userId]);
        return $rs['salt'];
    }
}
