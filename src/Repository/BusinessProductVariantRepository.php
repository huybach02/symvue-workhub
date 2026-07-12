<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BusinessProductVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusinessProductVariant>
 */
class BusinessProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessProductVariant::class);
    }
}
