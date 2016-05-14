<?php
namespace Acme\Model\Repository;

use Acme\Model\User as UserModel;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class Message extends EntityRepository
{
    /**
     * @param $userId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getList($userId)
    {
        $userRepository = $this->getEntityManager()->getRepository('Acme\Model\User');

        /** @var UserModel $user */
        if (($user = $userRepository->find($userId)) == true) {
            /** @var QueryBuilder $qb */
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->select(['m.id', 'u.name', 'm.subject'])
                ->from('Acme\Model\Message', 'm')
                ->innerJoin('Acme\Model\User', 'u', Join::WITH, 'm.fromUser = u.id')
                ->where('m.toUser = :user')
                ->setParameter('user', $user);
            $query = $qb->getQuery();
            return $query->getResult();
        }
        throw new \InvalidArgumentException('User not found');
    }
}