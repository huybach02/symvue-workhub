<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MerchandiseRecipeItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MerchandiseRecipeItem>
 */
class MerchandiseRecipeItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MerchandiseRecipeItem::class);
    }
}
