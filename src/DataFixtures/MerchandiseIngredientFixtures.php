<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Merchandise;
use App\Entity\Category;
use App\Entity\Provider;
use App\Service\MerchandiseService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MerchandiseIngredientFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private readonly MerchandiseService $merchandiseService,
    ) {}

    public static function getGroups(): array
    {
        return ['merchandise-ingredient'];
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixture::class,
            UnitFixture::class,
            ProviderFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepo = $manager->getRepository(Category::class);
        $merchandiseRepo = $manager->getRepository(Merchandise::class);
        $unitRepo = $manager->getRepository(\App\Entity\Unit::class);

        // Bản đồ cấu hình đơn vị theo danh mục hàng hóa
        $unitConfigs = [
            'mi-goi-nguyen-lieu' => [
                'base_unit' => 'GOI',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'GOI', 'to_value' => '30.00'],
                ]
            ],
            'mi-udon-nguyen-lieu' => [
                'base_unit' => 'GOI',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'GOI', 'to_value' => '20.00'],
                ]
            ],
            'cai-thao' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'TUI', 'to_value' => '20.00'],
                    ['from_unit' => 'TUI', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '500.00'],
                ]
            ],
            'thit-bo' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'KHAY', 'to_value' => '10.00'],
                    ['from_unit' => 'KHAY', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '500.00'],
                ]
            ],
            'thit-heo' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'KHAY', 'to_value' => '10.00'],
                    ['from_unit' => 'KHAY', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '500.00'],
                ]
            ],
            'tom-tuoi' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'KHAY', 'to_value' => '10.00'],
                    ['from_unit' => 'KHAY', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '1000.00'],
                ]
            ],
            'muc-tuoi' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'KHAY', 'to_value' => '10.00'],
                    ['from_unit' => 'KHAY', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '1000.00'],
                ]
            ],
            'ca-vien' => [
                'base_unit' => 'VIEN',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'TUI', 'to_value' => '10.00'],
                    ['from_unit' => 'TUI', 'from_value' => '1.00', 'to_unit' => 'VIEN', 'to_value' => '100.00'],
                ]
            ],
            'gia-vi' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'HOP', 'to_value' => '20.00'],
                    ['from_unit' => 'HOP', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '500.00'],
                ]
            ],
            'rau-an-kem' => [
                'base_unit' => 'G',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'TUI', 'to_value' => '20.00'],
                    ['from_unit' => 'TUI', 'from_value' => '1.00', 'to_unit' => 'G', 'to_value' => '500.00'],
                ]
            ],
            'sot-va-gia-vi-long' => [
                'base_unit' => 'ML',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'CHAI', 'to_value' => '12.00'],
                    ['from_unit' => 'CHAI', 'from_value' => '1.00', 'to_unit' => 'ML', 'to_value' => '1000.00'],
                ]
            ],
            'sua-va-nuoc-giai-khat' => [
                'base_unit' => 'ML',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'CHAI', 'to_value' => '24.00'],
                    ['from_unit' => 'CHAI', 'from_value' => '1.00', 'to_unit' => 'ML', 'to_value' => '500.00'],
                ]
            ],
            'sua-dac-va-sua-tuoi' => [
                'base_unit' => 'ML',
                'conversions' => [
                    ['from_unit' => 'THUNG', 'from_value' => '1.00', 'to_unit' => 'HOP', 'to_value' => '48.00'],
                    ['from_unit' => 'HOP', 'from_value' => '1.00', 'to_unit' => 'ML', 'to_value' => '380.00'],
                ]
            ],
        ];

        $ingredients = [
            ['name' => 'Mì gói Koreno làm mì cay', 'category_slug' => 'mi-goi-nguyen-lieu'],
            ['name' => 'Mì udon tươi Hàn Quốc', 'category_slug' => 'mi-udon-nguyen-lieu'],
            ['name' => 'Kim chi cải thảo cắt khúc', 'category_slug' => 'cai-thao'],
            ['name' => 'Ba chỉ bò Mỹ thái lát lẩu', 'category_slug' => 'thit-bo'],
            ['name' => 'Tôm tươi nguyên con làm mì cay', 'category_slug' => 'tom-tuoi'],
            ['name' => 'Mực ống tươi cắt khoanh', 'category_slug' => 'muc-tuoi'],
            ['name' => 'Cá viên thả lẩu mì cay', 'category_slug' => 'ca-vien'],
            ['name' => 'Tàu hũ ky lá khô', 'category_slug' => 'gia-vi'],
            ['name' => 'Nấm đùi gà cắt lát', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Bông cải xanh tươi', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Ớt hiểm xay làm cấp độ cay', 'category_slug' => 'gia-vi'],
            ['name' => 'Sốt mì cay đặc chế', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Sốt mì trộn đặc trưng', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Xúc xích Đức xông khói', 'category_slug' => 'thit-heo'],
            ['name' => 'Hành phi thơm vàng', 'category_slug' => 'gia-vi'],
            ['name' => 'Mỡ hành tươi nấu chín', 'category_slug' => 'gia-vi'],
            ['name' => 'Sốt phô mai cay hảo hạng', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Rau muống chẻ sợi lẩu', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Giá đỗ sạch tươi ngon', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Bột cà phê Arabica', 'category_slug' => 'sua-va-nuoc-giai-khat'],
            ['name' => 'Sữa đặc có đường', 'category_slug' => 'sua-dac-va-sua-tuoi'],
            ['name' => 'Đường cát trắng', 'category_slug' => 'gia-vi'],
            ['name' => 'Bột matcha Nhật Bản', 'category_slug' => 'sua-va-nuoc-giai-khat'],
            ['name' => 'Syrup hương Vani', 'category_slug' => 'sua-va-nuoc-giai-khat'],
            ['name' => 'Syrup hương Caramel', 'category_slug' => 'sua-va-nuoc-giai-khat'],
            ['name' => 'Trà đen Phúc Long', 'category_slug' => 'sua-va-nuoc-giai-khat'],
            ['name' => 'Mật ong hoa nhãn', 'category_slug' => 'gia-vi'],
            ['name' => 'Thịt heo xay tươi', 'category_slug' => 'thit-heo'],
            ['name' => 'Nước mắm Phú Quốc', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Tiêu đen xay nhuyễn', 'category_slug' => 'gia-vi'],
            ['name' => 'Ớt bột Hàn Quốc', 'category_slug' => 'gia-vi'],
            ['name' => 'Hành lá tươi', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Thịt nạc vai bò', 'category_slug' => 'thit-bo'],
            ['name' => 'Nước tương Chin-su', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Nấm kim châm tươi', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Nước dùng xương đặc chế', 'category_slug' => 'sot-va-gia-vi-long'],
            ['name' => 'Cải thìa tươi', 'category_slug' => 'rau-an-kem'],
            ['name' => 'Cải thảo Đà Lạt tươi', 'category_slug' => 'cai-thao'],
        ];

        foreach ($ingredients as $index => $item) {
            $num = $index + 1;
            $code = sprintf('ING-%03d', $num);
            $name = $item['name'];

            $merchandise = $merchandiseRepo->findOneBy(['code' => $code]) ?? new Merchandise();
            $merchandise->setCode($code);
            $merchandise->setName($name);
            $merchandise->setType('ingredient');
            $merchandise->setProfit((string) rand(10, 30));
            $merchandise->setStockAlertQuantity((string) rand(5, 50));
            $merchandise->setDescription("<p>Mô tả chi tiết cho nguyên liệu <strong>{$name}</strong> dùng trong chế biến mì cay, mì trộn hoặc pha chế đồ uống.</p>");
            $merchandise->setNotes("Ghi chú nguyên liệu mẫu số {$num}.");
            $merchandise->setStatus(1);

            $category = $categoryRepo->findOneBy(['slug' => $item['category_slug'], 'type' => 'ingredient']);
            if ($category) {
                $merchandise->setCategory($category);
            }

            $config = $unitConfigs[$item['category_slug']] ?? null;
            $baseUnit = null;
            if ($config) {
                $baseUnit = $unitRepo->findOneBy(['code' => $config['base_unit']]);
                if ($baseUnit) {
                    $merchandise->setBaseUnit($baseUnit);
                }
            }

            $manager->persist($merchandise);

            if ($config && $baseUnit) {
                $conversionsData = [];
                foreach ($config['conversions'] as $c) {
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

            // Gán nhà cung cấp (provider) và thiết lập giá, quy đổi tương ứng cho nguyên liệu
            $categoryProviderMap = [
                'sua-dac-va-sua-tuoi' => 'VINAMILK',
                'cai-thao' => 'VIETGAPDL',
                'rau-an-kem' => 'VIETGAPDL',
                'gia-vi' => 'MASAN',
                'sot-va-gia-vi-long' => 'MASAN',
                'sua-va-nuoc-giai-khat' => 'TRUNGUYEN',
                'mi-goi-nguyen-lieu' => 'MASAN',
                'mi-udon-nguyen-lieu' => 'MEGAMARKET',
                'thit-bo' => 'MEGAMARKET',
                'thit-heo' => 'MEGAMARKET',
                'tom-tuoi' => 'MEGAMARKET',
                'muc-tuoi' => 'MEGAMARKET',
                'ca-vien' => 'MEGAMARKET',
            ];

            $providerRepo = $manager->getRepository(Provider::class);
            $providerCode = $categoryProviderMap[$item['category_slug']] ?? 'MEGAMARKET';
            $provider = $providerRepo->findOneBy(['code' => $providerCode]);

            if ($provider) {
                $pConversions = [];
                $customConvs = [];
                if ($config) {
                    foreach ($config['conversions'] as $c) {
                        $fromUnit = $unitRepo->findOneBy(['code' => $c['from_unit']]);
                        $toUnit = $unitRepo->findOneBy(['code' => $c['to_unit']]);
                        if ($fromUnit && $toUnit) {
                            $fromValue = $c['from_value'];
                            $toValue = $c['to_value'];
                            
                            if ($providerCode === 'VIETGAPDL') {
                                if ($c['from_unit'] === 'THUNG' && $c['to_unit'] === 'TUI') {
                                    $toValue = '24.00';
                                } elseif ($c['from_unit'] === 'TUI' && $c['to_unit'] === 'G') {
                                    $toValue = '400.00';
                                }
                            }

                            $pConversions[] = [
                                'fromUnitId' => $fromUnit->getId(),
                                'fromValue' => $fromValue,
                                'toUnitId' => $toUnit->getId(),
                                'toValue' => $toValue,
                            ];

                            $customConvs[] = [
                                'from_unit' => $c['from_unit'],
                                'from_value' => $fromValue,
                                'to_unit' => $c['to_unit'],
                                'to_value' => $toValue,
                            ];
                        }
                    }
                }

                $priceVal = 1000.00;
                if ($baseUnit) {
                    $slug = $item['category_slug'];
                    $codeUnit = $baseUnit->getCode();
                    
                    if ($codeUnit === 'G') {
                        if ($slug === 'thit-bo') {
                            $priceVal = rand(220, 280); // 220đ - 280đ/gam (220k - 280k/kg)
                        } elseif ($slug === 'thit-heo') {
                            $priceVal = rand(110, 150); // 110đ - 150đ/gam (110k - 150k/kg)
                        } elseif ($slug === 'tom-tuoi') {
                            $priceVal = rand(180, 250); // 180đ - 250đ/gam (180k - 250k/kg)
                        } elseif ($slug === 'muc-tuoi') {
                            $priceVal = rand(220, 320); // 220đ - 320đ/gam (220k - 320k/kg)
                        } elseif ($slug === 'rau-an-kem' || $slug === 'cai-thao') {
                            $priceVal = rand(15, 30); // 15đ - 30đ/gam (15k - 30k/kg)
                        } else {
                            $priceVal = rand(40, 100);
                        }
                    } elseif ($codeUnit === 'ML') {
                        if ($slug === 'sua-dac-va-sua-tuoi') {
                            $priceVal = rand(35, 65); // 35đ - 65đ/ml (35k - 65k/lít)
                        } elseif ($slug === 'sot-va-gia-vi-long') {
                            if (str_contains(strtolower($name), 'nước dùng')) {
                                $priceVal = rand(15, 30); // 15đ - 30đ/ml (15k - 30k/lít nước dùng)
                            } else {
                                $priceVal = rand(40, 80); // 40đ - 80đ/ml nước mắm/tương (40k - 80k/lít)
                            }
                        } else {
                            $priceVal = rand(20, 40);
                        }
                    } elseif ($codeUnit === 'VIEN') {
                        $priceVal = rand(600, 1500); // 600đ - 1500đ/viên
                    } elseif ($codeUnit === 'GOI' || $codeUnit === 'PHAN') {
                        $priceVal = rand(6000, 10000); // 6000đ - 10000đ/gói hoặc phần
                    } else {
                        $priceVal = rand(2000, 5000);
                    }
                }

                $pPrices = [];
                if ($baseUnit) {
                    $pPrices[] = [
                        'unitId' => $baseUnit->getId(),
                        'price' => (string)$priceVal,
                        'discountRate' => '0.00',
                        'discountAmount' => '0.00',
                        'priceAfterDiscount' => (string)$priceVal,
                        'effectiveFrom' => date('Y-m-d'),
                        'effectiveTo' => null,
                        'isDefault' => true,
                    ];
                }

                if ($config) {
                    // Helper function to calculate ratio recursively
                    $getRatio = function(string $from, string $to, array $convs) use (&$getRatio): float {
                        if ($from === $to) return 1.0;
                        foreach ($convs as $c) {
                            if ($c['from_unit'] === $from && $c['to_unit'] === $to) {
                                return (float)$c['to_value'] / (float)$c['from_value'];
                            }
                        }
                        foreach ($convs as $c) {
                            if ($c['from_unit'] === $from) {
                                $nextRatio = $getRatio($c['to_unit'], $to, $convs);
                                if ($nextRatio > 0) {
                                    return ((float)$c['to_value'] / (float)$c['from_value']) * $nextRatio;
                                }
                            }
                        }
                        return 1.0;
                    };

                    foreach ($config['conversions'] as $c) {
                        $fromUnit = $unitRepo->findOneBy(['code' => $c['from_unit']]);
                        if ($fromUnit && $baseUnit && $fromUnit->getId() !== $baseUnit->getId()) {
                            $ratio = $getRatio($c['from_unit'], $config['base_unit'], $customConvs);
                            $convPrice = $priceVal * $ratio;
                            $pPrices[] = [
                                'unitId' => $fromUnit->getId(),
                                'price' => (string)$convPrice,
                                'discountRate' => '0.00',
                                'discountAmount' => '0.00',
                                'priceAfterDiscount' => (string)$convPrice,
                                'effectiveFrom' => date('Y-m-d'),
                                'effectiveTo' => null,
                                'isDefault' => false,
                            ];
                        }
                    }
                }

                $providersData = [
                    [
                        'providerId' => $provider->getId(),
                        'unitConfigMode' => 'custom',
                        'isDefault' => true,
                        'conversions' => $pConversions,
                        'prices' => $pPrices,
                    ]
                ];

                $this->merchandiseService->saveProviders($merchandise, $providersData);
            }
        }

        $manager->flush();
    }
}
