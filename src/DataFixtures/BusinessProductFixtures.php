<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BusinessProduct;
use App\Entity\BusinessProductVariant;
use App\Entity\BusinessProductVariantPrice;
use App\Entity\BusinessProductVariantRecipe;
use App\Entity\BusinessProductVariantRecipeItem;
use App\Entity\Category;
use App\Entity\Merchandise;
use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BusinessProductFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['business-product'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixture::class,
            UnitFixture::class,
            MerchandiseFinishedProductFixtures::class,
            MerchandiseIngredientFixtures::class,
        ];
    }

    private array $unitCache = [];
    private array $merchandiseCache = [];
    private array $categoryCache = [];

    public function load(ObjectManager $manager): void
    {
        $this->warmCaches($manager);

        $products = $this->getProductsData();

        foreach ($products as $pData) {
            $product = $this->findProductByCode($manager, $pData['code']) ?? new BusinessProduct();
            $this->mapProduct($product, $pData);
            $manager->persist($product);

            // Xoá variant cũ trước khi thêm mới
            if ($product->getId() !== null) {
                $this->removeOldVariants($manager, $product);
                $manager->flush();
            } else {
                $manager->flush(); // flush để sinh id
            }

            foreach ($pData['variants'] as $vIdx => $vData) {
                $this->createVariant($product, $vData, $vIdx, $manager);
            }

            $manager->flush();
        }
    }

    private function warmCaches(ObjectManager $manager): void
    {
        $units = $manager->getRepository(Unit::class)->findAll();
        foreach ($units as $u) {
            $this->unitCache[$u->getCode()] = $u;
        }

        $merchandises = $manager->getRepository(Merchandise::class)->findAll();
        foreach ($merchandises as $m) {
            $this->merchandiseCache[$m->getCode()] = $m;
        }

        $categories = $manager->getRepository(Category::class)->findBy(['type' => 'business_product']);
        foreach ($categories as $c) {
            $this->categoryCache[$c->getSlug()] = $c;
        }
    }

    private function findProductByCode(ObjectManager $manager, string $code): ?BusinessProduct
    {
        return $manager->getRepository(BusinessProduct::class)->findOneBy(['code' => $code]);
    }

    private function mapProduct(BusinessProduct $product, array $data): void
    {
        $category = $this->categoryCache[$data['category_slug']] ?? null;

        $product->setCode($data['code']);
        $product->setName($data['name']);
        $product->setCategory($category);
        $product->setTargetProfitMargin($data['target_profit_margin']);
        $product->setDescription($data['description'] ?? null);
        $product->setNotes($data['notes'] ?? null);
        $product->setStatus($data['status'] ?? 1);
        $product->setSortOrder($data['sort_order'] ?? 0);
    }

    private function removeOldVariants(ObjectManager $manager, BusinessProduct $product): void
    {
        $variantRepo = $manager->getRepository(BusinessProductVariant::class);
        $oldVariants = $variantRepo->findBy(['businessProduct' => $product]);

        foreach ($oldVariants as $oldVariant) {
            $recipeRepo = $manager->getRepository(BusinessProductVariantRecipe::class);
            $recipes = $recipeRepo->findBy(['variant' => $oldVariant]);
            foreach ($recipes as $recipe) {
                foreach ($recipe->getItems()->toArray() as $item) {
                    $manager->remove($item);
                }
                $manager->remove($recipe);
            }

            $priceRepo = $manager->getRepository(BusinessProductVariantPrice::class);
            $prices = $priceRepo->findBy(['variant' => $oldVariant]);
            foreach ($prices as $price) {
                $manager->remove($price);
            }

            $manager->remove($oldVariant);
        }
    }

    private function createVariant(
        BusinessProduct $product,
        array $data,
        int $index,
        ObjectManager $manager
    ): void {
        $unit = $this->unitCache[$data['unit_code']] ?? null;
        if (!$unit) {
            throw new \RuntimeException("Unit not found: {$data['unit_code']}");
        }

        $variant = new BusinessProductVariant();
        $variant->setBusinessProduct($product);
        $variant->setCode($data['code']);
        $variant->setName($data['name']);
        $variant->setUnit($unit);
        $variant->setBarcode($data['barcode'] ?? null);
        $variant->setIsDefault($data['is_default'] ?? false);
        $variant->setStatus($data['status'] ?? 1);
        $variant->setSortOrder($data['sort_order'] ?? $index);
        $variant->setCurrency($data['currency'] ?? 'VND');

        $margin = $data['target_profit_margin_snapshot']
            ?? $product->getTargetProfitMargin()
            ?? '0.00';

        $variant->setSellingPrice($data['selling_price'] ?? '0.00');
        $variant->setTargetProfitMarginSnapshot(sprintf('%.2f', (float) $margin));
        $variant->setCostPriceSnapshot('0.00');
        $variant->setSuggestedPriceSnapshot('0.00');

        $manager->persist($variant);

        // Tạo recipe
        if (!empty($data['recipe']['items'])) {
            $this->createRecipe($variant, $data['recipe'], $margin, $manager);
        }

        // Tạo price config
        if (!empty($data['price_config'])) {
            $this->createPriceConfig($variant, $data['price_config'], $manager);
        }
    }

    private function createRecipe(
        BusinessProductVariant $variant,
        array $recipeData,
        string $margin,
        ObjectManager $manager
    ): void {
        $recipe = new BusinessProductVariantRecipe();
        $recipe->setVariant($variant);
        $recipe->setVersion($recipeData['version'] ?? 1);
        $recipe->setIsActive($recipeData['is_active'] ?? true);
        $recipe->setStatus($recipeData['status'] ?? 1);
        $recipe->setNotes($recipeData['notes'] ?? null);
        $recipe->setTargetProfitMarginSnapshot(sprintf('%.2f', (float) $margin));
        $recipe->setTotalCostSnapshot('0.00');
        $recipe->setSuggestedPriceSnapshot('0.00');

        $manager->persist($recipe);

        foreach ($recipeData['items'] as $iIdx => $itemData) {
            $merchandise = $this->merchandiseCache[$itemData['merchandise_code']] ?? null;
            if (!$merchandise) {
                throw new \RuntimeException("Merchandise not found: {$itemData['merchandise_code']}");
            }

            $unit = $this->unitCache[$itemData['unit_code']] ?? null;
            if (!$unit) {
                throw new \RuntimeException("Unit not found: {$itemData['unit_code']}");
            }

            $qty = (float) ($itemData['quantity'] ?? 0);
            $wasteRate = (float) ($itemData['waste_rate'] ?? 0);

            $recipeItem = new BusinessProductVariantRecipeItem();
            $recipeItem->setRecipe($recipe);
            $recipeItem->setFinishedProduct($merchandise);
            $recipeItem->setQuantity(sprintf('%.4f', $qty));
            $recipeItem->setUnit($unit);
            $recipeItem->setWasteRate(sprintf('%.2f', $wasteRate));
            $recipeItem->setSortOrder($itemData['sort_order'] ?? $iIdx);
            $recipeItem->setNotes($itemData['notes'] ?? null);

            $recipeItem->setFactorToBaseSnapshot('1.0000');
            $recipeItem->setBaseQuantitySnapshot(sprintf('%.4f', $qty));
            $recipeItem->setUnitCostSnapshot('0.0000');
            $recipeItem->setLineCostSnapshot('0.00');

            $manager->persist($recipeItem);
            $recipe->addItem($recipeItem);
        }
    }

    private function createPriceConfig(
        BusinessProductVariant $variant,
        array $priceData,
        ObjectManager $manager
    ): void {
        $price = new BusinessProductVariantPrice();
        $price->setVariant($variant);
        $price->setPrice(sprintf('%.2f', (float) $priceData['price']));
        $price->setCurrency($priceData['currency'] ?? 'VND');
        $price->setEffectiveFrom(
            isset($priceData['effective_from'])
                ? new \DateTime($priceData['effective_from'])
                : new \DateTime()
        );
        $price->setEffectiveTo(
            isset($priceData['effective_to'])
                ? new \DateTime($priceData['effective_to'])
                : null
        );
        $price->setIsCurrent($priceData['is_current'] ?? true);
        $price->setNote($priceData['note'] ?? null);

        $manager->persist($price);
    }

    // ====================================================================
    // Dữ liệu Sản phẩm kinh doanh
    //
    // Thành phẩm sản xuất nội bộ (FP-PROD-*):
    //   FP-PROD-001: Xúc xích heo         FP-PROD-002: Bò viên
    //   FP-PROD-003: Chả cá               FP-PROD-004: Nước lẩu mì cay
    //   FP-PROD-005: Sốt mì trộn cay
    //
    // Nguyên liệu thô (ING-*): dùng làm topping, gia vị, rau ăn kèm
    // ====================================================================
    private function getProductsData(): array
    {
        // ================================================================
        // Công thức nền (base) dùng chung - để tránh lặp code
        // ================================================================

        // Base mì cay: mì gói + nước lẩu + rau + gia vị nền
        $baseSpicyNoodle = [
            ['merchandise_code' => 'ING-001', 'quantity' => '1.0000', 'unit_code' => 'GOI', 'waste_rate' => '0.00', 'notes' => 'Mì gói Koreno'],
            ['merchandise_code' => 'FP-PROD-004', 'quantity' => '0.3000', 'unit_code' => 'L', 'waste_rate' => '0.00', 'notes' => 'Nước lẩu mì cay'],
            ['merchandise_code' => 'ING-003', 'quantity' => '50.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Kim chi cải thảo'],
            ['merchandise_code' => 'ING-035', 'quantity' => '30.0000', 'unit_code' => 'G', 'waste_rate' => '2.00', 'notes' => 'Nấm kim châm'],
            ['merchandise_code' => 'ING-037', 'quantity' => '40.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Cải thìa'],
            ['merchandise_code' => 'ING-038', 'quantity' => '30.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Cải thảo Đà Lạt'],
            ['merchandise_code' => 'ING-032', 'quantity' => '10.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt bột Hàn Quốc'],
            ['merchandise_code' => 'ING-031', 'quantity' => '5.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt hiểm xay'],
        ];

        // Base mì trộn: mì gói + sốt mì trộn + rau + gia vị nền
        $baseMixedNoodle = [
            ['merchandise_code' => 'ING-001', 'quantity' => '1.0000', 'unit_code' => 'GOI', 'waste_rate' => '0.00', 'notes' => 'Mì gói Koreno'],
            ['merchandise_code' => 'FP-PROD-005', 'quantity' => '0.2000', 'unit_code' => 'L', 'waste_rate' => '0.00', 'notes' => 'Sốt mì trộn cay'],
            ['merchandise_code' => 'ING-015', 'quantity' => '10.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Hành phi'],
            ['merchandise_code' => 'ING-016', 'quantity' => '15.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Mỡ hành'],
            ['merchandise_code' => 'ING-037', 'quantity' => '30.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Cải thìa'],
            ['merchandise_code' => 'ING-032', 'quantity' => '10.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt bột Hàn Quốc'],
        ];

        // Topping thịt bò
        $toppingBeef = [
            ['merchandise_code' => 'ING-004', 'quantity' => '100.0000', 'unit_code' => 'G', 'waste_rate' => '5.00', 'notes' => 'Ba chỉ bò Mỹ'],
            ['merchandise_code' => 'ING-033', 'quantity' => '60.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Thịt nạc vai bò'],
        ];

        // Topping hải sản
        $toppingSeafood = [
            ['merchandise_code' => 'ING-005', 'quantity' => '70.0000', 'unit_code' => 'G', 'waste_rate' => '8.00', 'notes' => 'Tôm tươi'],
            ['merchandise_code' => 'ING-006', 'quantity' => '70.0000', 'unit_code' => 'G', 'waste_rate' => '8.00', 'notes' => 'Mực ống'],
            ['merchandise_code' => 'ING-007', 'quantity' => '5.0000', 'unit_code' => 'VIEN', 'waste_rate' => '0.00', 'notes' => 'Cá viên'],
        ];

        // Topping bò viên (thành phẩm)
        $toppingBoVien = [
            ['merchandise_code' => 'FP-PROD-002', 'quantity' => '80.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Bò viên'],
        ];

        // Topping chả cá (thành phẩm)
        $toppingChaCa = [
            ['merchandise_code' => 'FP-PROD-003', 'quantity' => '80.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Chả cá'],
        ];

        // Topping xúc xích (thành phẩm)
        $toppingXucXich = [
            ['merchandise_code' => 'FP-PROD-001', 'quantity' => '100.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Xúc xích heo'],
        ];

        // ================================================================
        // Hàm scale recipe items theo tỉ lệ
        // ================================================================
        $scaleItems = function (array $items, float $ratio): array {
            if ($ratio === 1.0) {
                return $items;
            }
            return array_map(function (array $item) use ($ratio): array {
                $new = $item;
                $new['quantity'] = sprintf('%.4f', (float) $item['quantity'] * $ratio);
                return $new;
            }, $items);
        };

        // Hàm merge mảng recipe items (base + toppings)
        $mergeItems = function (array ...$arrays): array {
            $result = [];
            $sortOrder = 0;
            foreach ($arrays as $arr) {
                foreach ($arr as $item) {
                    $item['sort_order'] = $sortOrder++;
                    $result[] = $item;
                }
            }
            return $result;
        };

        // ================================================================
        // DỮ LIỆU SẢN PHẨM
        // ================================================================

        return [
            // ============================================================
            // 1. Mì cay bò Mỹ - 4 variants
            // ============================================================
            [
                'code' => 'BP-001',
                'name' => 'Mì cay bò Mỹ',
                'category_slug' => 'mi-cay-bo-my',
                'target_profit_margin' => '35.00',
                'description' => '<p>Mì cay bò Mỹ thượng hạng với thịt bò Mỹ nhập khẩu, nước lẩu mì cay đặc chế, ăn kèm rau tươi.</p>',
                'notes' => 'Sản phẩm best-seller',
                'status' => 1,
                'sort_order' => 1,
                'variants' => [
                    [
                        'code' => 'BP-001-V1',
                        'name' => 'Phần nhỏ',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001801001',
                        'is_default' => false,
                        'selling_price' => '49000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần nhỏ (70% định lượng chuẩn)',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 0.7),
                                $scaleItems($toppingBeef, 0.7),
                            ),
                        ],
                        'price_config' => ['price' => '49000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần nhỏ'],
                    ],
                    [
                        'code' => 'BP-001-V2',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001801002',
                        'is_default' => true,
                        'selling_price' => '65000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn',
                            'items' => $mergeItems($baseSpicyNoodle, $toppingBeef),
                        ],
                        'price_config' => ['price' => '65000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-001-V3',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001801003',
                        'is_default' => false,
                        'selling_price' => '89000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng chuẩn)',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.5),
                                $scaleItems($toppingBeef, 1.5),
                            ),
                        ],
                        'price_config' => ['price' => '89000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-001-V4',
                        'name' => 'Phần đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001801004',
                        'is_default' => false,
                        'selling_price' => '99000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần đặc biệt - thêm bò viên + xúc xích',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.2),
                                $toppingBeef,
                                $scaleItems($toppingBoVien, 0.8),
                                $scaleItems($toppingXucXich, 0.6),
                            ),
                        ],
                        'price_config' => ['price' => '99000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 2. Mì cay hải sản - 3 variants
            // ============================================================
            [
                'code' => 'BP-002',
                'name' => 'Mì cay hải sản',
                'category_slug' => 'mi-cay-hai-san',
                'target_profit_margin' => '32.00',
                'description' => '<p>Mì cay hải sản tươi ngon với tôm, mực, cá viên và nước lẩu mì cay đặc chế.</p>',
                'notes' => null,
                'status' => 1,
                'sort_order' => 2,
                'variants' => [
                    [
                        'code' => 'BP-002-V1',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001802001',
                        'is_default' => true,
                        'selling_price' => '69000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn',
                            'items' => $mergeItems($baseSpicyNoodle, $toppingSeafood),
                        ],
                        'price_config' => ['price' => '69000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-002-V2',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001802002',
                        'is_default' => false,
                        'selling_price' => '95000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.5),
                                $scaleItems($toppingSeafood, 1.5),
                            ),
                        ],
                        'price_config' => ['price' => '95000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-002-V3',
                        'name' => 'Phần đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001802003',
                        'is_default' => false,
                        'selling_price' => '105000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần đặc biệt - thêm bò viên + chả cá',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.2),
                                $toppingSeafood,
                                $scaleItems($toppingBoVien, 0.7),
                                $scaleItems($toppingChaCa, 0.7),
                            ),
                        ],
                        'price_config' => ['price' => '105000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 3. Mì cay thập cẩm - 4 variants
            // ============================================================
            [
                'code' => 'BP-003',
                'name' => 'Mì cay thập cẩm',
                'category_slug' => 'mi-cay-thap-cam',
                'target_profit_margin' => '32.00',
                'description' => '<p>Mì cay thập cẩm đầy đủ topping: bò Mỹ, tôm, mực, bò viên, chả cá, xúc xích - combo đầy đủ nhất.</p>',
                'notes' => 'Combo đầy đủ nhất - signature dish',
                'status' => 1,
                'sort_order' => 3,
                'variants' => [
                    [
                        'code' => 'BP-003-V1',
                        'name' => 'Phần nhỏ',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001803001',
                        'is_default' => false,
                        'selling_price' => '79000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần nhỏ (70% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 0.7),
                                $scaleItems($toppingBeef, 0.6),
                                $scaleItems($toppingSeafood, 0.6),
                                $scaleItems($toppingBoVien, 0.5),
                                $scaleItems($toppingChaCa, 0.5),
                                $scaleItems($toppingXucXich, 0.5),
                            ),
                        ],
                        'price_config' => ['price' => '79000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần nhỏ'],
                    ],
                    [
                        'code' => 'BP-003-V2',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001803002',
                        'is_default' => true,
                        'selling_price' => '109000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn đầy đủ topping',
                            'items' => $mergeItems(
                                $baseSpicyNoodle,
                                $toppingBeef,
                                $toppingSeafood,
                                $toppingBoVien,
                                $toppingChaCa,
                                $toppingXucXich,
                            ),
                        ],
                        'price_config' => ['price' => '109000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-003-V3',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001803003',
                        'is_default' => false,
                        'selling_price' => '149000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.5),
                                $scaleItems($toppingBeef, 1.2),
                                $scaleItems($toppingSeafood, 1.2),
                                $scaleItems($toppingBoVien, 1.2),
                                $scaleItems($toppingChaCa, 1.2),
                                $scaleItems($toppingXucXich, 1.0),
                            ),
                        ],
                        'price_config' => ['price' => '149000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-003-V4',
                        'name' => 'Phần siêu đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001803004',
                        'is_default' => false,
                        'selling_price' => '139000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần siêu đặc biệt - tăng 30% định lượng tất cả topping',
                            'items' => $mergeItems(
                                $scaleItems($baseSpicyNoodle, 1.3),
                                $scaleItems($toppingBeef, 1.3),
                                $scaleItems($toppingSeafood, 1.3),
                                $scaleItems($toppingBoVien, 1.2),
                                $scaleItems($toppingChaCa, 1.2),
                                $scaleItems($toppingXucXich, 1.2),
                            ),
                        ],
                        'price_config' => ['price' => '139000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần siêu đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 4. Mì trộn chả cá - 3 variants
            // ============================================================
            [
                'code' => 'BP-004',
                'name' => 'Mì trộn chả cá',
                'category_slug' => 'mi-tron-cha-ca',
                'target_profit_margin' => '35.00',
                'description' => '<p>Mì trộn chả cá thơm ngon với sốt mì trộn cay đặc trưng, hành phi và mỡ hành.</p>',
                'notes' => null,
                'status' => 1,
                'sort_order' => 4,
                'variants' => [
                    [
                        'code' => 'BP-004-V1',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001804001',
                        'is_default' => true,
                        'selling_price' => '49000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn',
                            'items' => $mergeItems($baseMixedNoodle, $toppingChaCa),
                        ],
                        'price_config' => ['price' => '49000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-004-V2',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001804002',
                        'is_default' => false,
                        'selling_price' => '69000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.5),
                                $scaleItems($toppingChaCa, 1.5),
                            ),
                        ],
                        'price_config' => ['price' => '69000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-004-V3',
                        'name' => 'Phần đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001804003',
                        'is_default' => false,
                        'selling_price' => '75000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần đặc biệt - thêm bò viên + xúc xích',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.2),
                                $toppingChaCa,
                                $scaleItems($toppingBoVien, 0.7),
                                $scaleItems($toppingXucXich, 0.5),
                            ),
                        ],
                        'price_config' => ['price' => '75000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 5. Mì trộn xúc xích - 3 variants
            // ============================================================
            [
                'code' => 'BP-005',
                'name' => 'Mì trộn xúc xích',
                'category_slug' => 'mi-tron-xuc-xich',
                'target_profit_margin' => '35.00',
                'description' => '<p>Mì trộn xúc xích heo sản xuất nội bộ, sốt mì trộn cay đậm đà, hành phi giòn thơm.</p>',
                'notes' => null,
                'status' => 1,
                'sort_order' => 5,
                'variants' => [
                    [
                        'code' => 'BP-005-V1',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001805001',
                        'is_default' => true,
                        'selling_price' => '49000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn',
                            'items' => $mergeItems($baseMixedNoodle, $toppingXucXich),
                        ],
                        'price_config' => ['price' => '49000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-005-V2',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001805002',
                        'is_default' => false,
                        'selling_price' => '69000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.5),
                                $scaleItems($toppingXucXich, 1.5),
                            ),
                        ],
                        'price_config' => ['price' => '69000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-005-V3',
                        'name' => 'Phần đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001805003',
                        'is_default' => false,
                        'selling_price' => '79000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần đặc biệt - thêm chả cá + bò viên',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.3),
                                $toppingXucXich,
                                $scaleItems($toppingChaCa, 0.7),
                                $scaleItems($toppingBoVien, 0.5),
                            ),
                        ],
                        'price_config' => ['price' => '79000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 6. Mì trộn đặc biệt - 4 variants
            // ============================================================
            [
                'code' => 'BP-006',
                'name' => 'Mì trộn đặc biệt',
                'category_slug' => 'mi-tron-dac-biet',
                'target_profit_margin' => '32.00',
                'description' => '<p>Mì trộn đặc biệt với đầy đủ topping: xúc xích, bò viên, chả cá - combo mì trộn hoàn hảo.</p>',
                'notes' => 'Combo mì trộn đầy đủ nhất',
                'status' => 1,
                'sort_order' => 6,
                'variants' => [
                    [
                        'code' => 'BP-006-V1',
                        'name' => 'Phần nhỏ',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001806001',
                        'is_default' => false,
                        'selling_price' => '55000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần nhỏ (70% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 0.7),
                                $scaleItems($toppingXucXich, 0.6),
                                $scaleItems($toppingBoVien, 0.6),
                                $scaleItems($toppingChaCa, 0.6),
                            ),
                        ],
                        'price_config' => ['price' => '55000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần nhỏ'],
                    ],
                    [
                        'code' => 'BP-006-V2',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001806002',
                        'is_default' => true,
                        'selling_price' => '75000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - định lượng chuẩn đầy đủ topping',
                            'items' => $mergeItems($baseMixedNoodle, $toppingXucXich, $toppingBoVien, $toppingChaCa),
                        ],
                        'price_config' => ['price' => '75000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-006-V3',
                        'name' => 'Tô lớn',
                        'unit_code' => 'TO',
                        'barcode' => '8935001806003',
                        'is_default' => false,
                        'selling_price' => '99000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Tô lớn (150% định lượng)',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.5),
                                $scaleItems($toppingXucXich, 1.3),
                                $scaleItems($toppingBoVien, 1.3),
                                $scaleItems($toppingChaCa, 1.3),
                            ),
                        ],
                        'price_config' => ['price' => '99000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá tô lớn'],
                    ],
                    [
                        'code' => 'BP-006-V4',
                        'name' => 'Phần siêu đặc biệt',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001806004',
                        'is_default' => false,
                        'selling_price' => '119000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần siêu đặc biệt - tăng 50% tất cả topping + thêm thịt bò',
                            'items' => $mergeItems(
                                $scaleItems($baseMixedNoodle, 1.5),
                                $scaleItems($toppingXucXich, 1.5),
                                $scaleItems($toppingBoVien, 1.5),
                                $scaleItems($toppingChaCa, 1.5),
                                $scaleItems($toppingBeef, 0.5),
                            ),
                        ],
                        'price_config' => ['price' => '119000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần siêu đặc biệt'],
                    ],
                ],
            ],

            // ============================================================
            // 7. Chả cá chiên - 3 variants
            // ============================================================
            [
                'code' => 'BP-007',
                'name' => 'Chả cá chiên',
                'category_slug' => 'cha-ca-chien',
                'target_profit_margin' => '42.00',
                'description' => '<p>Chả cá chiên vàng giòn, ăn kèm tương ớt. Được chế biến từ chả cá sản xuất nội bộ.</p>',
                'notes' => 'Món ăn thêm - side dish',
                'status' => 1,
                'sort_order' => 7,
                'variants' => [
                    [
                        'code' => 'BP-007-V1',
                        'name' => 'Phần nhỏ',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001807001',
                        'is_default' => false,
                        'selling_price' => '25000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần nhỏ - 100g chả cá',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-003', 'quantity' => '100.0000', 'unit_code' => 'G', 'waste_rate' => '5.00', 'notes' => 'Chả cá'],
                                ['merchandise_code' => 'ING-029', 'quantity' => '3.0000', 'unit_code' => 'ML', 'waste_rate' => '0.00', 'notes' => 'Nước mắm'],
                                ['merchandise_code' => 'ING-030', 'quantity' => '2.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Tiêu đen'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '5.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt bột Hàn Quốc'],
                            ],
                        ],
                        'price_config' => ['price' => '25000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần nhỏ'],
                    ],
                    [
                        'code' => 'BP-007-V2',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'barcode' => '8935001807002',
                        'is_default' => true,
                        'selling_price' => '35000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần vừa - 150g chả cá',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-003', 'quantity' => '150.0000', 'unit_code' => 'G', 'waste_rate' => '5.00', 'notes' => 'Chả cá'],
                                ['merchandise_code' => 'ING-029', 'quantity' => '5.0000', 'unit_code' => 'ML', 'waste_rate' => '0.00', 'notes' => 'Nước mắm'],
                                ['merchandise_code' => 'ING-030', 'quantity' => '3.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Tiêu đen'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '8.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt bột Hàn Quốc'],
                            ],
                        ],
                        'price_config' => ['price' => '35000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần vừa'],
                    ],
                    [
                        'code' => 'BP-007-V3',
                        'name' => 'Phần lớn',
                        'unit_code' => 'DIA',
                        'barcode' => '8935001807003',
                        'is_default' => false,
                        'selling_price' => '49000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => true,
                            'notes' => 'Phần lớn - 220g chả cá',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-003', 'quantity' => '220.0000', 'unit_code' => 'G', 'waste_rate' => '5.00', 'notes' => 'Chả cá'],
                                ['merchandise_code' => 'ING-029', 'quantity' => '7.0000', 'unit_code' => 'ML', 'waste_rate' => '0.00', 'notes' => 'Nước mắm'],
                                ['merchandise_code' => 'ING-030', 'quantity' => '4.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Tiêu đen'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '12.0000', 'unit_code' => 'G', 'waste_rate' => '0.00', 'notes' => 'Ớt bột Hàn Quốc'],
                            ],
                        ],
                        'price_config' => ['price' => '49000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => true, 'note' => 'Giá phần lớn'],
                    ],
                ],
            ],

            // ============================================================
            // 8. Xúc xích chiên - 3 variants (inactive)
            // ============================================================
            [
                'code' => 'BP-008',
                'name' => 'Xúc xích chiên',
                'category_slug' => 'xuc-xich-chien',
                'target_profit_margin' => '40.00',
                'description' => '<p>Xúc xích heo sản xuất nội bộ chiên giòn, ăn kèm tương ớt.</p>',
                'notes' => 'Sản phẩm đang tạm ngưng - chờ xem xét mở lại',
                'status' => 0,
                'sort_order' => 8,
                'variants' => [
                    [
                        'code' => 'BP-008-V1',
                        'name' => 'Phần nhỏ',
                        'unit_code' => 'PHAN',
                        'is_default' => false,
                        'status' => 0,
                        'selling_price' => '20000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => false,
                            'notes' => 'Phần nhỏ - công thức tạm ngưng',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-001', 'quantity' => '80.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Xúc xích heo'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '3.0000', 'unit_code' => 'G', 'waste_rate' => '0.00'],
                            ],
                        ],
                        'price_config' => ['price' => '20000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => false, 'note' => 'Giá cũ'],
                    ],
                    [
                        'code' => 'BP-008-V2',
                        'name' => 'Phần vừa',
                        'unit_code' => 'PHAN',
                        'is_default' => true,
                        'status' => 0,
                        'selling_price' => '30000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => false,
                            'notes' => 'Phần vừa - công thức tạm ngưng',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-001', 'quantity' => '120.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Xúc xích heo'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '5.0000', 'unit_code' => 'G', 'waste_rate' => '0.00'],
                            ],
                        ],
                        'price_config' => ['price' => '30000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => false, 'note' => 'Giá cũ'],
                    ],
                    [
                        'code' => 'BP-008-V3',
                        'name' => 'Phần lớn',
                        'unit_code' => 'DIA',
                        'is_default' => false,
                        'status' => 0,
                        'selling_price' => '45000',
                        'recipe' => [
                            'version' => 1,
                            'is_active' => false,
                            'notes' => 'Phần lớn - công thức tạm ngưng',
                            'items' => [
                                ['merchandise_code' => 'FP-PROD-001', 'quantity' => '180.0000', 'unit_code' => 'G', 'waste_rate' => '3.00', 'notes' => 'Xúc xích heo'],
                                ['merchandise_code' => 'ING-032', 'quantity' => '8.0000', 'unit_code' => 'G', 'waste_rate' => '0.00'],
                            ],
                        ],
                        'price_config' => ['price' => '45000', 'currency' => 'VND', 'effective_from' => '2025-01-01', 'is_current' => false, 'note' => 'Giá cũ'],
                    ],
                ],
            ],
        ];
    }
}
