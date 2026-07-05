<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DTO\RecipeDTO;
use App\DTO\RecipeItemDTO;
use App\Entity\Merchandise;
use App\Entity\Category;
use App\Entity\Provider;
use App\Entity\Unit;
use App\Service\MerchandiseService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MerchandiseFinishedProductFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private readonly MerchandiseService $merchandiseService,
    ) {}

    public static function getGroups(): array
    {
        return ['merchandise-finished-product'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixture::class,
            UnitFixture::class,
            ProviderFixture::class,
            MerchandiseIngredientFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepo = $manager->getRepository(Category::class);
        $merchandiseRepo = $manager->getRepository(Merchandise::class);
        $unitRepo = $manager->getRepository(Unit::class);
        $providerRepo = $manager->getRepository(Provider::class);

        // Lấy 1 nhà cung cấp làm mẫu
        $provider = $providerRepo->findOneBy([]);
        $providerId = $provider ? $provider->getId() : null;

        // 1. Tạo Thành phẩm nhập từ NCC
        $supplierProducts = [
            [
                'code' => 'FP-SUP-001',
                'name' => 'Bánh bao nhân thịt (Nhập khẩu)',
                'category_slug' => 'thit-heo', // Lấy category slug phù hợp
                'base_unit' => 'PHAN',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'PHAN', 'to_value' => '50.00'],
                ],
                'providers' => $providerId ? [
                    [
                        'providerId' => $providerId,
                        'unitConfigMode' => 'custom',
                        'conversions' => [
                            ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'PHAN', 'to_value' => '50.00'],
                        ],
                        'prices' => [
                            ['unit' => 'PHAN', 'price' => 15000],
                            ['unit' => 'THUNG', 'price' => 700000]
                        ]
                    ]
                ] : []
            ]
        ];

        foreach ($supplierProducts as $p) {
            $merchandise = $merchandiseRepo->findOneBy(['code' => $p['code']]) ?? new Merchandise();
            $merchandise->setCode($p['code']);
            $merchandise->setName($p['name']);
            $merchandise->setType('finished_product');
            $merchandise->setFinishedProductSource('supplier');
            $merchandise->setProfit('15.00');
            $merchandise->setStockAlertQuantity('10');
            $merchandise->setDescription("<p>Mô tả cho thành phẩm nhập khẩu <strong>{$p['name']}</strong>.</p>");
            $merchandise->setStatus(1);

            $category = $categoryRepo->findOneBy(['slug' => $p['category_slug']]);
            if ($category) {
                $merchandise->setCategory($category);
            }

            $baseUnit = $unitRepo->findOneBy(['code' => $p['base_unit']]);
            if ($baseUnit) {
                $merchandise->setBaseUnit($baseUnit);
            }

            $manager->persist($merchandise);
            $manager->flush(); // Flush để sinh id cho Merchandise

            // Lưu đơn vị quy đổi
            $conversionsData = [];
            if ($baseUnit) {
                foreach ($p['conversions'] as $c) {
                    $fromUnit = $unitRepo->findOneBy(['code' => $c['from_unit']]);
                    $toUnit = $unitRepo->findOneBy(['code' => $c['to_unit']]);
                    if ($fromUnit && $toUnit) {
                        $conversionsData[] = [
                            'fromUnitId' => $fromUnit->getId(),
                            'fromValue' => $c['from_value'],
                            'toUnitId' => $toUnit->getId(),
                            'toValue' => $c['to_value'],
                        ];
                    }
                }
                $this->merchandiseService->saveConversionsAndUnits($merchandise, $conversionsData, $baseUnit->getId());
            }

            // Lưu provider
            $providersData = [];
            foreach ($p['providers'] as $prov) {
                $pConversions = [];
                foreach ($prov['conversions'] as $c) {
                    $fromUnit = $unitRepo->findOneBy(['code' => $c['from_unit']]);
                    $toUnit = $unitRepo->findOneBy(['code' => $c['to_unit']]);
                    if ($fromUnit && $toUnit) {
                        $pConversions[] = [
                            'fromUnitId' => $fromUnit->getId(),
                            'fromValue' => $c['from_value'],
                            'toUnitId' => $toUnit->getId(),
                            'toValue' => $c['to_value'],
                        ];
                    }
                }

                $pPrices = [];
                foreach ($prov['prices'] as $pr) {
                    $u = $unitRepo->findOneBy(['code' => $pr['unit']]);
                    if ($u) {
                        $pPrices[] = [
                            'unitId' => $u->getId(),
                            'price' => $pr['price'],
                            'discountRate' => '0.00',
                            'discountAmount' => '0.00',
                            'priceAfterDiscount' => (string)$pr['price'],
                            'effectiveFrom' => date('Y-m-d'),
                            'effectiveTo' => null
                        ];
                    }
                }

                $providersData[] = [
                    'providerId' => $prov['providerId'],
                    'unitConfigMode' => $prov['unitConfigMode'],
                    'conversions' => $pConversions,
                    'prices' => $pPrices
                ];
            }
            if (!empty($providersData)) {
                $this->merchandiseService->saveProviders($merchandise, $providersData);
            }
        }

        // 2. Tạo Thành phẩm sản xuất nội bộ
        $productionProducts = [
            [
                'code' => 'FP-PROD-001',
                'name' => 'Xúc xích heo',
                'category_slug' => 'thit-heo',
                'base_unit' => 'KG',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'GOI', 'to_value' => '10.00'],
                    ['from_unit' => 'GOI', 'from_value' => '1.00', 'to_unit' => 'KG', 'to_value' => '0.50'],
                ],
                'recipe' => [
                    'notes' => 'Công thức sản xuất 1 kg Xúc xích heo',
                    'items' => [
                        ['ing_code' => 'ING-028', 'qty' => '850.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-029', 'qty' => '80.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-030', 'qty' => '15.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-031', 'qty' => '30.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-032', 'qty' => '25.0000', 'unit' => 'G', 'waste_rate' => '0.00']
                    ]
                ]
            ],
            [
                'code' => 'FP-PROD-002',
                'name' => 'Bò viên',
                'category_slug' => 'thit-bo',
                'base_unit' => 'KG',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'GOI', 'to_value' => '10.00'],
                    ['from_unit' => 'GOI', 'from_value' => '1.00', 'to_unit' => 'KG', 'to_value' => '0.50'],
                ],
                'recipe' => [
                    'notes' => 'Công thức sản xuất 1 kg Bò viên',
                    'items' => [
                        ['ing_code' => 'ING-033', 'qty' => '900.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-029', 'qty' => '60.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-030', 'qty' => '10.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-032', 'qty' => '20.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-034', 'qty' => '10.0000', 'unit' => 'ML', 'waste_rate' => '0.00']
                    ]
                ]
            ],
            [
                'code' => 'FP-PROD-003',
                'name' => 'Chả cá',
                'category_slug' => 'ca-vien',
                'base_unit' => 'KG',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'GOI', 'to_value' => '10.00'],
                    ['from_unit' => 'GOI', 'from_value' => '1.00', 'to_unit' => 'KG', 'to_value' => '0.50'],
                ],
                'recipe' => [
                    'notes' => 'Công thức sản xuất 1 kg Chả cá',
                    'items' => [
                        ['ing_code' => 'ING-007', 'qty' => '160.0000', 'unit' => 'VIEN', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-005', 'qty' => '100.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-006', 'qty' => '50.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-029', 'qty' => '30.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-030', 'qty' => '10.0000', 'unit' => 'G', 'waste_rate' => '0.00']
                    ]
                ]
            ],
            [
                'code' => 'FP-PROD-004',
                'name' => 'Nước lẩu mì cay',
                'category_slug' => 'sot-va-gia-vi-long',
                'base_unit' => 'L',
                'conversions' => [
                    ['from_unit' => 'CHAI', 'from_value' => '1.00', 'to_unit' => 'L', 'to_value' => '0.50'],
                ],
                'recipe' => [
                    'notes' => 'Công thức sản xuất 1 lít Nước lẩu mì cay',
                    'items' => [
                        ['ing_code' => 'ING-036', 'qty' => '700.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-034', 'qty' => '100.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-029', 'qty' => '80.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-031', 'qty' => '40.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-032', 'qty' => '20.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-035', 'qty' => '50.0000', 'unit' => 'G', 'waste_rate' => '0.00']
                    ]
                ]
            ],
            [
                'code' => 'FP-PROD-005',
                'name' => 'Sốt mì trộn cay',
                'category_slug' => 'sot-va-gia-vi-long',
                'base_unit' => 'L',
                'conversions' => [
                    ['from_unit' => 'CHAI', 'from_value' => '1.00', 'to_unit' => 'L', 'to_value' => '0.50'],
                ],
                'recipe' => [
                    'notes' => 'Công thức sản xuất 1 lít Sốt mì trộn cay',
                    'items' => [
                        ['ing_code' => 'ING-034', 'qty' => '500.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-029', 'qty' => '200.0000', 'unit' => 'ML', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-031', 'qty' => '80.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-030', 'qty' => '20.0000', 'unit' => 'G', 'waste_rate' => '0.00'],
                        ['ing_code' => 'ING-032', 'qty' => '50.0000', 'unit' => 'G', 'waste_rate' => '0.00']
                    ]
                ]
            ]
        ];

        foreach ($productionProducts as $p) {
            $merchandise = $merchandiseRepo->findOneBy(['code' => $p['code']]) ?? new Merchandise();
            $merchandise->setCode($p['code']);
            $merchandise->setName($p['name']);
            $merchandise->setType('finished_product');
            $merchandise->setFinishedProductSource('production');
            $merchandise->setProfit('25.00');
            $merchandise->setStockAlertQuantity('5');
            $merchandise->setDescription("<p>Thành phẩm sản xuất <strong>{$p['name']}</strong>.</p>");
            $merchandise->setStatus(1);

            $category = $categoryRepo->findOneBy(['slug' => $p['category_slug']]);
            if ($category) {
                $merchandise->setCategory($category);
            }

            $baseUnit = $unitRepo->findOneBy(['code' => $p['base_unit']]);
            if ($baseUnit) {
                $merchandise->setBaseUnit($baseUnit);
            }

            $manager->persist($merchandise);
            $manager->flush();

            // Lưu đơn vị tính quy đổi cho thành phẩm sản xuất nội bộ
            $conversionsData = [];
            if ($baseUnit) {
                if (!empty($p['conversions'])) {
                    foreach ($p['conversions'] as $c) {
                        $fromUnit = $unitRepo->findOneBy(['code' => $c['from_unit']]);
                        $toUnit = $unitRepo->findOneBy(['code' => $c['to_unit']]);
                        if ($fromUnit && $toUnit) {
                            $conversionsData[] = [
                                'fromUnitId' => $fromUnit->getId(),
                                'fromValue' => $c['from_value'],
                                'toUnitId' => $toUnit->getId(),
                                'toValue' => $c['to_value'],
                            ];
                        }
                    }
                }
                $this->merchandiseService->saveConversionsAndUnits($merchandise, $conversionsData, $baseUnit->getId());
            }

            // Lưu công thức
            $recipeItems = [];
            foreach ($p['recipe']['items'] as $itemData) {
                $ing = $merchandiseRepo->findOneBy(['code' => $itemData['ing_code']]);
                $u = $unitRepo->findOneBy(['code' => $itemData['unit']]);
                if ($ing && $u) {
                    $recipeItems[] = new RecipeItemDTO(
                        ingredientId: $ing->getId(),
                        quantity: $itemData['qty'],
                        unitId: $u->getId(),
                        wasteRate: $itemData['waste_rate'],
                        notes: '',
                    );
                }
            }

            if (!empty($recipeItems) && $baseUnit) {
                $this->merchandiseService->saveRecipe(
                    $merchandise,
                    new RecipeDTO(
                        outputUnitId: $baseUnit->getId(),
                        outputQuantity: 1,
                        items: $recipeItems,
                        notes: $p['recipe']['notes'],
                    )
                );
            }
        }

        $manager->flush();
    }
}
