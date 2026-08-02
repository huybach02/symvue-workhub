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
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductionOrderService
{
    public function __construct(
        private readonly ProductionOrderRepository $productionOrderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly WarehouseHelper $warehouseHelper,
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly UnitRepository $unitRepository,
        private readonly MerchandiseRecipeRepository $merchandiseRecipeRepository,
        private readonly MerchandiseUnitRepository $merchandiseUnitRepository,
    ) {
    }

    public function findAll(array $params): array
    {
        $qb = $this->productionOrderRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

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

        return $item->jsonSerialize();
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
        $orderItem->setPlannedBaseQuantity(
            MathHelper::mul($plannedQuantity, $plannedFactorToBase, 6),
        );
        $orderItem->setBaseUnit($baseUnit);
        $wastePercent = $itemData["expectedWastePercent"] ?? null;
        $orderItem->setExpectedWastePercent(
            $wastePercent !== null ? (string) $wastePercent : '0.00',
        );
        $orderItem->setSortOrder($sortOrder);
        $this->entityManager->persist($orderItem);

        $this->addMaterialsFromRecipe($orderItem, $recipe, $plannedQuantity);
    }

    /**
     * Sinh nguyên liệu kế hoạch theo công thức hiện tại của thành phẩm.
     */
    private function addMaterialsFromRecipe(
        ProductionOrderItem $orderItem,
        MerchandiseRecipe $recipe,
        string $plannedOutputQuantity,
    ): void {
        $scale = $this->computeRecipeScale($recipe, $plannedOutputQuantity);

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
            // Tạm thời để trống; giá trị thực tế lấy từ lô nguyên liệu khi xuất kho (FIFO)
            $orderMaterial->setPricingSnapshot([]);
            $orderMaterial->setPlannedCost('0.0000');
            $orderMaterial->setSortOrder($recipeItem->getSortOrder() ?? 0);
            $orderMaterial->setNote($recipeItem->getNotes());
            $this->entityManager->persist($orderMaterial);
        }
    }

    private function computeRecipeScale(
        MerchandiseRecipe $recipe,
        string $plannedOutputQuantity,
    ): string {
        $outputQuantity = $recipe->getOutputQuantity() ?? '1.0000';

        return MathHelper::div($plannedOutputQuantity, $outputQuantity, 6);
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
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?array $meta = null,
    ): void {
        $event = new ProductionEvent();
        $event->setProductionOrder($productionOrder);
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
}
