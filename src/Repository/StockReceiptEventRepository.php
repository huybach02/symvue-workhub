<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockReceiptEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockReceiptEvent>
 */
class StockReceiptEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockReceiptEvent::class);
    }
}
