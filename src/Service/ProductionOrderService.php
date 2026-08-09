<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\MathHelper;
use App\Class\ProductionEventType;
use App\Class\ProductionOrderStatus;
use App\Class\Request\RequestConstant;
use App\Class\Warehouse\Warehoue as WarehouseHelper;
use App\DTO\ProductionOrderDTO;
use App\Entity\Merchandise;
use App\Entity\MerchandiseRecipe;
use App\Entity\MerchandiseRecipeItem;
use App\Entity\ProductionEvent;
use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderItem;
use App\Entity\ProductionOrderMaterial;
use App\Entity\Request;
use App\Entity\Unit;
use App\Entity\User;
use App\Repository\MerchandiseRecipeRepository;
use App\Repository\MerchandiseRepository;
use App\Repository\MerchandiseUnitRepository;
use App\Repository\ProductionOrderRepository;
use App\Repository\ProductionOrderItemRepository;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductionOrderService
{
    public function __construct(
        private readonly ProductionOrderRepository $productionOrderRepository,
        private readonly ProductionOrderItemRepository $productionOrderItemRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseHelper $warehouseHelper,
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly UnitRepository $unitRepository,
        private readonly MerchandiseRecipeRepository $merchandiseRecipeRepository,
        private readonly MerchandiseUnitRepository $merchandiseUnitRepository,
        private readonly ProductionMaterialIssueService $materialIssueService,
    ) {
    }

    public function findAll(array $params): array
    {
        $qb = $this->productionOrderRepository->createQueryBuilder('e');

        $relationFields = [
            'requestId' => [
                'joinField' => 'e.request',
                'alias' => 'request_filter',
                'targetField' => 'code',
            ],
            'materialWarehouseId' => [
                'joinField' => 'e.materialWarehouse',
                'alias' => 'material_warehouse_filter',
                'targetField' => 'id',
            ],
            'finishedGoodsWarehouseId' => [
                'joinField' => 'e.finishedGoodsWarehouse',
                'alias' => 'finished_goods_warehouse_filter',
                'targetField' => 'id',
            ],
        ];

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'e',
            $relationFields,
        );

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(ProductionOrder $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->productionOrderRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (ProductionOrder $item) {
            return [
                'label' => $item->getTitle(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->productionOrderRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $this->enrichSupplementPlans($item->jsonSerialize());
    }

    public function updateItemStatus(
        int $itemId,
        string $status,
        User $currentUser,
    ): array {
        $this->entityManager->beginTransaction();

        try {
            $item = $this->productionOrderItemRepository->find($itemId);

            if (!$item) {
                throw new \Exception('Thành phẩm trong lệnh sản xuất không tồn tại');
            }

            $productionOrder = $this->productionOrderRepository->find(
                $item->getProductionOrder()?->getId(),
                \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
            );

            if (!$productionOrder) {
                throw new \Exception('Thành phẩm chưa thuộc lệnh sản xuất');
            }

            $item = $this->productionOrderItemRepository->find(
                $itemId,
                \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE,
            );

            $fromStatus = $item->getStatus();
            $this->assertNextProductionOrderItemStatus($fromStatus, $status);

            // Xuất kho nguyên liệu trước khi chuyển trạng thái MATERIAL_ISSUED.
            if ($status === ProductionOrderStatus::MaterialIssued->value) {
                $this->materialIssueService->issueMaterialsForItem($item, $currentUser);
            }

            $item->setStatus($status);

            if ($status === ProductionOrderStatus::Started->value && $item->getStartedAt() === null) {
                $item->setStartedAt(new \DateTime());
            }
            if ($status === ProductionOrderStatus::Completed->value && $item->getCompletedAt() === null) {
                $item->setCompletedAt(new \DateTime());
            }

            $this->recordProductionEvent(
                productionOrder: $productionOrder,
                productionOrderItem: $item,
                eventType: ProductionEventType::StatusChanged->value,
                actor: $currentUser,
                message: sprintf(
                    'Cập nhật trạng thái thành phẩm từ %s sang %s',
                    $fromStatus,
                    $status,
                ),
                fromStatus: $fromStatus,
                toStatus: $status,
            );

            $fromOrderStatus = $productionOrder->getStatus();
            $this->syncProductionOrderStatusFromItems($productionOrder);

            if ($fromOrderStatus !== $productionOrder->getStatus()) {
                $this->recordProductionEvent(
                    productionOrder: $productionOrder,
                    eventType: ProductionEventType::StatusChanged->value,
                    actor: $currentUser,
                    message: sprintf(
                        'Tổng hợp trạng thái lệnh sản xuất từ %s sang %s',
                        $fromOrderStatus,
                        $productionOrder->getStatus(),
                    ),
                    fromStatus: $fromOrderStatus,
                    toStatus: $productionOrder->getStatus(),
                );
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $item->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    public function create(ProductionOrderDTO $dto, User $currentUser): array
    {
        $this->entityManager->beginTransaction();
        try {
            $request = $this->entityManager
                ->getRepository(Request::class)
                ->find($dto->requestId, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);

            $this->assertRequestReadyForProduction($request, $currentUser);

            $productionOrder = $this->buildProductionOrder($request);
            $this->addItemsAndMaterialsFromPayload(
                $productionOrder,
                $request->getPayload() ?? [],
            );

            $this->recordProductionEvent(
                productionOrder: $productionOrder,
                eventType: ProductionEventType::Created->value,
                actor: $currentUser,
                message: 'Tạo lệnh sản xuất từ đề xuất #' . $request->getCode(),
                toStatus: ProductionOrderStatus::Created->value,
            );

            $request->setTargetRefType('production_order');
            $this->entityManager->flush();

            $request->setTargetRefId($productionOrder->getId());
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $productionOrder->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    public function update(int $id, ProductionOrderDTO $dto): array
    {
        $item = $this->productionOrderRepository->find($id);

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
        $item = $this->productionOrderRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function assertRequestReadyForProduction(
        ?Request $request,
        User $currentUser,
    ): void {
        if (
            !$request ||
            $request->getType() !== RequestConstant::TYPE_PRODUCTION ||
            $request->getStatus() !== RequestConstant::STATUS_APPROVED
        ) {
            throw new \Exception("Đề xuất không hợp lệ hoặc chưa được duyệt");
        }
        if ($request->getRequester()?->getId() !== $currentUser->getId()) {
            throw new \Exception("Bạn không phải là người tạo đề xuất này");
        }
        if ($this->productionOrderRepository->findOneBy(["request" => $request])) {
            throw new \Exception("Đề xuất này đã được tạo lệnh sản xuất");
        }
    }

    /**
     * Tạo entity lệnh sản xuất từ đề xuất đã duyệt.
     */
    private function buildProductionOrder(Request $request): ProductionOrder
    {
        $materialWarehouse = $this->warehouseHelper->getProductionMaterialWarehouse();
        $finishedGoodsWarehouse = $this->warehouseHelper->getProductionFinishedGoodsWarehouse();
        $orderCode = generateSequentialCode(
            $this->entityManager,
            "LSX",
            "production_order",
        );

        $productionOrder = new ProductionOrder();
        $productionOrder->setCode($orderCode);
        $productionOrder->setRequest($request);
        $productionOrder->setMaterialWarehouse($materialWarehouse);
        $productionOrder->setFinishedGoodsWarehouse($finishedGoodsWarehouse);
        $productionOrder->setStatus(ProductionOrderStatus::Created->value);
        $productionOrder->setTitle(
            "Lệnh sản xuất {$orderCode} ngày " . date("d/m/Y"),
        );
        $productionOrder->setWarehouseSnapshot([
            "materialWarehouse" => $this->warehouseSnapshot($materialWarehouse),
            "finishedGoodsWarehouse" => $this->warehouseSnapshot($finishedGoodsWarehouse),
        ]);
        $this->entityManager->persist($productionOrder);

        return $productionOrder;
    }

    private function warehouseSnapshot(\App\Entity\Warehouse $warehouse): array
    {
        return [
            "id" => $warehouse->getId(),
            "code" => $warehouse->getCode(),
            "name" => $warehouse->getName(),
            "branch" => $warehouse->getBranch()
                ? [
                    "id" => $warehouse->getBranch()->getId(),
                    "name" => $warehouse->getBranch()->getName(),
                    "code" => $warehouse->getBranch()->getCode(),
                ]
                : null,
        ];
    }

    /**
     * Sinh card thành phẩm + nguyên liệu từ payload đề xuất.
     */
    private function addItemsAndMaterialsFromPayload(
        ProductionOrder $productionOrder,
        array $payload,
    ): void {
        $sortOrder = 0;

        foreach ($payload["items"] ?? [] as $itemData) {
            $this->addOrderItemFromPayload(
                $productionOrder,
                $itemData,
                $sortOrder++,
            );
        }
    }

    private function addOrderItemFromPayload(
        ProductionOrder $productionOrder,
        array $itemData,
        int $sortOrder,
    ): void {
        $finishedProduct = $this->merchandiseRepository->find(
            $itemData["finishedProductId"] ?? 0,
        );
        if (!$finishedProduct) {
            throw new \Exception("Thành phẩm không tồn tại");
        }

        $recipe = $this->merchandiseRecipeRepository->findOneBy([
            "finishedProduct" => $finishedProduct,
        ]);
        if (!$recipe) {
            throw new \Exception(
                sprintf(
                    "Thành phẩm '%s' chưa có công thức sản xuất",
                    $finishedProduct->getName(),
                ),
            );
        }

        $plannedUnit = $this->unitRepository->find($itemData["outputUnitId"] ?? 0);
        if (!$plannedUnit) {
            throw new \Exception("Đơn vị tính thành phẩm không tồn tại");
        }

        $baseUnit = $finishedProduct->getBaseUnit();
        if (!$baseUnit) {
            throw new \Exception(
                sprintf(
                    "Thành phẩm '%s' chưa cấu hình đơn vị cơ bản",
                    $finishedProduct->getName(),
                ),
            );
        }

        $plannedQuantity = (string) ($itemData["quantity"] ?? "0");
        $plannedFactorToBase = $this->resolveFactorToBase(
            $finishedProduct,
            $plannedUnit,
            $recipe->getOutputFactorToBaseSnapshot(),
            "thành phẩm",
        );

        $orderItem = new ProductionOrderItem();
        $orderItem->setProductionOrder($productionOrder);
        $orderItem->setSourceRequestLineId((string) ($itemData["lineId"] ?? ''));
        $orderItem->setFinishedProduct($finishedProduct);
        $orderItem->setProductSnapshot([
            "id" => $finishedProduct->getId(),
            "code" => $finishedProduct->getCode(),
            "name" => $finishedProduct->getName(),
            "baseUnitId" => $baseUnit->getId(),
            "baseUnitName" => $baseUnit->getName(),
        ]);
        $orderItem->setRecipe($recipe);
        $orderItem->setRecipeSnapshot([
            "version" => $recipe->getVersion(),
            "outputQuantity" => $recipe->getOutputQuantity(),
            "outputUnitId" => $recipe->getOutputUnit()?->getId(),
            "outputUnitName" => $recipe->getOutputUnit()?->getName(),
            "outputFactorToBaseSnapshot" => $recipe->getOutputFactorToBaseSnapshot(),
            "outputBaseQuantitySnapshot" => $recipe->getOutputBaseQuantitySnapshot(),
        ]);
        $orderItem->setPlannedQuantity($plannedQuantity);
        $orderItem->setPlannedUnit($plannedUnit);
        $orderItem->setPlannedFactorToBase($plannedFactorToBase);
        $plannedBaseQuantity = MathHelper::mul(
            $plannedQuantity,
            $plannedFactorToBase,
            6,
        );
        $orderItem->setPlannedBaseQuantity($plannedBaseQuantity);
        $orderItem->setBaseUnit($baseUnit);
        $wastePercent = $itemData["expectedWastePercent"] ?? null;
        $orderItem->setExpectedWastePercent(
            $wastePercent !== null ? (string) $wastePercent : '0.00',
        );
        $orderItem->setSortOrder($sortOrder);
        $this->entityManager->persist($orderItem);

        $supplementData = $this->buildSupplementData(
            $finishedProduct,
            $itemData['supplementSelections'] ?? [],
            $plannedBaseQuantity,
        );
        $productionTargetBaseQuantity = $supplementData['productionTargetBaseQuantity'];
        if ($supplementData['plans'] !== []) {
            $orderItem->setSupplementData($supplementData);
        }

        $this->addMaterialsFromRecipe(
            $orderItem,
            $recipe,
            $productionTargetBaseQuantity,
        );
    }

    /**
     * Sinh nguyên liệu kế hoạch theo công thức hiện tại của thành phẩm.
     */
    private function addMaterialsFromRecipe(
        ProductionOrderItem $orderItem,
        MerchandiseRecipe $recipe,
        string $plannedOutputBaseQuantity,
    ): void {
        $scale = $this->computeRecipeScale(
            $recipe,
            $plannedOutputBaseQuantity,
        );

        foreach ($recipe->getItems() as $recipeItem) {
            $ingredient = $recipeItem->getIngredient();
            $baseUnit = $ingredient?->getBaseUnit();
            if (!$ingredient || !$baseUnit) {
                throw new \Exception(
                    sprintf(
                        "Nguyên liệu trong công thức '%s' chưa cấu hình đơn vị cơ bản",
                        $ingredient?->getName() ?? '',
                    ),
                );
            }

            $plannedQuantity = $this->computeMaterialPlannedQuantity(
                $recipeItem,
                $scale,
            );
            $plannedFactorToBase = $this->resolveFactorToBase(
                $ingredient,
                $recipeItem->getUnit(),
                $recipeItem->getFactorToBaseSnapshot(),
                "nguyên liệu",
            );

            $orderMaterial = new ProductionOrderMaterial();
            $orderMaterial->setProductionOrderItem($orderItem);
            $orderMaterial->setSourceRecipeItem($recipeItem);
            $orderMaterial->setIngredient($ingredient);
            $orderMaterial->setIngredientSnapshot([
                "id" => $ingredient->getId(),
                "code" => $ingredient->getCode(),
                "name" => $ingredient->getName(),
                "baseUnitId" => $baseUnit->getId(),
                "baseUnitName" => $baseUnit->getName(),
            ]);
            $orderMaterial->setRecipeItemSnapshot([
                "quantity" => $recipeItem->getQuantity(),
                "unitId" => $recipeItem->getUnit()?->getId(),
                "unitName" => $recipeItem->getUnit()?->getName(),
                "factorToBaseSnapshot" => $recipeItem->getFactorToBaseSnapshot(),
                "baseQuantitySnapshot" => $recipeItem->getBaseQuantitySnapshot(),
                "wasteRate" => $recipeItem->getWasteRate(),
            ]);
            $orderMaterial->setPlannedQuantity($plannedQuantity);
            $orderMaterial->setPlannedUnit($recipeItem->getUnit());
            $orderMaterial->setPlannedFactorToBase($plannedFactorToBase);
            $orderMaterial->setPlannedBaseQuantity(
                MathHelper::mul($plannedQuantity, $plannedFactorToBase, 6),
            );
            $orderMaterial->setBaseUnit($baseUnit);
            // Pricing thực tế sẽ được snapshot khi xuất kho; plannedCost hiện
            // chưa có nguồn giá dự kiến nên để 0, actualCost được ghi khi issue.
            $orderMaterial->setPricingSnapshot([]);
            $orderMaterial->setPlannedCost('0.0000');
            $orderMaterial->setSortOrder($recipeItem->getSortOrder() ?? 0);
            $orderMaterial->setNote($recipeItem->getNotes());
            $this->entityManager->persist($orderMaterial);
        }
    }

    private function computeRecipeScale(
        MerchandiseRecipe $recipe,
        string $plannedOutputBaseQuantity,
    ): string {
        $recipeOutputBaseQuantity = $recipe->getOutputBaseQuantitySnapshot();

        if (
            $recipeOutputBaseQuantity === null
            || MathHelper::comp($recipeOutputBaseQuantity, '0') <= 0
        ) {
            throw new \Exception(
                'Base quantity output của công thức không hợp lệ',
            );
        }

        return MathHelper::div(
            $plannedOutputBaseQuantity,
            $recipeOutputBaseQuantity,
            6,
        );
    }

    private function computeMaterialPlannedQuantity(
        MerchandiseRecipeItem $recipeItem,
        string $scale,
    ): string {
        $baseQuantity = MathHelper::mul(
            $recipeItem->getQuantity() ?? '0',
            $scale,
            6,
        );
        $wasteRate = $recipeItem->getWasteRate() ?? '0.00';
        // Tính cả tỷ lệ hao hụt như cách form đề xuất đã hiển thị
        $factor = MathHelper::add('1', MathHelper::div($wasteRate, '100', 6), 8);

        return MathHelper::mul($baseQuantity, $factor, 6);
    }

    /**
     * Lấy hệ số quy đổi của merchandise+unit, fallback về snapshot tại thời điểm lưu công thức.
     */
    private function resolveFactorToBase(
        Merchandise $merchandise,
        ?Unit $unit,
        ?string $snapshotFactor,
        string $label,
    ): string {
        if (!$unit) {
            throw new \Exception(
                sprintf("Đơn vị tính %s không tồn tại", $label),
            );
        }

        $merchandiseUnit = $this->merchandiseUnitRepository->findOneBy([
            "merchandise" => $merchandise,
            "unit" => $unit,
        ]);
        if ($merchandiseUnit && $merchandiseUnit->getFactorToBase() !== null) {
            return $merchandiseUnit->getFactorToBase();
        }

        if ($snapshotFactor !== null) {
            return $snapshotFactor;
        }

        throw new \Exception(
            sprintf(
                "Chưa cấu hình hệ số quy đổi cho đơn vị '%s' của %s '%s'",
                $unit->getName(),
                $label,
                $merchandise->getName(),
            ),
        );
    }

    private function recordProductionEvent(
        ProductionOrder $productionOrder,
        string $eventType,
        User $actor,
        string $message,
        ?ProductionOrderItem $productionOrderItem = null,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?array $meta = null,
    ): void {
        $event = new ProductionEvent();
        $event->setProductionOrder($productionOrder);
        $event->setProductionOrderItem($productionOrderItem);
        $event->setEventType($eventType);
        $event->setActor($actor);
        $event->setMessage($message);

        if ($fromStatus !== null) {
            $event->setFromStatus($fromStatus);
        }
        if ($toStatus !== null) {
            $event->setToStatus($toStatus);
        }
        if ($meta !== null) {
            $event->setMeta($meta);
        }

        $this->entityManager->persist($event);
    }

    private function assertNextProductionOrderItemStatus(
        string $currentStatus,
        string $targetStatus,
    ): void {
        $statusOrder = [
            ProductionOrderStatus::Created->value,
            ProductionOrderStatus::MaterialIssued->value,
            ProductionOrderStatus::Started->value,
            ProductionOrderStatus::InProgress->value,
            ProductionOrderStatus::Inspecting->value,
        ];

        $currentIndex = array_search($currentStatus, $statusOrder, true);
        $targetIndex = array_search($targetStatus, $statusOrder, true);

        if (
            $currentIndex === false ||
            $targetIndex === false ||
            $targetIndex !== $currentIndex + 1
        ) {
            throw new \Exception(
                'Chuyển trạng thái thành phẩm không đúng trình tự hợp lệ',
            );
        }
    }

    /**
     * Snapshot phần bù khi lập lệnh để BOM luôn dựa trên target đã duyệt.
     * Không reserve shortage: inspection sẽ tính lại shortage thực tế trước khi phân bổ.
     */
    private function buildSupplementData(
        Merchandise $finishedProduct,
        mixed $selections,
        string $plannedBaseQuantity,
    ): array {
        $plans = [];
        $supplementBase = '0.000000';
        $seenIds = [];

        foreach (is_array($selections) ? $selections : [] as $selection) {
            $targetId = (int) ($selection['productionOrderItemId'] ?? 0);
            $mode = $selection['mode'] ?? null;
            if ($targetId <= 0 || isset($seenIds[$targetId])) {
                continue;
            }
            $seenIds[$targetId] = true;
            if (!in_array($mode, ['MINIMUM', 'FULL'], true)) {
                throw new \Exception('Cách bù thiếu không hợp lệ');
            }

            $target = $this->productionOrderItemRepository->find($targetId);
            if (
                !$target ||
                $target->getStatus() !== ProductionOrderStatus::WaitingSupplement->value ||
                $target->getFinishedProduct()?->getId() !== $finishedProduct->getId()
            ) {
                continue;
            }

            $remaining = $this->getShortageRemaining($target, $mode);
            if (MathHelper::comp($remaining, '0') <= 0) {
                continue;
            }
            $plans[] = [
                'productionOrderItemId' => $target->getId(),
                'mode' => $mode,
                'plannedBaseQuantity' => $remaining,
            ];
            $supplementBase = MathHelper::add($supplementBase, $remaining, 6);
        }

        return [
            'productionTargetBaseQuantity' => MathHelper::add($plannedBaseQuantity, $supplementBase, 6),
            'plans' => $plans,
        ];
    }

    private function enrichSupplementPlans(array $productionOrder): array
    {
        $sourceItemIds = [];
        foreach ($productionOrder['items'] ?? [] as $orderItem) {
            foreach ($orderItem['supplementData']['plans'] ?? [] as $plan) {
                $sourceItemId = (int) ($plan['productionOrderItemId'] ?? 0);
                if ($sourceItemId > 0) {
                    $sourceItemIds[$sourceItemId] = $sourceItemId;
                }
            }
            foreach ($orderItem['shortageData']['supplementHistory'] ?? [] as $history) {
                $sourceItemId = (int) ($history['sourceProductionOrderItemId'] ?? 0);
                if ($sourceItemId > 0) {
                    $sourceItemIds[$sourceItemId] = $sourceItemId;
                }
            }
        }

        if ($sourceItemIds === []) {
            return $productionOrder;
        }

        $sourceItems = $this->productionOrderItemRepository->findByIdsWithDetails(
            array_values($sourceItemIds),
        );
        $sourceItemsById = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceItemsById[$sourceItem->getId()] = $sourceItem;
        }

        foreach ($productionOrder['items'] ?? [] as $itemIndex => $orderItem) {
            foreach ($orderItem['supplementData']['plans'] ?? [] as $planIndex => $plan) {
                $sourceItem = $sourceItemsById[(int) ($plan['productionOrderItemId'] ?? 0)] ?? null;
                if (!$sourceItem) {
                    continue;
                }

                $enrichedPlan = &$productionOrder['items'][$itemIndex]['supplementData']['plans'][$planIndex];
                $enrichedPlan['sourceProductionOrderCode'] = $sourceItem->getProductionOrder()?->getCode();
                $enrichedPlan['sourceFinishedProductCode'] = $sourceItem->getFinishedProduct()?->getCode();
                $enrichedPlan['sourceFinishedProductName'] = $sourceItem->getFinishedProduct()?->getName();
                unset($enrichedPlan);
            }
            foreach ($orderItem['shortageData']['supplementHistory'] ?? [] as $historyIndex => $history) {
                $sourceItem = $sourceItemsById[(int) ($history['sourceProductionOrderItemId'] ?? 0)] ?? null;
                if (!$sourceItem) {
                    continue;
                }

                $enrichedHistory = &$productionOrder['items'][$itemIndex]['shortageData']['supplementHistory'][$historyIndex];
                $enrichedHistory['sourceProductionOrderCode'] = $sourceItem->getProductionOrder()?->getCode();
                $enrichedHistory['sourceFinishedProductCode'] = $sourceItem->getFinishedProduct()?->getCode();
                $enrichedHistory['sourceFinishedProductName'] = $sourceItem->getFinishedProduct()?->getName();
                unset($enrichedHistory);
            }
        }

        return $productionOrder;
    }

    private function getShortageRemaining(
        ProductionOrderItem $item,
        string $mode,
    ): string {
        $planned = $item->getPlannedBaseQuantity() ?? '0';
        $minimum = MathHelper::sub(
            $planned,
            MathHelper::mul($planned, MathHelper::div($item->getExpectedWastePercent(), '100', 8), 6),
            6,
        );
        $shortageData = $item->getShortageData() ?? [];
        $external = (string) ($shortageData['externalFulfilledBaseQuantity'] ?? '0');
        $effective = MathHelper::add($item->getAcceptedBaseQuantity(), $external, 6);
        $target = $mode === 'FULL' ? $planned : $minimum;
        $remaining = MathHelper::sub($target, $effective, 6);

        return MathHelper::comp($remaining, '0') > 0 ? $remaining : '0.000000';
    }

    public function findOpenShortages(int $merchandiseId): array
    {
        $items = $this->productionOrderItemRepository
            ->findWaitingSupplementByMerchandiseId($merchandiseId);

        return array_map(function (ProductionOrderItem $item): array {
            $minimumRemaining = $this->getShortageRemaining($item, 'MINIMUM');
            $fullRemaining = $this->getShortageRemaining($item, 'FULL');
            $shortageData = $item->getShortageData() ?? [];

            return [
                'productionOrderId' => $item->getProductionOrder()?->getId(),
                'productionOrderCode' => $item->getProductionOrder()?->getCode(),
                'productionOrderItemId' => $item->getId(),
                'plannedBaseQuantity' => $item->getPlannedBaseQuantity(),
                'acceptedBaseQuantity' => $item->getAcceptedBaseQuantity(),
                'baseUnitName' => $item->getBaseUnit()?->getName(),
                'minimumAcceptableBaseQuantity' => $shortageData['minimumAcceptableBaseQuantity'] ?? MathHelper::sub($item->getPlannedBaseQuantity() ?? '0', MathHelper::mul($item->getPlannedBaseQuantity() ?? '0', MathHelper::div($item->getExpectedWastePercent(), '100', 8), 6), 6),
                'minimumRemainingBaseQuantity' => $minimumRemaining,
                'fullRemainingBaseQuantity' => $fullRemaining,
            ];
        }, $items);
    }

    public function syncProductionOrderStatusFromItems(
        ProductionOrder $productionOrder,
    ): void {
        $items = $this->productionOrderItemRepository->findBy(
            ['productionOrder' => $productionOrder],
            ['sortOrder' => 'ASC'],
        );
        if ($items === []) {
            return;
        }

        $statusOrder = [
            ProductionOrderStatus::Created->value,
            ProductionOrderStatus::MaterialIssued->value,
            ProductionOrderStatus::Started->value,
            ProductionOrderStatus::InProgress->value,
            ProductionOrderStatus::Inspecting->value,
        ];
        $activeStatuses = [];
        $hasWaitingSupplement = false;
        foreach ($items as $item) {
            if ($item->getStatus() === ProductionOrderStatus::WaitingSupplement->value) {
                $hasWaitingSupplement = true;
                continue;
            }
            if ($item->getStatus() === ProductionOrderStatus::Completed->value) {
                continue;
            }
            $index = array_search($item->getStatus(), $statusOrder, true);
            if ($index !== false) {
                $activeStatuses[] = $index;
            }
        }
        if ($activeStatuses !== []) {
            $productionOrder->setStatus($statusOrder[min($activeStatuses)]);
        } elseif ($hasWaitingSupplement) {
            $productionOrder->setStatus(ProductionOrderStatus::WaitingSupplement->value);
        } else {
            $productionOrder->setStatus(ProductionOrderStatus::Completed->value);
        }

        $startedStatuses = [
            ProductionOrderStatus::Started->value,
            ProductionOrderStatus::InProgress->value,
            ProductionOrderStatus::Inspecting->value,
            ProductionOrderStatus::Completed->value,
        ];
        if (
            in_array($productionOrder->getStatus(), $startedStatuses, true)
            && $productionOrder->getStartedAt() === null
        ) {
            $productionOrder->setStartedAt(new \DateTime());
        }
        if (
            $productionOrder->getStatus() === ProductionOrderStatus::Completed->value
            && $productionOrder->getCompletedAt() === null
        ) {
            $productionOrder->setCompletedAt(new \DateTime());
        }
    }
}
