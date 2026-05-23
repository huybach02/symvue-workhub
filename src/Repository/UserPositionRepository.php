<?php

namespace App\Repository;

use App\Entity\Department;
use App\Entity\User;
use App\Entity\UserPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPosition>
 */
class UserPositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPosition::class);
    }

    public function findLatestPrimaryPositionByUser(User $user): ?UserPosition
    {
        return $this->findOneBy(
            ['member' => $user, 'isPrimary' => true],
            ['id' => 'DESC']
        );
    }

    public function findPrimaryManagerByDepartmentExcludingUser(
        Department $department,
        User $excludedUser
    ): ?UserPosition {
        $result = $this->createQueryBuilder('userPosition')
            ->join('userPosition.position', 'position')
            ->andWhere('userPosition.department = :department')
            ->andWhere('userPosition.isPrimary = 1')
            ->andWhere('position.isManager = 1')
            ->andWhere('userPosition.member != :excludedUser')
            ->setParameter('department', $department)
            ->setParameter('excludedUser', $excludedUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof UserPosition ? $result : null;
    }
}
