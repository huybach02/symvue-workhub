<?php

namespace App\Repository;

use App\Entity\FixedScheduleOverride;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FixedScheduleOverride>
 */
class FixedScheduleOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FixedScheduleOverride::class);
    }

    public function hasFulltimeOverrideOverlap(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): bool
    {
        $count = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.member = :member')
            ->andWhere('f.type = :type')
            ->andWhere('f.startDate <= :endDate')
            ->andWhere('f.endDate >= :startDate')
            ->setParameter('member', $user)
            ->setParameter('type', 'fulltime')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findOverlappingOverrides(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.member = :member')
            ->andWhere('f.type = :type')
            ->andWhere('f.startDate <= :endDate')
            ->andWhere('f.endDate >= :startDate')
            ->setParameter('member', $user)
            ->setParameter('type', 'fulltime')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function clearOverridesByUser(int $userId): void
    {
        $this->createQueryBuilder('f')
            ->delete()
            ->andWhere('f.member = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
