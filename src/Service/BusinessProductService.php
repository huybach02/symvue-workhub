<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\BusinessProductDTO;
use App\DTO\BusinessProductVariantDTO;
use App\DTO\BusinessProductVariantPriceDTO;
use App\DTO\BusinessProductVariantRecipeDTO;
use App\DTO\BusinessProductVariantRecipeItemDTO;
use App\Entity\BusinessProduct;
use App\Entity\BusinessProductVariant;
use App\Entity\BusinessProductVariantPrice;
use App\Entity\BusinessProductVariantRecipe;
use App\Entity\BusinessProductVariantRecipeItem;
use App\Repository\BusinessProductRepository;
use App\Repository\BusinessProductVariantPriceRepository;
use App\Repository\BusinessProductVariantRecipeRepository;
use App\Repository\BusinessProductVariantRepository;
use App\Repository\CategoryRepository;
use App\Repository\MerchandiseRepository;
use App\Repository\MerchandiseUnitRepository;
use App\Repository\UnitRepository;
use Doctrine\ORM\EntityManagerInterface;

class BusinessProductService
{
    public function __construct(
        private readonly BusinessProductRepository $businessProductRepository,
        private readonly BusinessProductVariantRepository $variantRepository,
        private readonly BusinessProductVariantRecipeRepository $recipeRepository,
        private readonly BusinessProductVariantPriceRepository $priceRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly UnitRepository $unitRepository,
        private readonly MerchandiseRepository $merchandiseRepository,
        private readonly MerchandiseUnitRepository $merchandiseUnitRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MerchandiseService $merchandiseService,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->businessProductRepository->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c');

        $result = FilterWithPagination::findWithPagination(
            $qb,
            $params,
            'e',
            relationFields: [
                'category' => [
                    'alias' => 'cat_filter',
                    'joinField' => 'e.category',
                    'targetField' => 'name',
                ],
            ],
        );

        $productIds = array_map(fn(BusinessProduct $item) => (int) $item->getId(), $result['collection']);
        $enrichData = $this->getListEnrichData($productIds);

        $result['collection'] = array_map(
            fn(BusinessProduct $item) => $this->enrichItem($item, $enrichData),
            $result['collection']
        );

        return $result;
    }

    /**
     * Lấy dữ liệu bổ sung cho danh sách: số biến thể, số thành phẩm trong recipe, giá từng biến thể.
     */
    public function findAllExportData(): array
    {
        $qb = $this->businessProductRepository->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->addSelect('c')
            ->orderBy('e.id', 'ASC');

        $products = $qb->getQuery()->getResult();

        $productIds = array_map(fn(BusinessProduct $p) => (int) $p->getId(), $products);

        if (empty($productIds)) {
            return [];
        }

        // Lấy tất cả variants (active + inactive)
        $variants = $this->variantRepository->createQueryBuilder('v')
            ->leftJoin('v.unit', 'vu')
            ->addSelect('vu')
            ->where('v.businessProduct IN (:ids)')
            ->setParameter('ids', $productIds)
            ->orderBy('v.businessProduct', 'ASC')
            ->addOrderBy('v.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();

        $variantIds = array_map(fn(BusinessProductVariant $v) => (int) $v->getId(), $variants);

        // Lấy recipes
        $recipes = [];
        $recipeItems = [];
        if (!empty($variantIds)) {
            $recipeEntities = $this->recipeRepository->createQueryBuilder('r')
                ->where('r.variant IN (:vids)')
                ->setParameter('vids', $variantIds)
                ->orderBy('r.version', 'DESC')
                ->getQuery()
                ->getResult();

            foreach ($recipeEntities as $recipe) {
                $recipes[(int) $recipe->getVariant()->getId()] = $recipe;
            }

            // Lấy recipe items
            $recipeIds = array_map(fn(BusinessProductVariantRecipe $r) => (int) $r->getId(), $recipeEntities);
            if (!empty($recipeIds)) {
                $items = $this->entityManager->getRepository(BusinessProductVariantRecipeItem::class)
                    ->createQueryBuilder('ri')
                    ->leftJoin('ri.finishedProduct', 'fp')
                    ->addSelect('fp')
                    ->leftJoin('ri.unit', 'riu')
                    ->addSelect('riu')
                    ->where('ri.recipe IN (:rids)')
                    ->setParameter('rids', $recipeIds)
                    ->orderBy('ri.sortOrder', 'ASC')
                    ->getQuery()
                    ->getResult();

                foreach ($items as $item) {
                    $recipeId = (int) $item->getRecipe()->getId();
                    $recipeItems[$recipeId][] = $item;
                }
            }
        }

        // Lấy prices
        $prices = [];
        if (!empty($variantIds)) {
            $priceEntities = $this->priceRepository->createQueryBuilder('p')
                ->where('p.variant IN (:vids)')
                ->setParameter('vids', $variantIds)
                ->orderBy('p.isCurrent', 'DESC')
                ->addOrderBy('p.id', 'DESC')
                ->getQuery()
                ->getResult();

            foreach ($priceEntities as $price) {
                $vid = (int) $price->getVariant()->getId();
                if (!isset($prices[$vid])) {
                    $prices[$vid] = $price;
                }
            }
        }

        // Nhóm variant theo product
        $variantsByProduct = [];
        foreach ($variants as $variant) {
            $pid = (int) $variant->getBusinessProduct()->getId();
            $variantsByProduct[$pid][] = $variant;
        }

        // Build result
        $result = [];
        foreach ($products as $product) {
            $pid = (int) $product->getId();
            $productData = $product->jsonSerialize();

            $productVariants = [];
            foreach ($variantsByProduct[$pid] ?? [] as $variant) {
                $vid = (int) $variant->getId();
                $recipe = $recipes[$vid] ?? null;
                $rid = $recipe ? (int) $recipe->getId() : null;
                $price = $prices[$vid] ?? null;

                $variantItems = [];
                if ($rid && isset($recipeItems[$rid])) {
                    foreach ($recipeItems[$rid] as $item) {
                        $variantItems[] = [
                            'code' => $item->getFinishedProduct()?->getCode(),
                            'name' => $item->getFinishedProduct()?->getName(),
                            'quantity' => formatDecimal($item->getQuantity()),
                            'unit' => $item->getUnit()?->getSymbol() ?? $item->getUnit()?->getCode(),
                            'wasteRate' => formatDecimal($item->getWasteRate()),
                            'baseQuantity' => formatDecimal($item->getBaseQuantitySnapshot()),
                            'costSnapshot' => formatDecimal($item->getLineCostSnapshot()),
                        ];
                    }
                }

                $productVariants[] = [
                    'code' => $variant->getCode(),
                    'name' => $variant->getName(),
                    'unit' => $variant->getUnit()?->getSymbol() ?? $variant->getUnit()?->getCode(),
                    'barcode' => $variant->getBarcode(),
                    'isDefault' => $variant->isDefault(),
                    'sellingPrice' => formatDecimal($variant->getSellingPrice()),
                    'costSnapshot' => formatDecimal($variant->getCostPriceSnapshot()),
                    'suggestedPrice' => formatDecimal($variant->getSuggestedPriceSnapshot()),
                    'currency' => $variant->getCurrency(),
                    'status' => $variant->getStatus(),
                    'priceConfig' => $price ? [
                        'price' => formatDecimal($price->getPrice()),
                        'effectiveFrom' => $price->getEffectiveFrom()?->format('Y-m-d'),
                        'effectiveTo' => $price->getEffectiveTo()?->format('Y-m-d'),
                        'isCurrent' => $price->isCurrent(),
                        'note' => $price->getNote(),
                    ] : null,
                    'recipeVersion' => $recipe?->getVersion(),
                    'recipeTotalCost' => $recipe ? formatDecimal($recipe->getTotalCostSnapshot()) : null,
                    'recipeItems' => $variantItems,
                ];
            }

            $result[] = [
                'product' => $productData,
                'variants' => $productVariants,
            ];
        }

        return $result;
    }

    /**
     * Lấy dữ liệu bổ sung cho danh sách: số biến thể, số thành phẩm trong recipe, giá từng biến thể.
     */
    private function getListEnrichData(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $variantData = $this->variantRepository->createQueryBuilder('v')
            ->select('IDENTITY(v.businessProduct) as productId', 'v.id as variantId', 'v.name as variantName', 'v.sellingPrice', 'v.currency')
            ->where('v.businessProduct IN (:ids)')
            ->andWhere('v.deletedAt IS NULL')
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getArrayResult();

        // Nhóm variant theo product
        $variantsByProduct = [];
        foreach ($variantData as $row) {
            $pid = (int) $row['productId'];
            $variantsByProduct[$pid][] = $row;
        }

        // Đếm số recipe item (thành phẩm) duy nhất cho từng product
        $recipeItemsData = $this->recipeRepository->createQueryBuilder('r')
            ->select('IDENTITY(v.businessProduct) as productId', 'COUNT(DISTINCT ri.finishedProduct) as finishedCount')
            ->join('r.variant', 'v')
            ->join('r.items', 'ri')
            ->where('v.businessProduct IN (:ids)')
            ->andWhere('r.deletedAt IS NULL')
            ->andWhere('ri.deletedAt IS NULL')
            ->groupBy('v.businessProduct')
            ->setParameter('ids', $productIds)
            ->getQuery()
            ->getArrayResult();

        $finishedCountByProduct = [];
        foreach ($recipeItemsData as $row) {
            $finishedCountByProduct[(int) $row['productId']] = (int) $row['finishedCount'];
        }

        return [
            'variantsByProduct' => $variantsByProduct,
            'finishedCountByProduct' => $finishedCountByProduct,
        ];
    }

    private function enrichItem(BusinessProduct $item, array $enrichData): array
    {
        $data = $item->jsonSerialize();
        $id = (int) $item->getId();

        $variants = $enrichData['variantsByProduct'][$id] ?? [];
        $data['variantCount'] = count($variants);
        $data['finishedProductCount'] = $enrichData['finishedCountByProduct'][$id] ?? 0;

        // Giá từng biến thể: "Biến thể: Giá"
        $variantPrices = [];
        foreach ($variants as $v) {
            $variantPrices[] = [
                'name' => $v['variantName'],
                'price' => formatDecimal($v['sellingPrice']),
                'currency' => $v['currency'] ?? 'VND',
            ];
        }
        $data['variantPrices'] = $variantPrices;

        return $data;
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->businessProductRepository->createQueryBuilder('e')
            ->andWhere('e.status = 1');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        return array_map(function (BusinessProduct $item) {
            return [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }, $result['collection']);
    }

    public function findById(int $id): array
    {
        $item = $this->businessProductRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $data = $item->jsonSerialize();
        $data['variants'] = $this->getVariantsData($item);

        return $data;
    }

    public function create(BusinessProductDTO $dto): array
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $item = new BusinessProduct();
            $this->mapProduct($item, $dto);
            $this->entityManager->persist($item);

            $this->saveVariants($item, $dto->variants ?? [], $dto->targetProfitMargin);

            $this->entityManager->flush();
            $conn->commit();

            return $this->findById((int) $item->getId());
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function update(int $id, BusinessProductDTO $dto): array
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $item = $this->businessProductRepository->find($id);

            if (!$item) {
                throw new \Exception(t('error.not_found'));
            }

            $this->mapProduct($item, $dto);
            $this->replaceVariants($item, $dto->variants ?? [], $dto->targetProfitMargin);

            $this->entityManager->flush();
            $conn->commit();

            return $this->findById((int) $item->getId());
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $item = $this->businessProductRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        foreach ($this->variantRepository->findBy(['businessProduct' => $item]) as $variant) {
            $this->deleteVariant($variant);
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function pricePreview(array $data): array
    {
        $totalCost = 0.0;
        $targetProfitMargin = 0.0;

        foreach ($data as $item) {
            if (!isset($item['merchandiseId']) || !isset($item['unitId'])) {
                continue;
            }

            $unitPrice = $this->merchandiseService->getPriceByUnitId(
                (int) $item['merchandiseId'],
                (int) $item['unitId']
            );
            $quantity = (float) ($item['quantity'] ?? 0.0);
            $wasteRate = (float) ($item['wasteRate'] ?? 0.0);
            $actualQty = $quantity * (1.0 + ($wasteRate / 100.0));
            $totalCost += $unitPrice * $actualQty;

            if (isset($item['targetProfitMargin'])) {
                $targetProfitMargin = (float) $item['targetProfitMargin'];
            }
        }

        if ($targetProfitMargin < 100.0 && $targetProfitMargin >= 0.0) {
            $suggestedPrice = $totalCost / (1 - $targetProfitMargin / 100.0);
        } else {
            $suggestedPrice = $totalCost;
        }

        return [
            'totalCost' => round($totalCost),
            'suggestedPrice' => round($suggestedPrice),
        ];
    }

    private function mapProduct(BusinessProduct $item, BusinessProductDTO $dto): void
    {
        $category = $this->categoryRepository->find($dto->categoryId);

        $item->setCode($dto->code);
        $item->setName($dto->name);
        $item->setCategory($category);
        $item->setTargetProfitMargin(
            $dto->targetProfitMargin !== null
                ? sprintf('%.2f', (float) $dto->targetProfitMargin)
                : null
        );
        $item->setDescription($dto->description);
        $item->setNotes($dto->notes);
        $item->setImageUrl($dto->imageUrl);
        $item->setStatus($dto->status ?? 1);
        $item->setSortOrder($dto->sortOrder ?? 0);
    }

    /**
     * @param BusinessProductVariantDTO[] $variants
     */
    private function replaceVariants(BusinessProduct $product, array $variants, string|int|float|null $targetProfitMargin): void
    {
        foreach ($this->variantRepository->findBy(['businessProduct' => $product]) as $oldVariant) {
            $this->deleteVariant($oldVariant);
        }

        $this->entityManager->flush();

        $this->saveVariants($product, $variants, $targetProfitMargin);
    }

    /**
     * @param BusinessProductVariantDTO[] $variants
     */
    private function saveVariants(BusinessProduct $product, array $variants, string|int|float|null $targetProfitMargin): void
    {
        if ($variants === []) {
            throw new \Exception('Variants are required');
        }

        $firstVariant = null;
        $defaultSet = false;

        foreach ($variants as $index => $variantDto) {
            /** @var BusinessProductVariantDTO $variantDto */
            // Chỉ giữ 1 isDefault=true (lấy cái đầu tiên)
            $isDefault = (bool) $variantDto->isDefault && !$defaultSet;
            if ($isDefault) {
                $defaultSet = true;
            }

            $variant = $this->saveVariant(
                $product,
                $variantDto,
                $index,
                $targetProfitMargin,
                $isDefault
            );

            if ($firstVariant === null) {
                $firstVariant = $variant;
            }
        }

        if (!$defaultSet && $firstVariant !== null) {
            $firstVariant->setIsDefault(true);
        }
    }

    private function saveVariant(
        BusinessProduct $product,
        BusinessProductVariantDTO $dto,
        int $index,
        string|int|float|null $targetProfitMargin,
        bool $isDefault
    ): BusinessProductVariant {
        $unit = $this->unitRepository->find($dto->unitId);
        if (!$unit) {
            throw new \Exception(t('error.not_found'));
        }

        $priceConfig = $dto->priceConfig;
        if (!$priceConfig) {
            throw new \Exception('Price config is required');
        }

        $sellingPrice = (float) $priceConfig->price;
        $currency = $priceConfig->currency ?: ($dto->currency ?: 'VND');
        $margin = $targetProfitMargin;

        $variant = new BusinessProductVariant();
        $variant->setBusinessProduct($product);
        $variant->setCode($dto->code);
        $variant->setName($dto->name);
        $variant->setUnit($unit);
        $variant->setBarcode($dto->barcode);
        $variant->setIsDefault($isDefault);
        $variant->setStatus($dto->status ?? 1);
        $variant->setSortOrder($dto->sortOrder ?? $index);
        $variant->setCurrency($currency);
        $variant->setSellingPrice(sprintf('%.2f', $sellingPrice));
        $variant->setTargetProfitMarginSnapshot(sprintf('%.2f', $margin));

        $this->entityManager->persist($variant);

        $costSnapshot = $this->saveRecipe($variant, $dto->recipe, $margin);
        $variant->setCostPriceSnapshot(sprintf('%.2f', $costSnapshot['totalCost']));
        $variant->setSuggestedPriceSnapshot(sprintf('%.2f', $costSnapshot['suggestedPrice']));

        $this->savePriceConfig($variant, $priceConfig, $currency);

        return $variant;
    }

    private function saveRecipe(
        BusinessProductVariant $variant,
        ?BusinessProductVariantRecipeDTO $recipeDto,
        float $targetProfitMargin
    ): array {
        if (!$recipeDto || empty($recipeDto->items)) {
            throw new \Exception('Recipe items are required');
        }

        $recipe = new BusinessProductVariantRecipe();
        $recipe->setVariant($variant);
        $recipe->setVersion((int) ($recipeDto->version ?? 1));
        $recipe->setIsActive($recipeDto->isActive ?? true);
        $recipe->setStatus($recipeDto->status ?? 1);
        $recipe->setNotes($recipeDto->notes);
        $recipe->setTargetProfitMarginSnapshot(sprintf('%.2f', $targetProfitMargin));

        $this->entityManager->persist($recipe);

        $totalCost = 0.0;
        $seenFinishedProductIds = [];

        foreach ($recipeDto->items as $index => $itemDto) {
            /** @var BusinessProductVariantRecipeItemDTO $itemDto */
            $finishedProductId = (int) $itemDto->finishedProductId;

            if (in_array($finishedProductId, $seenFinishedProductIds, true)) {
                throw new \Exception(t('recipe_error.ingredient_duplicate', ['%index%' => $index + 1]));
            }
            $seenFinishedProductIds[] = $finishedProductId;

            $finishedProduct = $this->merchandiseRepository->find($finishedProductId);
            if (!$finishedProduct) {
                throw new \Exception(t('error.not_found'));
            }

            $unit = $this->unitRepository->find($itemDto->unitId);
            if (!$unit) {
                throw new \Exception(t('error.not_found'));
            }

            $qty = (float) ($itemDto->quantity ?? 0);
            if ($qty <= 0) {
                throw new \Exception('Quantity must be > 0');
            }

            $wasteRate = (float) ($itemDto->wasteRate ?? 0);
            $actualQty = $qty * (1.0 + ($wasteRate / 100.0));

            $merchandiseUnit = $this->merchandiseUnitRepository->findOneBy([
                'merchandise' => $finishedProduct,
                'unit' => $unit,
            ]);
            $factor = $merchandiseUnit?->getFactorToBase() ?? '1.0000';

            $unitCost = $this->merchandiseService->getPriceByUnitId($finishedProductId, (int) $itemDto->unitId);
            $lineCost = $actualQty * $unitCost;
            $totalCost += $lineCost;

            $recipeItem = new BusinessProductVariantRecipeItem();
            $recipeItem->setRecipe($recipe);
            $recipeItem->setFinishedProduct($finishedProduct);
            $recipeItem->setQuantity(sprintf('%.4f', $qty));
            $recipeItem->setUnit($unit);
            $recipeItem->setFactorToBaseSnapshot(sprintf('%.4f', (float) $factor));
            $recipeItem->setBaseQuantitySnapshot(sprintf('%.4f', $qty * (float) $factor));
            $recipeItem->setUnitCostSnapshot(sprintf('%.4f', $unitCost));
            $recipeItem->setLineCostSnapshot(sprintf('%.2f', $lineCost));
            $recipeItem->setWasteRate(sprintf('%.2f', $wasteRate));
            $recipeItem->setSortOrder((int) ($itemDto->sortOrder ?? $index));
            $recipeItem->setNotes($itemDto->notes);

            $this->entityManager->persist($recipeItem);
            $recipe->addItem($recipeItem);
        }

        if ($targetProfitMargin <= 100.0 && $targetProfitMargin >= 0.0) {
            $suggestedPrice = $totalCost / (1 - $targetProfitMargin / 100.0);
        } else {
            $suggestedPrice = $totalCost;
        }

        $recipe->setTotalCostSnapshot(sprintf('%.2f', $totalCost));
        $recipe->setSuggestedPriceSnapshot(sprintf('%.2f', $suggestedPrice));

        return [
            'totalCost' => $totalCost,
            'suggestedPrice' => $suggestedPrice,
        ];
    }

    private function savePriceConfig(
        BusinessProductVariant $variant,
        BusinessProductVariantPriceDTO $dto,
        string $currency
    ): void {
        $price = new BusinessProductVariantPrice();
        $price->setVariant($variant);
        $price->setPrice(sprintf('%.2f', (float) $dto->price));
        $price->setCurrency($dto->currency ?: $currency);
        $price->setEffectiveFrom($this->parseDate($dto->effectiveFrom));
        $price->setEffectiveTo($this->parseDate($dto->effectiveTo));
        $price->setIsCurrent($dto->isCurrent ?? true);
        $price->setNote($dto->note);

        $this->entityManager->persist($price);
    }

    private function deleteVariant(BusinessProductVariant $variant): void
    {
        foreach ($this->recipeRepository->findBy(['variant' => $variant]) as $recipe) {
            foreach ($recipe->getItems()->toArray() as $item) {
                $this->entityManager->remove($item);
            }
            $this->entityManager->remove($recipe);
        }

        foreach ($this->priceRepository->findBy(['variant' => $variant]) as $price) {
            $this->entityManager->remove($price);
        }

        $this->entityManager->remove($variant);
    }

    private function getVariantsData(BusinessProduct $product): array
    {
        $variants = $this->variantRepository->findBy(
            ['businessProduct' => $product],
            ['sortOrder' => 'ASC', 'id' => 'ASC']
        );

        return array_map(function (BusinessProductVariant $variant) {
            $recipe = $this->recipeRepository->findOneBy(
                ['variant' => $variant, 'isActive' => true],
                ['version' => 'DESC', 'id' => 'DESC']
            ) ?? $this->recipeRepository->findOneBy(
                ['variant' => $variant],
                ['version' => 'DESC', 'id' => 'DESC']
            );

            $price = $this->priceRepository->findOneBy(
                ['variant' => $variant, 'isCurrent' => true],
                ['id' => 'DESC']
            ) ?? $this->priceRepository->findOneBy(
                ['variant' => $variant],
                ['id' => 'DESC']
            );

            $items = [];
            if ($recipe) {
                foreach ($recipe->getItems() as $item) {
                    $items[] = [
                        'id' => $item->getId(),
                        'finishedProductId' => $item->getFinishedProduct()?->getId(),
                        'finishedProduct' => $item->getFinishedProduct()?->jsonSerialize(),
                        'quantity' => formatDecimal($item->getQuantity()),
                        'unitId' => $item->getUnit()?->getId(),
                        'unit' => $item->getUnit()?->jsonSerialize(),
                        'wasteRate' => formatDecimal($item->getWasteRate()),
                        'notes' => $item->getNotes(),
                        'sortOrder' => $item->getSortOrder(),
                        'unitCostSnapshot' => formatDecimal($item->getUnitCostSnapshot()),
                        'lineCostSnapshot' => formatDecimal($item->getLineCostSnapshot()),
                    ];
                }
            }

            return [
                'id' => $variant->getId(),
                'code' => $variant->getCode(),
                'name' => $variant->getName(),
                'unitId' => $variant->getUnit()?->getId(),
                'unit' => $variant->getUnit()?->jsonSerialize(),
                'barcode' => $variant->getBarcode(),
                'sellingPrice' => formatDecimal($variant->getSellingPrice()),
                'suggestedPriceSnapshot' => formatDecimal($variant->getSuggestedPriceSnapshot()),
                'costPriceSnapshot' => formatDecimal($variant->getCostPriceSnapshot()),
                'targetProfitMarginSnapshot' => formatDecimal($variant->getTargetProfitMarginSnapshot()),
                'currency' => $variant->getCurrency(),
                'isDefault' => $variant->isDefault(),
                'status' => $variant->getStatus(),
                'sortOrder' => $variant->getSortOrder(),
                'recipe' => $recipe ? [
                    'id' => $recipe->getId(),
                    'version' => $recipe->getVersion(),
                    'totalCostSnapshot' => formatDecimal($recipe->getTotalCostSnapshot()),
                    'suggestedPriceSnapshot' => formatDecimal($recipe->getSuggestedPriceSnapshot()),
                    'targetProfitMarginSnapshot' => formatDecimal($recipe->getTargetProfitMarginSnapshot()),
                    'isActive' => $recipe->isActive(),
                    'status' => $recipe->getStatus(),
                    'notes' => $recipe->getNotes(),
                    'items' => $items,
                ] : ['items' => []],
                'priceConfig' => $price ? [
                    'id' => $price->getId(),
                    'price' => formatDecimal($price->getPrice()),
                    'currency' => $price->getCurrency(),
                    'effectiveFrom' => $price->getEffectiveFrom()?->format('Y-m-d'),
                    'effectiveTo' => $price->getEffectiveTo()?->format('Y-m-d'),
                    'isCurrent' => $price->isCurrent(),
                    'note' => $price->getNote(),
                ] : null,
            ];
        }, $variants);
    }

    private function parseDate(?string $value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
