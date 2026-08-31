<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\MathHelper;
use App\DTO\WarehouseDTO;
use App\Entity\InventoryBalance;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\Merchandise;
use App\Entity\Unit;
use App\Entity\User;
use App\Entity\UserPosition;
use App\Entity\Warehouse;
use App\Repository\InventoryBalanceRepository;
use App\Repository\InventoryMovementRepository;
use App\Repository\WarehouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepository $warehouseRepository,
        private readonly InventoryBalanceRepository $inventoryBalanceRepository,
        private readonly InventoryMovementRepository $inventoryMovementRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params, User $currentUser): array
    {
        $qb = $this->warehouseRepository->createQueryBuilder('e');
        $this->restrictToAssignedBranches($qb, $currentUser);

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Warehouse $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params, User $currentUser): array
    {
        $qb = $this->warehouseRepository->createQueryBuilder('e')
            ->andWhere('e.status = true');
        $this->restrictToAssignedBranches($qb, $currentUser);

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (Warehouse $item) {
            return [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id, User $currentUser): array
    {
        $item = $this->getWarehouseOrFail($id, $currentUser);

        return $item->jsonSerialize();
    }

    public function findInventoryBalances(
        int $id,
        array $params,
        User $currentUser,
    ): array
    {
        $warehouse = $this->getWarehouseOrFail($id, $currentUser);
        $qb = $this->inventoryBalanceRepository
            ->createForWarehouseQueryBuilder($warehouse);

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'balance',
            [
                'merchandise' => [
                    'joinField' => 'balance.merchandise',
                    'alias' => 'merchandise_filter',
                    'targetField' => 'name',
                ],
                'lot' => [
                    'joinField' => 'balance.lot',
                    'alias' => 'lot_code_filter',
                    'targetField' => 'internalCode',
                ],
                'lotStatus' => [
                    'joinField' => 'balance.lot',
                    'alias' => 'lot_status_filter',
                    'targetField' => 'status',
                ],
                'expiryDate' => [
                    'joinField' => 'balance.lot',
                    'alias' => 'lot_expiry_filter',
                    'targetField' => 'expiryDate',
                ],
            ],
        );

        $result['collection'] = array_map(
            fn (InventoryBalance $balance): array => $this->serializeBalance($balance),
            $result['collection'],
        );

        return $result;
    }

    public function findInventoryMovements(
        int $id,
        array $params,
        User $currentUser,
    ): array
    {
        $warehouse = $this->getWarehouseOrFail($id, $currentUser);
        $qb = $this->inventoryMovementRepository
            ->createForWarehouseQueryBuilder($warehouse);

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'movement',
            [
                'merchandise' => [
                    'joinField' => 'movement.merchandise',
                    'alias' => 'merchandise_filter',
                    'targetField' => 'name',
                ],
                'lot' => [
                    'joinField' => 'movement.lot',
                    'alias' => 'lot_filter',
                    'targetField' => 'internalCode',
                ],
                'postedBy' => [
                    'joinField' => 'movement.postedBy',
                    'alias' => 'posted_by_filter',
                    'targetField' => 'name',
                ],
            ],
        );

        $result['collection'] = array_map(
            fn (InventoryMovement $movement): array => $this->serializeMovement($movement),
            $result['collection'],
        );

        return $result;
    }

    public function create(WarehouseDTO $dto): array
    {
        $item = new Warehouse();
        
        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);
        
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, WarehouseDTO $dto): array
    {
        $item = $this->warehouseRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // TODO: Map DTO properties to entity
        // Example: $item->setName($dto->name);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->warehouseRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function getWarehouseOrFail(
        int $id,
        User $currentUser,
    ): Warehouse
    {
        $qb = $this->warehouseRepository
            ->createQueryBuilder('warehouse')
            ->andWhere('warehouse.id = :warehouseId')
            ->setParameter('warehouseId', $id);

        $this->restrictToAssignedBranches($qb, $currentUser, 'warehouse');

        $warehouse = $qb->getQuery()->getOneOrNullResult();

        if (!$warehouse instanceof Warehouse) {
            throw new \Exception(t('error.not_found'));
        }

        return $warehouse;
    }

    private function restrictToAssignedBranches(
        QueryBuilder $qb,
        User $currentUser,
        string $warehouseAlias = 'e',
    ): void {
        if (isAdmin($currentUser)) {
            return;
        }

        $currentTimestamp = time();
        $assignmentQb = $this->entityManager
            ->createQueryBuilder()
            ->select('1')
            ->from(UserPosition::class, 'assignedPosition')
            ->innerJoin('assignedPosition.department', 'assignedDepartment')
            ->andWhere('assignedPosition.member = :warehouseCurrentUser')
            ->andWhere('assignedPosition.status = 1')
            ->andWhere(sprintf('assignedDepartment.branch = %s.branch', $warehouseAlias))
            ->andWhere(
                '(assignedPosition.startTemp IS NULL OR assignedPosition.startTemp <= :warehouseCurrentTimestamp)',
            )
            ->andWhere(
                '(assignedPosition.endTemp IS NULL OR assignedPosition.endTemp > :warehouseCurrentTimestamp)',
            );

        $qb
            ->andWhere($qb->expr()->exists($assignmentQb->getDQL()))
            ->setParameter('warehouseCurrentUser', $currentUser)
            ->setParameter('warehouseCurrentTimestamp', $currentTimestamp);
    }

    private function serializeBalance(InventoryBalance $balance): array
    {
        return [
            'id' => $balance->getId(),
            'merchandise' => $this->serializeMerchandise($balance->getMerchandise()),
            'lot' => $this->serializeLot($balance->getLot()),
            'baseUnit' => $this->serializeUnit($balance->getMerchandise()?->getBaseUnit()),
            'onHandBaseQuantity' => formatDecimal($balance->getOnHandBaseQuantity()),
            'reservedBaseQuantity' => formatDecimal($balance->getReservedBaseQuantity()),
            'blockedBaseQuantity' => formatDecimal($balance->getBlockedBaseQuantity()),
            'availableBaseQuantity' => formatDecimal(MathHelper::sub(
                $balance->getOnHandBaseQuantity(),
                MathHelper::add(
                    $balance->getReservedBaseQuantity(),
                    $balance->getBlockedBaseQuantity(),
                ),
            )),
        ];
    }

    private function serializeMovement(InventoryMovement $movement): array
    {
        return [
            'id' => $movement->getId(),
            'movementType' => $movement->getMovementType(),
            'sourceType' => $movement->getSourceType(),
            'merchandise' => $this->serializeMerchandise($movement->getMerchandise()),
            'lot' => $this->serializeLot($movement->getLot()),
            'quantityBaseDelta' => formatDecimal($movement->getQuantityBaseDelta()),
            'baseUnit' => $this->serializeUnit($movement->getBaseUnit()),
            'unitCostBase' => formatDecimal($movement->getUnitCostBase()),
            'note' => $movement->getNote(),
            'postedAt' => $movement->getPostedAt()?->format('Y-m-d H:i:s'),
            'postedBy' => $movement->getPostedBy()
                ? [
                    'id' => $movement->getPostedBy()?->getId(),
                    'name' => $movement->getPostedBy()?->getName(),
                ]
                : null,
        ];
    }

    private function serializeMerchandise(?Merchandise $merchandise): ?array
    {
        if (!$merchandise) {
            return null;
        }

        return [
            'id' => $merchandise->getId(),
            'code' => $merchandise->getCode(),
            'name' => $merchandise->getName(),
        ];
    }

    private function serializeLot(?InventoryLot $lot): ?array
    {
        if (!$lot) {
            return null;
        }

        return [
            'id' => $lot->getId(),
            'internalCode' => $lot->getInternalCode(),
            'originType' => $lot->getOriginType(),
            'supplierLotCode' => $lot->getSupplierLotCode(),
            'productionLotCode' => $lot->getProductionLotCode(),
            'manufactureDate' => $lot->getManufactureDate()?->format('Y-m-d'),
            'expiryDate' => $lot->getExpiryDate()?->format('Y-m-d'),
            'status' => $lot->getStatus(),
        ];
    }

    private function serializeUnit(?Unit $unit): ?array
    {
        if (!$unit) {
            return null;
        }

        return [
            'id' => $unit->getId(),
            'code' => $unit->getCode(),
            'name' => $unit->getName(),
            'symbol' => $unit->getSymbol(),
        ];
    }
}
