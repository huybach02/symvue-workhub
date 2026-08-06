<?php

declare(strict_types=1);

namespace App\Repository;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
use App\Entity\InventoryBalance;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\Merchandise;
use App\Entity\Warehouse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryBalance>
 */
class InventoryBalanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryBalance::class);
    }

    /**
     * Tìm các balance tồn khả dụng theo kho + nguyên liệu để xuất kho.
     *
     * - Chỉ lấy lot AVAILABLE, chưa hết hạn (expiryDate >= now).
     * - Tồn khả dụng = onHand - reserved - blocked.
     * - Sắp xếp FEFO: expiryDate ASC, receivedAt ASC, id ASC.
     * - Khóa PESSIMISTIC_WRITE các balance trả về để tránh xuất kho đồng thời.
     *
     * @return InventoryBalance[]
     */
    public function findAvailableForIssue(
        Warehouse $warehouse,
        Merchandise $merchandise,
        \DateTimeInterface $now,
    ): array {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.lot', 'lot')
            ->where('b.warehouse = :warehouse')
            ->andWhere('b.merchandise = :merchandise')
            ->andWhere('lot.status = :lotStatus')
            ->andWhere('lot.expiryDate >= :now')
            ->setParameter('warehouse', $warehouse)
            ->setParameter('merchandise', $merchandise)
            ->setParameter('lotStatus', InventoryLotStatus::Available->value)
            ->setParameter('now', $now)
            ->orderBy('lot.expiryDate', 'ASC')
            ->addOrderBy('lot.receivedAt', 'ASC')
            ->addOrderBy('lot.id', 'ASC');

        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        $balances = $query->getResult();

        return array_filter(
            $balances,
            fn (InventoryBalance $balance): bool => MathHelper::comp(
                MathHelper::sub(
                    $balance->getOnHandBaseQuantity(),
                    MathHelper::add($balance->getReservedBaseQuantity(), $balance->getBlockedBaseQuantity()),
                ),
                '0',
            ) > 0,
        );
    }

    /**
     * Giá nhập đơn vị của lot = unitCostBase của movement STOCK_IN gốc
     * (lot được nhập vào kho qua luồng phiếu nhập kho). Fallback '0.0000' nếu chưa có.
     */
    public function findUnitCostBaseForLot(InventoryLot $lot): string
    {
        $row = $this->getEntityManager()->createQuery(
            'SELECT m.unitCostBase
             FROM ' . InventoryMovement::class . ' m
             WHERE m.lot = :lot
               AND m.movementType = :movementType
               AND m.quantityBaseDelta > 0
             ORDER BY m.postedAt ASC, m.id ASC'
        )
            ->setParameter('lot', $lot)
            ->setParameter('movementType', InventoryMovementType::StockIn->value)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $row['unitCostBase'] ?? '0.0000';
    }
}
