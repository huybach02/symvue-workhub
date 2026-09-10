<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiningTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DiningTable>
 */
class DiningTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiningTable::class);
    }

    /**
     * Tìm tất cả các bàn trong khoảng số bàn [from, to]
     *
     * @return DiningTable[]
     */
    public function findTablesByRange(int $from, int $to): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.tableNumber >= :from')
            ->andWhere('d.tableNumber <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('d.tableNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tìm bàn theo số bàn
     */
    public function findOneByTableNumber(int $tableNumber): ?DiningTable
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.tableNumber = :tableNumber')
            ->setParameter('tableNumber', $tableNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
