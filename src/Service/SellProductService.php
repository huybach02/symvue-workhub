<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\Class\InventoryLotStatus;
use App\Class\InventoryMovementSourceType;
use App\Class\InventoryMovementType;
use App\Class\MathHelper;
use App\Class\SaleOrderPaymentStatus;
use App\Class\SaleOrderStatus;
use App\DTO\CreateSaleOrderDTO;
use App\Entity\Branch;
use App\Entity\BusinessProduct;
use App\Entity\BusinessProductVariant;
use App\Entity\BusinessProductVariantRecipe;
use App\Entity\DiningTable;
use App\Entity\InventoryBalance;
use App\Entity\InventoryMovement;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderItem;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Repository\BranchRepository;
use App\Repository\BusinessProductRepository;
use App\Repository\BusinessProductVariantRecipeRepository;
use App\Repository\BusinessProductVariantRepository;
use App\Repository\DiningTableRepository;
use App\Repository\InventoryBalanceRepository;
use App\Repository\SaleOrderRepository;
use App\Repository\UserPositionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SellProductService
{
    public function __construct(
        private readonly SaleOrderRepository $saleOrderRepository,
        private readonly BusinessProductRepository $businessProductRepository,
        private readonly BusinessProductVariantRepository $variantRepository,
        private readonly BusinessProductVariantRecipeRepository $recipeRepository,
        private readonly DiningTableRepository $diningTableRepository,
        private readonly BranchRepository $branchRepository,
        private readonly InventoryBalanceRepository $inventoryBalanceRepository,
        private readonly UserPositionRepository $userPositionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->saleOrderRepository->createQueryBuilder('e')
            ->leftJoin('e.branch', 'b')
            ->leftJoin('e.diningTable', 'dt')
            ->leftJoin('e.cashier', 'u')
            ->addSelect('b', 'dt', 'u')
            ->orderBy('e.id', 'DESC');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        $result['collection'] = array_map(
            fn(SaleOrder $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->saleOrderRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(CreateSaleOrderDTO $dto, ?User $currentUser = null): array
    {
        if (!$currentUser instanceof User) {
            throw new \Exception('Yêu cầu thông tin tài khoản người dùng để thực hiện bán hàng và ghi nhận biến động kho');
        }

        $this->entityManager->beginTransaction();

        try {
            $branch = $this->resolveBranch($dto, $currentUser);
            $warehouse = $branch?->getWarehouse();

            $diningTable = $this->diningTableRepository->findForUpdate($dto->diningTableId);
            if (!$diningTable instanceof DiningTable) {
                throw new \Exception(sprintf('Bàn ăn ID %d không tồn tại', $dto->diningTableId));
            }
            if ($diningTable->getStatus() !== DiningTable::STATUS_ACTIVE) {
                throw new \Exception(sprintf('Bàn số %d hiện đang ngưng sử dụng', $diningTable->getTableNumber()));
            }
            if ($diningTable->isUsing() === true) {
                throw new \Exception(sprintf('Bàn số %d hiện đang có khách sử dụng', $diningTable->getTableNumber()));
            }
            $diningTable->setIsUsing(true);

            $orderDate = new \DateTime();
            $orderCode = $this->saleOrderRepository->generateNextCode($orderDate);

            $saleOrder = new SaleOrder();
            $saleOrder->setCode($orderCode);
            $saleOrder->setOrderDate($orderDate);
            $saleOrder->setBranch($branch);
            $saleOrder->setDiningTable($diningTable);
            $saleOrder->setCashier($currentUser);
            $saleOrder->setPaymentMethod($dto->paymentMethod);
            $saleOrder->setNote($dto->orderNote);
            $saleOrder->setStatus(SaleOrderStatus::Created->value);
            $saleOrder->setPaymentStatus(SaleOrderPaymentStatus::Unpaid->value);

            $subtotal = '0.00';
            $recipeDemands = [];

            foreach ($dto->items ?? [] as $itemDto) {
                $product = $this->businessProductRepository->find($itemDto->productId);
                if (!$product instanceof BusinessProduct) {
                    throw new \Exception(sprintf('Sản phẩm ID %d không tồn tại', $itemDto->productId));
                }
                if ($product->getStatus() !== 1) {
                    throw new \Exception(sprintf('Sản phẩm "%s" hiện đang ngừng kinh doanh', $product->getName()));
                }

                $variant = null;
                if ($itemDto->variantId !== null) {
                    $variant = $this->variantRepository->find($itemDto->variantId);
                    if (!$variant instanceof BusinessProductVariant) {
                        throw new \Exception(sprintf('Biến thể ID %d không tồn tại', $itemDto->variantId));
                    }
                    if ($variant->getBusinessProduct()?->getId() !== $product->getId()) {
                        throw new \Exception('Biến thể không thuộc sản phẩm đã chọn');
                    }
                    if ($variant->getStatus() !== 1) {
                        throw new \Exception(sprintf('Biến thể "%s" hiện đang ngừng kinh doanh', $variant->getName()));
                    }
                } else {
                    $variants = $this->variantRepository->findBy(
                        ['businessProduct' => $product, 'status' => 1],
                        ['isDefault' => 'DESC', 'id' => 'ASC'],
                        1
                    );
                    $variant = $variants[0] ?? null;
                }

                if (!$variant instanceof BusinessProductVariant) {
                    throw new \Exception(sprintf('Sản phẩm "%s" không có biến thể hoạt động để bán', $product->getName()));
                }

                $productSnapshot = $product->jsonSerialize();
                $variantSnapshot = $this->serializeVariantSnapshot($variant);

                $itemQuantity = sprintf('%.2f', (float) $itemDto->quantity);
                // Lấy giá bán authoritative từ database (variant->getSellingPrice), tuyệt đối không tin cậy giá client gửi lên
                $unitPrice = sprintf('%.2f', (float) ($variant->getSellingPrice() ?? '0.00'));
                $lineSubtotal = MathHelper::mul($itemQuantity, $unitPrice, 2);
                $subtotal = MathHelper::add($subtotal, $lineSubtotal, 2);

                $saleOrderItem = new SaleOrderItem();
                $saleOrderItem->setBusinessProduct($product);
                $saleOrderItem->setVariant($variant);
                $saleOrderItem->setQuantity($itemQuantity);
                $saleOrderItem->setUnitPrice($unitPrice);
                $saleOrderItem->setSubtotal($lineSubtotal);
                $saleOrderItem->setNote($itemDto->note);
                $saleOrderItem->setProductSnapshot($productSnapshot);
                $saleOrderItem->setVariantSnapshot($variantSnapshot);

                // Thu thập nhu cầu nguyên vật liệu và lưu snapshot công thức đang hoạt động của variant
                $recipeSnapshot = null;
                if ($variant !== null) {
                    $recipe = $this->recipeRepository->findOneBy(
                        ['variant' => $variant, 'isActive' => true],
                        ['version' => 'DESC', 'id' => 'DESC']
                    );

                    if ($recipe instanceof BusinessProductVariantRecipe) {
                        $recipeSnapshot = $this->serializeRecipeSnapshot($recipe, $itemQuantity);
                        foreach ($recipe->getItems() as $recipeItem) {
                            $ingredient = $recipeItem->getFinishedProduct();
                            if ($ingredient === null || $ingredient->getBaseUnit() === null) {
                                continue;
                            }

                            $recipeQty = $recipeItem->getQuantity() ?: '0';
                            $factor = $recipeItem->getFactorToBaseSnapshot() ?: '1';
                            $neededBaseQty = MathHelper::mul(
                                MathHelper::mul($itemQuantity, $recipeQty, 6),
                                $factor,
                                6
                            );

                            if (MathHelper::comp($neededBaseQty, '0') <= 0) {
                                continue;
                            }

                            $ingredientId = (int) $ingredient->getId();
                            if (!isset($recipeDemands[$ingredientId])) {
                                $recipeDemands[$ingredientId] = [
                                    'merchandise' => $ingredient,
                                    'totalNeeded' => '0.000000',
                                    'baseUnit' => $ingredient->getBaseUnit(),
                                ];
                            }

                            $recipeDemands[$ingredientId]['totalNeeded'] = MathHelper::add(
                                $recipeDemands[$ingredientId]['totalNeeded'],
                                $neededBaseQty,
                                6
                            );
                        }
                    }
                }

                $saleOrderItem->setRecipeSnapshot($recipeSnapshot);
                $saleOrder->addItem($saleOrderItem);
            }

            $saleOrder->setSubtotal($subtotal);
            $saleOrder->setTotalAmount($subtotal);

            // Xử lý trừ kho nguyên vật liệu
            if (!empty($recipeDemands)) {
                if (!$warehouse instanceof Warehouse) {
                    throw new \Exception('Chi nhánh chưa được cấu hình kho hàng đang hoạt động để trừ nguyên vật liệu');
                }

                $this->deductInventoryForOrder($warehouse, $recipeDemands, $saleOrder, $currentUser);
            }

            $this->entityManager->persist($saleOrder);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $saleOrder->jsonSerialize();
        } catch (\Throwable $th) {
            $this->entityManager->rollback();
            throw $th;
        }
    }

    public function delete(int $id): void
    {
        $item = $this->saleOrderRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function resolveBranch(CreateSaleOrderDTO $dto, ?User $currentUser): Branch
    {
        if ($dto->branchId !== null) {
            $branch = $this->branchRepository->find($dto->branchId);
            if (!$branch instanceof Branch || $branch->getStatus() !== true) {
                throw new \Exception(sprintf('Chi nhánh ID %d không tồn tại hoặc đã ngưng hoạt động', $dto->branchId));
            }
            return $branch;
        }

        if ($currentUser !== null) {
            $position = $this->userPositionRepository->findActivePrimaryPositionByUser($currentUser);
            $userBranch = $position?->getDepartment()?->getBranch();
            if ($userBranch instanceof Branch) {
                if ($userBranch->getStatus() !== true) {
                    throw new \Exception(sprintf('Chi nhánh "%s" của tài khoản đã ngưng hoạt động', $userBranch->getName()));
                }
                return $userBranch;
            }
        }

        throw new \Exception('Không xác định được chi nhánh bán hàng');
    }

    /**
     * Trừ kho nguyên vật liệu theo định lượng công thức (FEFO)
     */
    private function deductInventoryForOrder(
        Warehouse $warehouse,
        array $recipeDemands,
        SaleOrder $saleOrder,
        User $currentUser
    ): void {
        $today = (new \DateTime())->setTime(0, 0, 0);
        $postedAt = new \DateTime();

        // Sắp xếp thứ tự ID để tránh deadlock
        ksort($recipeDemands);

        foreach ($recipeDemands as $ingredientId => $demand) {
            $merchandise = $demand['merchandise'];
            $totalNeeded = $demand['totalNeeded'];

            /** @var InventoryBalance[] $availableBalances */
            $availableBalances = $this->inventoryBalanceRepository->findAvailableForIssue(
                $warehouse,
                $merchandise,
                $today
            );

            // Kiểm tra tổng tồn khả dụng
            $totalAvailable = '0.000000';
            foreach ($availableBalances as $balance) {
                $available = MathHelper::sub(
                    $balance->getOnHandBaseQuantity(),
                    MathHelper::add($balance->getReservedBaseQuantity(), $balance->getBlockedBaseQuantity())
                );
                $totalAvailable = MathHelper::add($totalAvailable, $available, 6);
            }

            if (MathHelper::comp($totalAvailable, $totalNeeded) < 0) {
                throw new \Exception(sprintf(
                    'Nguyên liệu "%s" trong kho "%s" không đủ tồn (cần %s %s, khả dụng %s %s)',
                    $merchandise->getName(),
                    $warehouse->getName(),
                    formatDecimal($totalNeeded),
                    $demand['baseUnit']?->getCode() ?? '',
                    formatDecimal($totalAvailable),
                    $demand['baseUnit']?->getCode() ?? ''
                ));
            }

            // Phân bổ và trừ tồn theo từng lot
            $remaining = $totalNeeded;
            foreach ($availableBalances as $balance) {
                if (MathHelper::comp($remaining, '0') <= 0) {
                    break;
                }

                $available = MathHelper::sub(
                    $balance->getOnHandBaseQuantity(),
                    MathHelper::add($balance->getReservedBaseQuantity(), $balance->getBlockedBaseQuantity())
                );

                if (MathHelper::comp($available, '0') <= 0) {
                    continue;
                }

                $take = MathHelper::comp($remaining, $available) <= 0 ? $remaining : $available;
                $newOnHand = MathHelper::sub($balance->getOnHandBaseQuantity(), $take);
                $balance->setOnHandBaseQuantity($newOnHand);

                if (MathHelper::comp($newOnHand, '0') === 0) {
                    $balance->getLot()?->setStatus(InventoryLotStatus::Depleted->value);
                }

                $unitCostBase = $this->inventoryBalanceRepository->findUnitCostBaseForLot($balance->getLot());

                $movement = new InventoryMovement();
                $movement->setMovementType(InventoryMovementType::StockOut->value);
                $movement->setSourceType(InventoryMovementSourceType::SaleOrder->value);
                $movement->setSourceRef([
                    'orderCode' => $saleOrder->getCode(),
                    'branchId' => $warehouse->getBranch()?->getId(),
                    'warehouseId' => $warehouse->getId(),
                ]);
                $movement->setWarehouse($warehouse);
                $movement->setMerchandise($merchandise);
                $movement->setLot($balance->getLot());
                $movement->setQuantityBaseDelta(MathHelper::mul($take, '-1', 6));
                $movement->setBaseUnit($demand['baseUnit']);
                $movement->setUnitCostBase($unitCostBase);
                $movement->setNote(sprintf('Xuất nguyên liệu bán hàng theo đơn %s', $saleOrder->getCode()));
                $movement->setPostedAt($postedAt);
                $movement->setPostedBy($currentUser);

                $this->entityManager->persist($movement);

                $remaining = MathHelper::sub($remaining, $take, 6);
            }
        }
    }

    private function serializeVariantSnapshot(BusinessProductVariant $variant): array
    {
        return [
            'id' => $variant->getId(),
            'code' => $variant->getCode(),
            'name' => $variant->getName(),
            'barcode' => $variant->getBarcode(),
            'sellingPrice' => formatDecimal($variant->getSellingPrice()),
            'costPriceSnapshot' => formatDecimal($variant->getCostPriceSnapshot()),
            'suggestedPriceSnapshot' => formatDecimal($variant->getSuggestedPriceSnapshot()),
            'unit' => $variant->getUnit() ? [
                'id' => $variant->getUnit()->getId(),
                'code' => $variant->getUnit()->getCode(),
                'name' => $variant->getUnit()->getName(),
            ] : null,
        ];
    }

    private function serializeRecipeSnapshot(BusinessProductVariantRecipe $recipe, string $itemQuantity): array
    {
        $items = [];
        foreach ($recipe->getItems() as $recipeItem) {
            $merchandise = $recipeItem->getFinishedProduct();
            $recipeQty = $recipeItem->getQuantity() ?: '0';
            $factor = $recipeItem->getFactorToBaseSnapshot() ?: '1';
            $wasteRate = $recipeItem->getWasteRate() ?: '0.00';

            $totalBaseQty = MathHelper::mul(
                MathHelper::mul($itemQuantity, $recipeQty, 6),
                $factor,
                6
            );

            $items[] = [
                'merchandiseId' => $merchandise?->getId(),
                'merchandiseCode' => $merchandise?->getCode(),
                'merchandiseName' => $merchandise?->getName(),
                'merchandiseType' => $merchandise?->getType(),
                'quantity' => formatDecimal($recipeQty),
                'unitId' => $recipeItem->getUnit()?->getId(),
                'unitCode' => $recipeItem->getUnit()?->getCode(),
                'unitName' => $recipeItem->getUnit()?->getName(),
                'factorToBase' => formatDecimal($factor),
                'wasteRate' => formatDecimal($wasteRate),
                'baseUnitId' => $merchandise?->getBaseUnit()?->getId(),
                'baseUnitCode' => $merchandise?->getBaseUnit()?->getCode(),
                'totalBaseQuantity' => formatDecimal($totalBaseQty),
            ];
        }

        return [
            'id' => $recipe->getId(),
            'version' => $recipe->getVersion(),
            'notes' => $recipe->getNotes(),
            'totalCostSnapshot' => formatDecimal($recipe->getTotalCostSnapshot() ?? '0.00'),
            'suggestedPriceSnapshot' => formatDecimal($recipe->getSuggestedPriceSnapshot() ?? '0.00'),
            'targetProfitMarginSnapshot' => formatDecimal($recipe->getTargetProfitMarginSnapshot() ?? '0.00'),
            'items' => $items,
        ];
    }
}
