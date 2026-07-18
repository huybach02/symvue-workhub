<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockReceiptProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockReceiptProvider>
 */
class StockReceiptProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockReceiptProvider::class);
    }
}
