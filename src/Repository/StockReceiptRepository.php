<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockReceipt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockReceipt>
 */
class StockReceiptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockReceipt::class);
    }

    public function getNextSupplementNo(StockReceipt $parentReceipt): int
    {
        $maxNo = $this->createQueryBuilder('r')
            ->select('MAX(r.supplementNo)')
            ->where('r.parentReceipt = :parent')
            ->setParameter('parent', $parentReceipt)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxNo) + 1;
    }

    public function findChildren(int $parentReceiptId): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('IDENTITY(e.parentReceipt) = :parentId')
            ->setParameter('parentId', $parentReceiptId)
            ->orderBy('e.supplementNo', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countChildren(int $parentReceiptId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('IDENTITY(e.parentReceipt) = :parentId')
            ->setParameter('parentId', $parentReceiptId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
