<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductionItemInspection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionItemInspection>
 */
class ProductionItemInspectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionItemInspection::class);
    }
}
