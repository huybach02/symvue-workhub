<?php

namespace App\Repository;

use App\Entity\WorkShiftAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkShiftAssignment>
 */
class WorkShiftAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkShiftAssignment::class);
    }

    public function findByDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?array $memberIds = null,
    ): array
    {
        $qb = $this->createQueryBuilder('wsa')
            ->leftJoin('wsa.member', 'm')
            ->addSelect('m')
            ->leftJoin('wsa.workShift', 'ws')
            ->addSelect('ws')
            ->andWhere('wsa.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d'))
            ->setParameter('endDate', $endDate->format('Y-m-d'))
        ;

        if ($memberIds !== null) {
            if ($memberIds === []) {
                return [];
            }

            $qb
                ->andWhere('m.id IN (:memberIds)')
                ->setParameter('memberIds', $memberIds)
            ;
        }

        return $qb
            ->orderBy('wsa.date', 'ASC')
            ->addOrderBy('wsa.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return WorkShiftAssignment[] Returns an array of WorkShiftAssignment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('w.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WorkShiftAssignment
    //    {
    //        return $this->createQueryBuilder('w')
    //            ->andWhere('w.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
