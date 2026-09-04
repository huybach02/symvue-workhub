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

    public function findActivePrimaryPositionByUser(User $user): ?UserPosition
    {
        $currentTimestamp = time();

        $result = $this->createQueryBuilder('userPosition')
            ->addSelect('department', 'branch', 'warehouse')
            ->innerJoin('userPosition.department', 'department')
            ->innerJoin('department.branch', 'branch')
            ->leftJoin('branch.warehouse', 'warehouse')
            ->andWhere('userPosition.member = :user')
            ->andWhere('userPosition.isPrimary = 1')
            ->andWhere('userPosition.status = 1')
            ->andWhere('(userPosition.startTemp IS NULL OR userPosition.startTemp <= :currentTimestamp)')
            ->andWhere('(userPosition.endTemp IS NULL OR userPosition.endTemp > :currentTimestamp)')
            ->setParameter('user', $user)
            ->setParameter('currentTimestamp', $currentTimestamp)
            ->orderBy('userPosition.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof UserPosition ? $result : null;
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
