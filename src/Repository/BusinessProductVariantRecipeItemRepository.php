<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BusinessProductVariantRecipeItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusinessProductVariantRecipeItem>
 */
class BusinessProductVariantRecipeItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessProductVariantRecipeItem::class);
    }
}
