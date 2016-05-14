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
     * @param $userId
     * @return mixed
     */
    public function getSalt($userId)
    {
        $userRepository = $this->getUserRepository();
        /** @var UserModel $rs */
        $userModel = $userRepository->find($userId);

        if ($userModel instanceof UserModel) {
            return $userModel->getSalt();
        }

        throw new \InvalidArgumentException('User not found');
    }

    /**
     * @return EntityRepository
     */
    protected function getUserRepository()
    {
        return $this->em->getRepository('Acme\Model\User');
    }
}
