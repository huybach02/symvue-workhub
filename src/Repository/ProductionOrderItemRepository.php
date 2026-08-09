<?php

declare(strict_types=1);

namespace App\Repository;

use App\Class\ProductionOrderStatus;
use App\Entity\ProductionOrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionOrderItem>
 */
class ProductionOrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionOrderItem::class);
    }

    /** @return ProductionOrderItem[] */
    public function findByIdsWithDetails(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('item')
            ->leftJoin('item.productionOrder', 'productionOrder')->addSelect('productionOrder')
            ->leftJoin('item.finishedProduct', 'finishedProduct')->addSelect('finishedProduct')
            ->leftJoin('item.baseUnit', 'baseUnit')->addSelect('baseUnit')
            ->andWhere('item.id IN (:ids)')
            ->setParameter('ids', array_values(array_unique($ids)))
            ->getQuery()
            ->getResult();
    }

    /** @return ProductionOrderItem[] */
    public function findWaitingSupplementByMerchandiseId(int $merchandiseId): array
    {
        return $this->createQueryBuilder('item')
            ->join('item.productionOrder', 'productionOrder')->addSelect('productionOrder')
            ->andWhere('IDENTITY(item.finishedProduct) = :merchandiseId')
            ->andWhere('item.status = :status')
            ->setParameter('merchandiseId', $merchandiseId)
            ->setParameter('status', ProductionOrderStatus::WaitingSupplement->value)
            ->orderBy('productionOrder.createdAt', 'ASC')
            ->addOrderBy('item.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
