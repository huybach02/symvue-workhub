<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockReceiptItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockReceiptItem>
 */
class StockReceiptItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockReceiptItem::class);
    }
}
