<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementSourceType;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
use App\Class\ProductionEventType;
use App\Class\ProductionOrderStatus;
use App\DTO\ProductionItemInspectDTO;
use App\DTO\ProductionItemInspectLotDTO;
use App\Entity\InventoryBalance;
use App\Entity\InventoryLot;
use App\Entity\InventoryMovement;
use App\Entity\ProductionEvent;
use App\Entity\ProductionGoodsReceipt;
use App\Entity\ProductionItemInspection;
use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderItem;
use App\Entity\User;
use App\Repository\ProductionItemInspectionRepository;
use App\Repository\ProductionOrderItemRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class ProductionInspectionService
{
    private const QUANTITY_SCALE = 6;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductionOrderItemRepository $productionOrderItemRepository,
        private readonly ProductionItemInspectionRepository $inspectionRepository,
        private readonly ProductionOrderService $productionOrderService,
    ) {
    }

    public function inspect(
        int $itemId,
        ProductionItemInspectDTO $dto,
        User $currentUser,
    ): array {
        $this->entityManager->beginTransaction();

        try {
            $item = $this->productionOrderItemRepository->find($itemId, LockMode::PESSIMISTIC_WRITE);
            if (!$item) {
                throw new \Exception('Thành phẩm trong lệnh sản xuất không tồn tại');
            }
            if (!in_array($item->getStatus(), [
                ProductionOrderStatus::Inspecting->value,
                ProductionOrderStatus::WaitingSupplement->value,
            ], true)) {
                throw new \Exception('Thành phẩm phải ở trạng thái đang kiểm hàng hoặc chờ bổ sung');
            }

            $existing = $this->inspectionRepository->findOneBy([
                'productionOrderItem' => $item,
                'clientRequestUuid' => $dto->clientRequestUuid,
            ]);
            if ($existing) {
                $this->entityManager->commit();

                return $existing->jsonSerialize();
            }

            $order = $item->getProductionOrder();
            if (!$order || !$order->getFinishedGoodsWarehouse() || !$item->getFinishedProduct() || !$item->getBaseUnit()) {
                throw new \Exception('Dữ liệu lệnh sản xuất không hợp lệ để nhập kho');
            }

            $postedAt = new \DateTime();
            $metricsBefore = $this->metrics($item);
            [$lots, $acceptedCurrent] = $this->normalizeLots($dto->lots);

            $inspection = new ProductionItemInspection();
            $inspection->setCode(generateSequentialCode($this->entityManager, 'KHSX', 'production_item_inspection'));
            $inspection->setProductionOrderItem($item);
            $inspection->setSequenceNo($this->inspectionRepository->count(['productionOrderItem' => $item]) + 1);
            $inspection->setClientRequestUuid((string) $dto->clientRequestUuid);
            // Cần flush để lấy id phục vụ sourceRef của lot; kết quả cuối cùng sẽ
            // được ghi đè sau khi đã post kho và phân bổ shortage trong transaction này.
            $inspection->setResolution('PENDING');
            $inspection->setRemainingBaseQuantityBefore($metricsBefore['fullRemaining']);
            $inspection->setRemainingBaseQuantityAfter($metricsBefore['fullRemaining']);
            $inspection->setLots($lots);
            $inspection->setPostedAt($postedAt);
            $this->entityManager->persist($inspection);

            $goodsReceipt = $this->createGoodsReceipt($inspection, $order, $acceptedCurrent, $postedAt);
            $this->entityManager->persist($goodsReceipt);
            // Cần id inspection/receipt để sourceRef của lot bất biến và truy vết được.
            $this->entityManager->flush();

            $this->postAcceptedLotsToInventory($item, $inspection, $goodsReceipt, $lots, $postedAt, $currentUser);
            $item->setAcceptedBaseQuantity(MathHelper::add($item->getAcceptedBaseQuantity(), $acceptedCurrent, self::QUANTITY_SCALE));

            $allocations = $this->allocateSurplusToSelectedShortages($item, $inspection, $postedAt, $currentUser);
            $metricsAfter = $this->metrics($item);
            $resolution = $this->resolveCurrentItem($item, $dto->shortageResolution, $metricsAfter, $postedAt);
            $inspection->setResolution($resolution);
            $inspection->setRemainingBaseQuantityAfter($metricsAfter['fullRemaining']);
            $inspection->setResolutionData([
                'minimumAcceptableBaseQuantity' => $metricsAfter['minimum'],
                'effectiveAcceptedBaseQuantity' => $metricsAfter['effective'],
                'shortageResolution' => $dto->shortageResolution,
                'supplementAllocations' => $allocations,
            ]);

            $this->recordEvent($order, $item, ProductionEventType::InspectionPosted->value, $currentUser, 'Đã kiểm hàng thành phẩm', $inspection, $goodsReceipt);
            if (MathHelper::comp($acceptedCurrent, '0') > 0) {
                $this->recordEvent(
                    $order,
                    $item,
                    ProductionEventType::InventoryPosted->value,
                    $currentUser,
                    'Đã nhập kho thành phẩm đạt kiểm',
                    $inspection,
                    $goodsReceipt,
                    meta: ['quantityBase' => $acceptedCurrent],
                );
            }
            $this->recordResolutionEvent($order, $item, $resolution, $currentUser, $inspection);

            $fromOrderStatus = $order->getStatus();
            $this->productionOrderService->syncProductionOrderStatusFromItems($order);
            if ($fromOrderStatus !== $order->getStatus()) {
                $this->recordEvent($order, null, ProductionEventType::StatusChanged->value, $currentUser, sprintf('Tổng hợp trạng thái lệnh sản xuất từ %s sang %s', $fromOrderStatus, $order->getStatus()), null, null, $fromOrderStatus, $order->getStatus());
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $inspection->jsonSerialize();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: string} */
    private function normalizeLots(array $lotDtos): array
    {
        $lots = [];
        $acceptedTotal = '0.000000';
        $lineUuids = [];

        foreach ($lotDtos as $lotDto) {
            if (!$lotDto instanceof ProductionItemInspectLotDTO || isset($lineUuids[$lotDto->clientLineUuid])) {
                throw new \Exception('Dòng lô kiểm hàng không hợp lệ hoặc bị trùng');
            }
            $lineUuids[$lotDto->clientLineUuid] = true;
            $received = MathHelper::add((string) $lotDto->receivedQuantity, '0', self::QUANTITY_SCALE);
            $accepted = MathHelper::add((string) $lotDto->acceptedQuantity, '0', self::QUANTITY_SCALE);
            if (MathHelper::comp($received, '0') <= 0 || MathHelper::comp($accepted, $received) > 0) {
                throw new \Exception('Số lượng lô kiểm hàng không hợp lệ');
            }
            $manufactureDate = new \DateTime((string) $lotDto->manufactureDate);
            $expiryDate = new \DateTime((string) $lotDto->expiryDate);
            if ($expiryDate <= $manufactureDate) {
                throw new \Exception('Hạn sử dụng phải sau ngày sản xuất');
            }
            if (MathHelper::comp(MathHelper::sub($received, $accepted, self::QUANTITY_SCALE), '0') > 0 && trim((string) $lotDto->rejectionReason) === '') {
                throw new \Exception('Lý do từ chối là bắt buộc khi có hàng không đạt');
            }
            $lots[] = [
                'clientLineUuid' => $lotDto->clientLineUuid,
                'receivedQuantity' => $received,
                'acceptedQuantity' => $accepted,
                'rejectedQuantity' => MathHelper::sub($received, $accepted, self::QUANTITY_SCALE),
                'manufactureDate' => $manufactureDate->format('Y-m-d'),
                'expiryDate' => $expiryDate->format('Y-m-d'),
                'productionLotCode' => $lotDto->productionLotCode,
                'rejectionReason' => $lotDto->rejectionReason,
                'note' => $lotDto->note,
            ];
            $acceptedTotal = MathHelper::add($acceptedTotal, $accepted, self::QUANTITY_SCALE);
        }

        return [$lots, $acceptedTotal];
    }

    private function createGoodsReceipt(ProductionItemInspection $inspection, ProductionOrder $order, string $acceptedTotal, \DateTimeInterface $postedAt): ProductionGoodsReceipt
    {
        $warehouse = $order->getFinishedGoodsWarehouse();
        $receipt = new ProductionGoodsReceipt();
        $receipt->setCode(generateSequentialCode($this->entityManager, 'PNKSX', 'production_goods_receipt'));
        $receipt->setProductionItemInspection($inspection);
        $receipt->setWarehouse($warehouse);
        $receipt->setWarehouseSnapshot($order->getWarehouseSnapshot()['finishedGoodsWarehouse'] ?? [
            'id' => $warehouse?->getId(), 'code' => $warehouse?->getCode(), 'name' => $warehouse?->getName(),
        ]);
        $receipt->setTotalAcceptedBaseQuantity($acceptedTotal);
        $receipt->setPostedAt($postedAt);

        return $receipt;
    }

    private function postAcceptedLotsToInventory(ProductionOrderItem $item, ProductionItemInspection $inspection, ProductionGoodsReceipt $goodsReceipt, array $lots, \DateTimeInterface $postedAt, User $actor): void
    {
        $order = $item->getProductionOrder();
        $warehouse = $order?->getFinishedGoodsWarehouse();
        $product = $item->getFinishedProduct();
        $baseUnit = $item->getBaseUnit();
        if (!$warehouse || !$product || !$baseUnit) {
            throw new \Exception('Dữ liệu thành phẩm hoặc kho nhập không hợp lệ');
        }

        foreach ($lots as $lotData) {
            if (MathHelper::comp($lotData['acceptedQuantity'], '0') <= 0) {
                continue;
            }
            $lot = new InventoryLot();
            $lot->setInternalCode(sprintf('LOT-%s-%s', $goodsReceipt->getCode(), strtoupper(str_replace('-', '', (string) $lotData['clientLineUuid']))));
            $lot->setOriginType('PRODUCTION');
            $lot->setMerchandise($product);
            $lot->setProvider(null);
            $lot->setProductionLotCode($lotData['productionLotCode']);
            $lot->setSourceRef([
                'productionOrderId' => $order?->getId(),
                'productionOrderItemId' => $item->getId(),
                'inspectionId' => $inspection->getId(),
                'goodsReceiptId' => $goodsReceipt->getId(),
            ]);
            $lot->setManufactureDate(new \DateTime($lotData['manufactureDate']));
            $lot->setExpiryDate(new \DateTime($lotData['expiryDate']));
            $lot->setReceivedAt($postedAt);
            $lot->setStatus(InventoryLotStatus::Available->value);
            $lot->setNote($lotData['note']);
            $this->entityManager->persist($lot);

            $movement = new InventoryMovement();
            $movement->setMovementType(InventoryMovementType::StockIn->value);
            $movement->setSourceType(InventoryMovementSourceType::ProductionGoodsReceipt->value);
            $movement->setProductionOrderItem($item);
            $movement->setSourceRef($lot->getSourceRef());
            $movement->setWarehouse($warehouse);
            $movement->setMerchandise($product);
            $movement->setLot($lot);
            $movement->setQuantityBaseDelta($lotData['acceptedQuantity']);
            $movement->setBaseUnit($baseUnit);
            $movement->setUnitCostBase(null);
            $movement->setNote('Nhập kho từ phiếu ' . $goodsReceipt->getCode());
            $movement->setPostedAt($postedAt);
            $movement->setPostedBy($actor);
            $this->entityManager->persist($movement);

            $balance = new InventoryBalance();
            $balance->setWarehouse($warehouse);
            $balance->setMerchandise($product);
            $balance->setLot($lot);
            $balance->setOnHandBaseQuantity($lotData['acceptedQuantity']);
            $this->entityManager->persist($balance);
        }
    }

    /** @return array<int, array{targetProductionOrderItemId: int, quantityBase: string}> */
    private function allocateSurplusToSelectedShortages(ProductionOrderItem $source, ProductionItemInspection $inspection, \DateTimeInterface $postedAt, User $actor): array
    {
        $supplementData = $source->getSupplementData() ?? [];
        $plans = $supplementData['plans'] ?? [];
        if (!is_array($plans) || $plans === []) {
            return [];
        }
        $available = MathHelper::sub($source->getAcceptedBaseQuantity(), $source->getPlannedBaseQuantity() ?? '0', self::QUANTITY_SCALE);
        $available = MathHelper::sub($available, $this->alreadyAllocatedFromSource($source), self::QUANTITY_SCALE);
        if (MathHelper::comp($available, '0') <= 0) {
            return [];
        }

        $allocations = [];
        foreach ($plans as $plan) {
            if (MathHelper::comp($available, '0') <= 0) {
                break;
            }
            $targetId = (int) ($plan['productionOrderItemId'] ?? 0);
            $mode = $plan['mode'] ?? 'MINIMUM';
            $target = $targetId > 0 ? $this->productionOrderItemRepository->find($targetId, LockMode::PESSIMISTIC_WRITE) : null;
            if (!$target || $target->getStatus() !== ProductionOrderStatus::WaitingSupplement->value || $target->getFinishedProduct()?->getId() !== $source->getFinishedProduct()?->getId()) {
                continue;
            }
            $needed = $this->remainingForMode($target, $mode === 'FULL' ? 'FULL' : 'MINIMUM');
            if (MathHelper::comp($needed, '0') <= 0) {
                continue;
            }
            $quantity = MathHelper::comp($available, $needed) < 0 ? $available : $needed;
            $shortageData = $target->getShortageData() ?? [];
            $shortageData['externalFulfilledBaseQuantity'] = MathHelper::add((string) ($shortageData['externalFulfilledBaseQuantity'] ?? '0'), $quantity, self::QUANTITY_SCALE);
            $shortageData['supplementHistory'] = array_merge($shortageData['supplementHistory'] ?? [], [[
                'sourceProductionOrderId' => $source->getProductionOrder()?->getId(),
                'sourceProductionOrderItemId' => $source->getId(),
                'sourceInspectionId' => $inspection->getId(),
                'quantityBase' => $quantity,
                'allocatedAt' => $postedAt->format('Y-m-d H:i:s'),
            ]]);
            $target->setShortageData($shortageData);
            $targetMetrics = $this->metrics($target);
            if (MathHelper::comp($targetMetrics['effective'], $targetMetrics['minimum']) >= 0) {
                $target->setStatus(ProductionOrderStatus::Completed->value);
                $target->setCompletedAt($postedAt);
                $target->setClosedShortBaseQuantity($targetMetrics['fullRemaining']);
                $this->recordEvent($target->getProductionOrder(), $target, ProductionEventType::ShortageResolved->value, $actor, 'Thiếu hàng đã được bù từ lệnh sản xuất khác', $inspection, null, ProductionOrderStatus::WaitingSupplement->value, ProductionOrderStatus::Completed->value, ['quantityBase' => $quantity]);
            } else {
                $this->recordEvent($target->getProductionOrder(), $target, ProductionEventType::ShortageAllocated->value, $actor, 'Đã bù một phần thiếu hàng từ lệnh sản xuất khác', $inspection, null, null, null, ['quantityBase' => $quantity]);
            }
            $this->productionOrderService->syncProductionOrderStatusFromItems($target->getProductionOrder());
            $allocations[] = ['targetProductionOrderItemId' => $target->getId(), 'quantityBase' => $quantity];
            $available = MathHelper::sub($available, $quantity, self::QUANTITY_SCALE);
        }

        return $allocations;
    }

    private function alreadyAllocatedFromSource(ProductionOrderItem $item): string
    {
        $total = '0.000000';
        foreach ($this->inspectionRepository->findBy(['productionOrderItem' => $item]) as $inspection) {
            $resolutionData = $inspection->getResolutionData() ?? [];
            foreach (($resolutionData['supplementAllocations'] ?? []) as $allocation) {
                $total = MathHelper::add($total, (string) ($allocation['quantityBase'] ?? '0'), self::QUANTITY_SCALE);
            }
        }

        return $total;
    }

    private function resolveCurrentItem(ProductionOrderItem $item, ?string $shortageResolution, array $metrics, \DateTimeInterface $postedAt): string
    {
        if (MathHelper::comp($metrics['effective'], $metrics['planned']) >= 0) {
            $resolution = 'FULL';
        } elseif (MathHelper::comp($metrics['effective'], $metrics['minimum']) >= 0) {
            $resolution = 'WITHIN_TOLERANCE';
            $item->setClosedShortBaseQuantity($metrics['fullRemaining']);
        } elseif ($shortageResolution === 'SUPPLEMENT_LATER') {
            $resolution = 'SUPPLEMENT_LATER';
            $item->setStatus(ProductionOrderStatus::WaitingSupplement->value);
            $shortageData = $item->getShortageData() ?? [];
            $shortageData['resolution'] = 'SUPPLEMENT_LATER';
            $shortageData['minimumAcceptableBaseQuantity'] = $metrics['minimum'];
            $shortageData['externalFulfilledBaseQuantity'] = (string) ($shortageData['externalFulfilledBaseQuantity'] ?? '0.000000');
            $shortageData['supplementHistory'] = $shortageData['supplementHistory'] ?? [];
            $item->setShortageData($shortageData);

            return $resolution;
        } elseif ($shortageResolution === 'ACCEPT_SHORTAGE') {
            $resolution = 'ACCEPT_SHORTAGE';
            $item->setClosedShortBaseQuantity($metrics['fullRemaining']);
            $shortageData = $item->getShortageData() ?? [];
            $shortageData['resolution'] = 'ACCEPT_SHORTAGE';
            $shortageData['minimumAcceptableBaseQuantity'] = $metrics['minimum'];
            $shortageData['acceptedAt'] = $postedAt->format('Y-m-d H:i:s');
            $item->setShortageData($shortageData);
        } else {
            throw new \Exception('Vui lòng chọn cách xử lý phần thiếu hàng');
        }

        $item->setStatus(ProductionOrderStatus::Completed->value);
        if ($item->getCompletedAt() === null) {
            $item->setCompletedAt($postedAt);
        }

        return $resolution;
    }

    /** @return array{planned: string, minimum: string, effective: string, fullRemaining: string} */
    private function metrics(ProductionOrderItem $item): array
    {
        $planned = $item->getPlannedBaseQuantity() ?? '0.000000';
        $minimum = MathHelper::sub($planned, MathHelper::mul($planned, MathHelper::div($item->getExpectedWastePercent(), '100', 8), self::QUANTITY_SCALE), self::QUANTITY_SCALE);
        $shortageData = $item->getShortageData() ?? [];
        $external = (string) ($shortageData['externalFulfilledBaseQuantity'] ?? '0.000000');
        $effective = MathHelper::add($item->getAcceptedBaseQuantity(), $external, self::QUANTITY_SCALE);
        $fullRemaining = MathHelper::sub($planned, $effective, self::QUANTITY_SCALE);

        return [
            'planned' => $planned,
            'minimum' => $minimum,
            'effective' => $effective,
            'fullRemaining' => MathHelper::comp($fullRemaining, '0') > 0 ? $fullRemaining : '0.000000',
        ];
    }

    private function remainingForMode(ProductionOrderItem $item, string $mode): string
    {
        $metrics = $this->metrics($item);
        $remaining = MathHelper::sub($mode === 'FULL' ? $metrics['planned'] : $metrics['minimum'], $metrics['effective'], self::QUANTITY_SCALE);

        return MathHelper::comp($remaining, '0') > 0 ? $remaining : '0.000000';
    }

    private function recordResolutionEvent(ProductionOrder $order, ProductionOrderItem $item, string $resolution, User $actor, ProductionItemInspection $inspection): void
    {
        $type = match ($resolution) {
            'SUPPLEMENT_LATER' => ProductionEventType::WaitingSupplement->value,
            'ACCEPT_SHORTAGE' => ProductionEventType::ShortageAccepted->value,
            default => null,
        };
        if ($type) {
            $this->recordEvent($order, $item, $type, $actor, $resolution === 'SUPPLEMENT_LATER' ? 'Thành phẩm đang chờ bổ sung' : 'Đã chấp nhận đóng thiếu', $inspection, null, null, $item->getStatus());
        }
    }

    private function recordEvent(ProductionOrder $order, ?ProductionOrderItem $item, string $eventType, User $actor, string $message, ?ProductionItemInspection $inspection = null, ?ProductionGoodsReceipt $goodsReceipt = null, ?string $fromStatus = null, ?string $toStatus = null, ?array $meta = null): void
    {
        $event = new ProductionEvent();
        $event->setProductionOrder($order);
        $event->setProductionOrderItem($item);
        $event->setProductionItemInspection($inspection);
        $event->setProductionGoodsReceipt($goodsReceipt);
        $event->setEventType($eventType);
        $event->setActor($actor);
        $event->setMessage($message);
        $event->setFromStatus($fromStatus);
        $event->setToStatus($toStatus);
        $event->setMeta($meta);
        $this->entityManager->persist($event);
    }
}
