<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductionOrderMaterial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionOrderMaterial>
 */
class ProductionOrderMaterialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionOrderMaterial::class);
    }
}
