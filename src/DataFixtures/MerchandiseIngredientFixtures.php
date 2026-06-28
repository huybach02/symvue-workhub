<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Merchandise;
use App\Entity\Category;
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
        }

        $manager->flush();
    }
}
