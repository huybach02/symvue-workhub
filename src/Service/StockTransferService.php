<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementSourceType;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
use App\Class\Request\RequestConstant;
use App\DTO\StockTransferDTO;
use App\Entity\InventoryBalance;
use App\Entity\InventoryMovement;
use App\Entity\Request;
use App\Entity\RequestEvent;
use App\Entity\StockTransfer;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Repository\InventoryBalanceRepository;
use App\Repository\StockTransferRepository;
use App\Repository\UserPositionRepository;
use App\Repository\WarehouseRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class StockTransferService
{
    private const QUANTITY_SCALE = 6;

    public function __construct(
        private readonly StockTransferRepository $stockTransferRepository,
        private readonly InventoryBalanceRepository $inventoryBalanceRepository,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly UserPositionRepository $userPositionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getRequestContext(User $currentUser): array
    {
        $sourceWarehouse = $this->resolveSourceWarehouse($currentUser);
        $today = new \DateTime('today');
        $balances = $this->inventoryBalanceRepository
            ->findTransferableFinishedGoods($sourceWarehouse, $today);
        $destinationWarehouses = $this->warehouseRepository
            ->createQueryBuilder('warehouse')
            ->andWhere('warehouse.status = true')
            ->andWhere('warehouse != :sourceWarehouse')
            ->setParameter('sourceWarehouse', $sourceWarehouse)
            ->orderBy('warehouse.name', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'sourceWarehouse' => $this->warehouseSnapshot($sourceWarehouse),
            'destinationWarehouses' => array_map(
                fn (Warehouse $warehouse): array => $this->warehouseSnapshot($warehouse),
                $destinationWarehouses,
            ),
            'balances' => array_map(
                fn (InventoryBalance $balance): array => $this->balanceSnapshot($balance),
                $balances,
            ),
        ];
    }

    public function enrichRequestPayload(array $payload, User $requester): array
    {
        $sourceWarehouse = $this->resolveSourceWarehouse($requester);
        $destinationWarehouse = $this->resolveDestinationWarehouse(
            $sourceWarehouse,
            $payload['destinationWarehouseId'] ?? null,
        );

        $today = new \DateTime('today');
        $items = [];
        foreach ($payload['items'] ?? [] as $itemData) {
            $balance = $this->inventoryBalanceRepository->find(
                (int) ($itemData['sourceBalanceId'] ?? 0),
            );
            $quantity = MathHelper::add((string) ($itemData['quantity'] ?? '0'), '0');
            $this->assertSourceBalance($balance, $sourceWarehouse, $quantity, $today);

            $items[] = [
                'lineId' => (string) $itemData['lineId'],
                'sourceBalanceId' => $balance->getId(),
                'quantity' => $quantity,
                'availableQuantityAtRequest' => $this->availableQuantity($balance),
                ...$this->itemSnapshot($balance),
            ];
        }

        return [
            'sourceWarehouseId' => $sourceWarehouse->getId(),
            'sourceWarehouse' => $this->warehouseSnapshot($sourceWarehouse),
            'destinationWarehouseId' => $destinationWarehouse->getId(),
            'destinationWarehouse' => $this->warehouseSnapshot($destinationWarehouse),
            'items' => $items,
            'reason' => trim((string) ($payload['reason'] ?? '')),
            'note' => trim((string) ($payload['note'] ?? '')),
        ];
    }

    public function execute(StockTransferDTO $dto, User $currentUser): array
    {
        $this->entityManager->beginTransaction();

        try {
            $request = $this->entityManager->getRepository(Request::class)->find(
                $dto->requestId,
                LockMode::PESSIMISTIC_WRITE,
            );
            $this->assertRequestReady($request, $currentUser);

            $payload = $request->getPayload() ?? [];
            $sourceWarehouse = $this->resolveSourceWarehouse($currentUser);
            if ((int) ($payload['sourceWarehouseId'] ?? 0) !== $sourceWarehouse->getId()) {
                throw new \Exception('Bạn không còn thuộc chi nhánh của kho nguồn trong đề xuất');
            }

            $destinationWarehouse = $this->resolveDestinationWarehouse(
                $sourceWarehouse,
                $payload['destinationWarehouseId'] ?? null,
            );
            $this->lockWarehouses($sourceWarehouse, $destinationWarehouse);

            $transfer = new StockTransfer();
            $transfer
                ->setCode(generateSequentialCode($this->entityManager, 'PCK', 'stock_transfer'))
                ->setRequest($request)
                ->setSourceWarehouse($sourceWarehouse)
                ->setDestinationWarehouse($destinationWarehouse)
                ->setWarehouseSnapshot([
                    'source' => $this->warehouseSnapshot($sourceWarehouse),
                    'destination' => $this->warehouseSnapshot($destinationWarehouse),
                ])
                ->setReason((string) ($payload['reason'] ?? ''))
                ->setNote(trim((string) ($payload['note'] ?? '')) ?: null)
                ->setStatus('COMPLETED')
                ->setExecutedAt(new \DateTime())
                ->setExecutedBy($currentUser)
                ->setCreatedBy($currentUser->getId())
                ->setUpdatedBy($currentUser->getId());
            $this->entityManager->persist($transfer);
            $this->entityManager->flush();

            $executionLines = $this->executeLines(
                $transfer,
                $sourceWarehouse,
                $destinationWarehouse,
                $payload['items'] ?? [],
                $currentUser,
            );
            $this->entityManager->flush();

            $transfer->setItemsSnapshot(array_map(
                fn (array $line): array => $this->buildExecutedItemSnapshot($line),
                $executionLines,
            ));

            $request
                ->setTargetRefType('stock_transfer')
                ->setTargetRefId($transfer->getId())
                ->setUpdatedBy($currentUser->getId());
            $this->recordAppliedEvent($request, $transfer, $currentUser);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $transfer->jsonSerialize();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            throw $throwable;
        }
    }

    private function resolveSourceWarehouse(User $user): Warehouse
    {
        $position = $this->userPositionRepository->findActivePrimaryPositionByUser($user);
        $warehouse = $position?->getDepartment()?->getBranch()?->getWarehouse();

        if (!$warehouse instanceof Warehouse || $warehouse->isStatus() !== true) {
            throw new \Exception('Chi nhánh của bạn chưa được cấu hình kho đang hoạt động');
        }

        return $warehouse;
    }

    private function resolveDestinationWarehouse(
        Warehouse $sourceWarehouse,
        mixed $destinationWarehouseId,
    ): Warehouse {
        $destinationWarehouse = $this->warehouseRepository->find((int) $destinationWarehouseId);

        if (
            !$destinationWarehouse instanceof Warehouse
            || $destinationWarehouse->isStatus() !== true
            || $destinationWarehouse->getBranch() === null
        ) {
            throw new \Exception('Kho đích không tồn tại hoặc đã ngừng hoạt động');
        }

        if ($sourceWarehouse->getId() === $destinationWarehouse->getId()) {
            throw new \Exception('Kho đích phải khác kho nguồn');
        }

        return $destinationWarehouse;
    }

    private function assertRequestReady(?Request $request, User $currentUser): void
    {
        if (
            !$request instanceof Request
            || $request->getType() !== RequestConstant::TYPE_STOCK_TRANSFER
            || $request->getStatus() !== RequestConstant::STATUS_APPROVED
        ) {
            throw new \Exception('Đề xuất chuyển kho không hợp lệ hoặc chưa được duyệt');
        }

        if ($request->getRequester()?->getId() !== $currentUser->getId()) {
            throw new \Exception('Bạn không phải là người tạo đề xuất này');
        }

        if (
            $request->getTargetRefId() !== null
            || $this->stockTransferRepository->findOneBy(['request' => $request])
        ) {
            throw new \Exception('Đề xuất này đã được thực hiện chuyển kho');
        }
    }

    private function lockWarehouses(Warehouse $source, Warehouse $destination): void
    {
        $warehouses = [$source, $destination];
        usort(
            $warehouses,
            static fn (Warehouse $a, Warehouse $b): int => $a->getId() <=> $b->getId(),
        );

        foreach ($warehouses as $warehouse) {
            $this->entityManager->lock($warehouse, LockMode::PESSIMISTIC_WRITE);
        }
    }

    private function executeLines(
        StockTransfer $transfer,
        Warehouse $sourceWarehouse,
        Warehouse $destinationWarehouse,
        array $items,
        User $currentUser,
    ): array {
        if ($items === []) {
            throw new \Exception('Đề xuất chuyển kho không có dòng hàng');
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) $a['sourceBalanceId']) <=> ((int) $b['sourceBalanceId']),
        );

        $today = new \DateTime('today');
        $postedAt = new \DateTime();
        $lines = [];
        $processedBalanceIds = [];

        foreach ($items as $itemData) {
            $balanceId = (int) ($itemData['sourceBalanceId'] ?? 0);
            if (isset($processedBalanceIds[$balanceId])) {
                throw new \Exception('Đề xuất có lô hàng bị trùng');
            }
            $processedBalanceIds[$balanceId] = true;

            $sourceBalance = $this->inventoryBalanceRepository->find(
                $balanceId,
                LockMode::PESSIMISTIC_WRITE,
            );
            $quantity = MathHelper::add((string) ($itemData['quantity'] ?? '0'), '0');
            $this->assertSourceBalance($sourceBalance, $sourceWarehouse, $quantity, $today);
            $this->assertPayloadSnapshotMatches($sourceBalance, $itemData);

            $destinationBalance = $this->inventoryBalanceRepository->findOneBy([
                'warehouse' => $destinationWarehouse,
                'merchandise' => $sourceBalance->getMerchandise(),
                'lot' => $sourceBalance->getLot(),
            ]);
            if ($destinationBalance instanceof InventoryBalance) {
                $this->entityManager->lock($destinationBalance, LockMode::PESSIMISTIC_WRITE);
            } else {
                $destinationBalance = new InventoryBalance();
                $destinationBalance
                    ->setWarehouse($destinationWarehouse)
                    ->setMerchandise($sourceBalance->getMerchandise())
                    ->setLot($sourceBalance->getLot());
                $this->entityManager->persist($destinationBalance);
            }

            $sourceBefore = $sourceBalance->getOnHandBaseQuantity();
            $destinationBefore = $destinationBalance->getOnHandBaseQuantity();
            $sourceAfter = MathHelper::sub($sourceBefore, $quantity, self::QUANTITY_SCALE);
            $destinationAfter = MathHelper::add($destinationBefore, $quantity, self::QUANTITY_SCALE);
            $sourceBalance->setOnHandBaseQuantity($sourceAfter);
            $destinationBalance->setOnHandBaseQuantity($destinationAfter);

            $unitCostBase = $this->inventoryBalanceRepository->findUnitCostBaseForLot(
                $sourceBalance->getLot(),
            );
            $sourceMovement = $this->createMovement(
                $transfer,
                $sourceWarehouse,
                $sourceBalance,
                MathHelper::mul($quantity, '-1', self::QUANTITY_SCALE),
                $unitCostBase,
                $postedAt,
                $currentUser,
                InventoryMovementType::TransferOut->value,
                (string) $itemData['lineId'],
                $destinationWarehouse,
            );
            $destinationMovement = $this->createMovement(
                $transfer,
                $destinationWarehouse,
                $destinationBalance,
                $quantity,
                $unitCostBase,
                $postedAt,
                $currentUser,
                InventoryMovementType::TransferIn->value,
                (string) $itemData['lineId'],
                $sourceWarehouse,
            );

            $lines[] = [
                'lineId' => (string) $itemData['lineId'],
                'quantity' => $quantity,
                'unitCostBase' => $unitCostBase,
                'sourceBalance' => $sourceBalance,
                'destinationBalance' => $destinationBalance,
                'sourceBefore' => $sourceBefore,
                'sourceAfter' => $sourceAfter,
                'destinationBefore' => $destinationBefore,
                'destinationAfter' => $destinationAfter,
                'sourceMovement' => $sourceMovement,
                'destinationMovement' => $destinationMovement,
            ];
        }

        return $lines;
    }

    private function assertSourceBalance(
        ?InventoryBalance $balance,
        Warehouse $sourceWarehouse,
        string $quantity,
        \DateTimeInterface $today,
    ): void {
        if (!$balance instanceof InventoryBalance || $balance->getWarehouse()?->getId() !== $sourceWarehouse->getId()) {
            throw new \Exception('Lô hàng không thuộc kho nguồn của chi nhánh');
        }

        $merchandise = $balance->getMerchandise();
        $lot = $balance->getLot();
        if (!$merchandise || $merchandise->getType() !== 'finished_product') {
            throw new \Exception('Chỉ được chuyển kho thành phẩm');
        }
        if (!$merchandise->getBaseUnit()) {
            throw new \Exception('Thành phẩm chưa được cấu hình đơn vị cơ bản');
        }
        if (
            !$lot
            || $lot->getStatus() !== InventoryLotStatus::Available->value
            || $lot->getExpiryDate() < $today
        ) {
            throw new \Exception('Lô hàng không còn khả dụng để chuyển kho');
        }
        if (MathHelper::comp($quantity, '0', self::QUANTITY_SCALE) <= 0) {
            throw new \Exception('Số lượng chuyển phải lớn hơn 0');
        }
        if (MathHelper::comp($this->availableQuantity($balance), $quantity, self::QUANTITY_SCALE) < 0) {
            throw new \Exception(sprintf(
                'Lô %s không đủ tồn khả dụng để chuyển',
                $lot->getInternalCode(),
            ));
        }
    }

    private function assertPayloadSnapshotMatches(InventoryBalance $balance, array $itemData): void
    {
        $snapshotLotId = (int) ($itemData['lot']['id'] ?? 0);
        $snapshotMerchandiseId = (int) ($itemData['merchandise']['id'] ?? 0);

        if (
            $snapshotLotId !== $balance->getLot()?->getId()
            || $snapshotMerchandiseId !== $balance->getMerchandise()?->getId()
        ) {
            throw new \Exception('Thông tin lô hàng đã thay đổi so với đề xuất được duyệt');
        }
    }

    private function createMovement(
        StockTransfer $transfer,
        Warehouse $warehouse,
        InventoryBalance $balance,
        string $quantityDelta,
        string $unitCostBase,
        \DateTimeInterface $postedAt,
        User $currentUser,
        string $movementType,
        string $lineId,
        Warehouse $counterpartWarehouse,
    ): InventoryMovement {
        $movement = new InventoryMovement();
        $movement
            ->setMovementType($movementType)
            ->setSourceType(InventoryMovementSourceType::StockTransfer->value)
            ->setSourceRef([
                'stockTransferId' => $transfer->getId(),
                'requestId' => $transfer->getRequest()?->getId(),
                'lineId' => $lineId,
                'counterpartWarehouseId' => $counterpartWarehouse->getId(),
            ])
            ->setStockTransfer($transfer)
            ->setWarehouse($warehouse)
            ->setMerchandise($balance->getMerchandise())
            ->setLot($balance->getLot())
            ->setQuantityBaseDelta($quantityDelta)
            ->setBaseUnit($balance->getMerchandise()?->getBaseUnit())
            ->setUnitCostBase($unitCostBase)
            ->setNote('Chuyển kho theo phiếu ' . $transfer->getCode())
            ->setPostedAt($postedAt)
            ->setPostedBy($currentUser);
        $this->entityManager->persist($movement);

        return $movement;
    }

    private function buildExecutedItemSnapshot(array $line): array
    {
        /** @var InventoryBalance $sourceBalance */
        $sourceBalance = $line['sourceBalance'];
        /** @var InventoryBalance $destinationBalance */
        $destinationBalance = $line['destinationBalance'];

        return [
            'lineId' => $line['lineId'],
            ...$this->itemSnapshot($sourceBalance),
            'requestedQuantity' => $line['quantity'],
            'transferredQuantity' => $line['quantity'],
            'unitCostBase' => $line['unitCostBase'],
            'source' => [
                'warehouseId' => $sourceBalance->getWarehouse()?->getId(),
                'balanceId' => $sourceBalance->getId(),
                'quantityBefore' => $line['sourceBefore'],
                'quantityAfter' => $line['sourceAfter'],
                'movementId' => $line['sourceMovement']->getId(),
            ],
            'destination' => [
                'warehouseId' => $destinationBalance->getWarehouse()?->getId(),
                'balanceId' => $destinationBalance->getId(),
                'quantityBefore' => $line['destinationBefore'],
                'quantityAfter' => $line['destinationAfter'],
                'movementId' => $line['destinationMovement']->getId(),
            ],
        ];
    }

    private function recordAppliedEvent(
        Request $request,
        StockTransfer $transfer,
        User $currentUser,
    ): void {
        $event = new RequestEvent();
        $event
            ->setRequest($request)
            ->setEventType(RequestConstant::EVENT_EFFECT_APPLIED)
            ->setActor($currentUser)
            ->setActorType('user')
            ->setStepNo($request->getCurrentStepNo())
            ->setRevisionNo($request->getRevisionNo())
            ->setFromStatus(RequestConstant::STATUS_APPROVED)
            ->setToStatus(RequestConstant::STATUS_APPROVED)
            ->setComment('Đã thực hiện chuyển kho theo phiếu ' . $transfer->getCode())
            ->setPayloadSnapshot($request->getPayload())
            ->setMeta([
                'targetRefType' => 'stock_transfer',
                'targetRefId' => $transfer->getId(),
            ])
            ->setCreatedAt(new \DateTime());
        $request->addEvent($event);
        $this->entityManager->persist($event);
    }

    private function availableQuantity(InventoryBalance $balance): string
    {
        return MathHelper::sub(
            $balance->getOnHandBaseQuantity(),
            MathHelper::add(
                $balance->getReservedBaseQuantity(),
                $balance->getBlockedBaseQuantity(),
            ),
        );
    }

    private function balanceSnapshot(InventoryBalance $balance): array
    {
        return [
            'id' => $balance->getId(),
            'availableQuantity' => $this->availableQuantity($balance),
            ...$this->itemSnapshot($balance),
        ];
    }

    private function itemSnapshot(InventoryBalance $balance): array
    {
        $merchandise = $balance->getMerchandise();
        $lot = $balance->getLot();
        $unit = $merchandise?->getBaseUnit();

        return [
            'merchandise' => [
                'id' => $merchandise?->getId(),
                'code' => $merchandise?->getCode(),
                'name' => $merchandise?->getName(),
                'type' => $merchandise?->getType(),
            ],
            'lot' => [
                'id' => $lot?->getId(),
                'internalCode' => $lot?->getInternalCode(),
                'supplierLotCode' => $lot?->getSupplierLotCode(),
                'productionLotCode' => $lot?->getProductionLotCode(),
                'manufactureDate' => $lot?->getManufactureDate()?->format('Y-m-d'),
                'expiryDate' => $lot?->getExpiryDate()?->format('Y-m-d'),
                'status' => $lot?->getStatus(),
            ],
            'baseUnit' => [
                'id' => $unit?->getId(),
                'code' => $unit?->getCode(),
                'name' => $unit?->getName(),
                'symbol' => $unit?->getSymbol(),
            ],
        ];
    }

    private function warehouseSnapshot(Warehouse $warehouse): array
    {
        $branch = $warehouse->getBranch();

        return [
            'id' => $warehouse->getId(),
            'code' => $warehouse->getCode(),
            'name' => $warehouse->getName(),
            'branch' => $branch ? [
                'id' => $branch->getId(),
                'code' => $branch->getCode(),
                'name' => $branch->getName(),
            ] : null,
        ];
    }
}
