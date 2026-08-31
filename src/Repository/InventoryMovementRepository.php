<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InventoryMovement;
use App\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryMovement>
 */
class InventoryMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryMovement::class);
    }

    public function createForWarehouseQueryBuilder(Warehouse $warehouse): QueryBuilder
    {
        return $this->createQueryBuilder('movement')
            ->addSelect('merchandise', 'lot', 'baseUnit', 'postedBy')
            ->innerJoin('movement.merchandise', 'merchandise')
            ->innerJoin('movement.lot', 'lot')
            ->innerJoin('movement.baseUnit', 'baseUnit')
            ->innerJoin('movement.postedBy', 'postedBy')
            ->andWhere('movement.warehouse = :warehouse')
            ->setParameter('warehouse', $warehouse)
            ->orderBy('movement.postedAt', 'DESC')
            ->addOrderBy('movement.id', 'DESC');
    }
}
