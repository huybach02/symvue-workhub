<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementType;
use App\Class\InventoryMovementSourceType;
use App\Class\MathHelper;
use App\Entity\InventoryBalance;
use App\Entity\InventoryMovement;
use App\Entity\ProductionOrder;
use App\Entity\ProductionOrderItem;
use App\Entity\ProductionOrderMaterial;
use App\Entity\User;
use App\Repository\InventoryBalanceRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductionMaterialIssueService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InventoryBalanceRepository $inventoryBalanceRepository,
    ) {
    }

    public function issueMaterialsForItem(
        ProductionOrderItem $item,
        User $currentUser,
    ): array {
        $productionOrder = $item->getProductionOrder();
        if ($productionOrder === null) {
            throw new \Exception('Thành phẩm chưa thuộc lệnh sản xuất nào');
        }

        $warehouse = $productionOrder->getMaterialWarehouse();
        if ($warehouse === null) {
            throw new \Exception('Lệnh sản xuất chưa cấu hình kho xuất nguyên liệu');
        }

        $materials = $item->getMaterials()->toArray();
        if ($materials === []) {
            throw new \Exception('Thành phẩm không có nguyên liệu để xuất kho');
        }

        $this->assertMaterialsValid($materials);

        $postedAt = new \DateTime();
        $allocationPlan = $this->buildAllocationPlan($materials, $warehouse, $postedAt);

        $totalCost = '0.0000';
        $movementCount = 0;

        foreach ($materials as $material) {
            $materialIssueLines = $allocationPlan[$material->getId()] ?? [];

            $pricingSnapshot = [];
            $materialTotalCost = '0.0000';

            foreach ($materialIssueLines as $line) {
                /** @var InventoryBalance $balance */
                $balance = $line['balance'];
                $quantity = $line['quantity'];

                $newOnHand = MathHelper::sub(
                    $balance->getOnHandBaseQuantity(),
                    $quantity,
                );
                $balance->setOnHandBaseQuantity($newOnHand);

                // Lot không còn tồn vật lý thì chuyển sang DEPLETED
                if (MathHelper::comp($newOnHand, '0') === 0) {
                    $balance->getLot()?->setStatus(InventoryLotStatus::Depleted->value);
                }

                $unitCostBase = $this->inventoryBalanceRepository->findUnitCostBaseForLot(
                    $balance->getLot(),
                );

                $movement = new InventoryMovement();
                $movement->setMovementType(InventoryMovementType::StockOut->value);
                $movement->setSourceType(InventoryMovementSourceType::ProductionMaterialIssue->value);
                $movement->setSourceRef([
                    'productionOrderId' => $productionOrder->getId(),
                    'productionOrderItemId' => $item->getId(),
                    'productionOrderMaterialId' => $material->getId(),
                ]);
                $movement->setWarehouse($warehouse);
                $movement->setMerchandise($material->getIngredient());
                $movement->setLot($balance->getLot());
                $movement->setProductionOrderItem($item);
                $movement->setQuantityBaseDelta(MathHelper::mul($quantity, '-1'));
                $movement->setBaseUnit($material->getBaseUnit());
                $movement->setUnitCostBase($unitCostBase);
                $movement->setNote('Xuất nguyên liệu cho lệnh sản xuất ' . $productionOrder->getCode());
                $movement->setPostedAt($postedAt);
                $movement->setPostedBy($currentUser);
                $this->entityManager->persist($movement);
                ++$movementCount;

                $lineCost = MathHelper::mul($quantity, $unitCostBase, 4);
                $materialTotalCost = MathHelper::add($materialTotalCost, $lineCost, 4);
                $totalCost = MathHelper::add($totalCost, $lineCost, 4);

                $pricingSnapshot[] = [
                    'lotId' => $balance->getLot()?->getId(),
                    'internalCode' => $balance->getLot()?->getInternalCode(),
                    'quantity' => $quantity,
                    'unitCostBase' => $unitCostBase,
                ];
            }

            $material->setPricingSnapshot($pricingSnapshot);
            $material->setPlannedCost($materialTotalCost);
        }

        return [
            'warehouse_id' => $warehouse->getId(),
            'material_count' => count($materials),
            'movement_count' => $movementCount,
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Kiểm tra dữ liệu nguyên liệu hợp lệ trước khi xuất.
     *
     * @param ProductionOrderMaterial[] $materials
     */
    private function assertMaterialsValid(array $materials): void
    {
        foreach ($materials as $material) {
            $ingredientName = $material->getIngredient()?->getName() ?? (string) $material->getId();

            if ($material->getIngredient() === null || $material->getBaseUnit() === null) {
                throw new \Exception(sprintf(
                    'Nguyên liệu %s thiếu thông tin merchandise/base unit',
                    $ingredientName,
                ));
            }

            if (MathHelper::comp($material->getPlannedBaseQuantity(), '0') <= 0) {
                throw new \Exception(sprintf(
                    'Nguyên liệu %s có số lượng kế hoạch không hợp lệ',
                    $ingredientName,
                ));
            }
        }
    }

    /**
     * Lập kế hoạch phân bổ tồn kho theo lot (FEFO) cho tất cả nguyên liệu.
     * Không thay đổi dữ liệu — chỉ trả về plan. Nếu thiếu bất kỳ nguyên liệu nào
     * thì ném exception với danh sách thiếu
     */
    private function buildAllocationPlan(
        array $materials,
        \App\Entity\Warehouse $warehouse,
        \DateTimeInterface $postedAt,
    ): array {
        $allocationPlan = [];
        $shortages = [];

        foreach ($materials as $material) {
            $remaining = $material->getPlannedBaseQuantity();
            $availableBalances = $this->inventoryBalanceRepository->findAvailableForIssue(
                $warehouse,
                $material->getIngredient(),
                $postedAt,
            );

            $availableTotal = '0.000000';

            foreach ($availableBalances as $balance) {
                $available = MathHelper::sub(
                    $balance->getOnHandBaseQuantity(),
                    MathHelper::add($balance->getReservedBaseQuantity(), $balance->getBlockedBaseQuantity()),
                );

                if (MathHelper::comp($remaining, '0') <= 0) {
                    break;
                }

                $take = MathHelper::comp($remaining, $available) < 0
                    ? $remaining
                    : $available;

                $availableTotal = MathHelper::add($availableTotal, $take);
                $allocationPlan[$material->getId()][] = [
                    'balance' => $balance,
                    'quantity' => $take,
                ];
                $remaining = MathHelper::sub($remaining, $take);
            }

            if (MathHelper::comp($remaining, '0') > 0) {
                $shortages[] = [
                    'ingredientId' => $material->getIngredient()?->getId(),
                    'ingredientName' => $material->getIngredient()?->getName(),
                    'requiredBase' => $material->getPlannedBaseQuantity(),
                    'availableBase' => $availableTotal,
                    'shortageBase' => $remaining,
                ];
            }
        }

        if ($shortages !== []) {
            throw new \Exception(json_encode([
                'message' => 'Thiếu nguyên liệu để xuất kho, vui lòng kiểm tra lại tồn kho',
                'shortages' => $shortages,
            ], JSON_UNESCAPED_UNICODE));
        }

        return $allocationPlan;
    }
}
