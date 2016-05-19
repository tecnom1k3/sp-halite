<?php
namespace Acme\Service;

use Acme\Model\User as UserModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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
     * @return $this
     */
    public function setEm(EntityManager $em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $userRepository = $this->getUserRepository();

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
     * @return EntityRepository
     */
    protected function getUserRepository()
    {
        return $this->em->getRepository('Acme\Model\User');
    }
    
    /**
     * @param $userId
     * @return null|UserModel
     */
    public function findById($userId)
    {
        return $this->em->getRepository('Acme\Model\User')->find($userId);
    }
}
