<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Merchandise;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MerchandiseIngredientFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            CategoryFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $categoryRepo = $manager->getRepository(Category::class);
        $merchandiseRepo = $manager->getRepository(Merchandise::class);

        // Đảm bảo đơn vị Lốc (LOC) tồn tại
        $unitRepo = $manager->getRepository(\App\Entity\Unit::class);
        $locUnit = $unitRepo->findOneBy(['code' => 'LOC']);
        if (!$locUnit) {
            $locUnit = new \App\Entity\Unit();
            $locUnit->setName('Lốc');
            $locUnit->setCode('LOC');
            $locUnit->setSymbol('lốc');
            $locUnit->setStatus(1);
            $manager->persist($locUnit);
            $manager->flush();
        }

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

            $manager->persist($merchandise);
        }

        $manager->flush();

        // 2. Tạo hoặc cập nhật các nguyên liệu làm đồ ăn có đơn vị lồng nhau
        $nestedUnitIngredients = [
            'ING-MIGOI' => [
                'name' => 'Mì gói Koreno làm mì cay',
                'profit' => '25.00',
                'stockAlertQuantity' => '100.00',
                'description' => '<p>Mì gói Koreno dai ngon chuyên dùng nấu mì cay cấp độ.</p>',
                'notes' => 'Bảo quản nơi khô ráo, tránh ẩm ướt.',
                'baseUnit' => 'GOI',
                'category_slug' => 'mi-goi-nguyen-lieu',
                'conversions' => [
                    ['from' => 'LOC', 'to' => 'GOI', 'value' => '5.00'],
                    ['from' => 'THUNG', 'to' => 'LOC', 'value' => '8.00'],
                ]
            ],
            'ING-KIMCHI' => [
                'name' => 'Kim chi cải thảo cắt khúc',
                'profit' => '30.00',
                'stockAlertQuantity' => '10000.00',
                'description' => '<p>Kim chi cải thảo muối chua cay, cắt khúc vừa ăn dùng làm topping và nấu nước lẩu mì cay.</p>',
                'notes' => 'Luôn bảo quản trong ngăn mát tủ lạnh.',
                'baseUnit' => 'G',
                'category_slug' => 'cai-thao',
                'conversions' => [
                    ['from' => 'TUI', 'to' => 'G', 'value' => '500.00'],
                    ['from' => 'THUNG', 'to' => 'TUI', 'value' => '20.00'],
                ]
            ],
            'ING-CAVIEN' => [
                'name' => 'Cá viên thả lẩu mì cay',
                'profit' => '20.00',
                'stockAlertQuantity' => '500.00',
                'description' => '<p>Cá viên chiên dai giòn dùng thả lẩu hoặc chiên bán kèm.</p>',
                'notes' => 'Bảo quản tủ đông.',
                'baseUnit' => 'VIEN',
                'category_slug' => 'ca-vien',
                'conversions' => [
                    ['from' => 'GOI', 'to' => 'VIEN', 'value' => '100.00'],
                    ['from' => 'THUNG', 'to' => 'GOI', 'value' => '10.00'],
                ]
            ],
            'ING-NUOCMAM' => [
                'name' => 'Nước mắm Nam Ngư',
                'profit' => '15.00',
                'stockAlertQuantity' => '5000.00',
                'description' => '<p>Nước mắm hương cá hồi Nam Ngư dùng để làm nước chấm hoặc gia vị tẩm ướp đồ ăn.</p>',
                'notes' => 'Bảo quản nơi khô ráo thoáng mát, đậy nắp kỹ sau khi dùng.',
                'baseUnit' => 'ML',
                'category_slug' => 'nuoc-mam-nguyen-lieu',
                'conversions' => [
                    ['from' => 'CHAI', 'to' => 'ML', 'value' => '900.00'],
                    ['from' => 'LOC', 'to' => 'CHAI', 'value' => '6.00'],
                    ['from' => 'THUNG', 'to' => 'LOC', 'value' => '4.00'],
                ]
            ],
            'ING-NUOCTUONG' => [
                'name' => 'Nước tương Chinsu tỏi ớt',
                'profit' => '18.00',
                'stockAlertQuantity' => '3000.00',
                'description' => '<p>Nước tương Chinsu tỏi ớt thơm cay hảo hạng dùng làm nước sốt hoặc tẩm ướp mì trộn.</p>',
                'notes' => 'Bảo quản mát sau khi mở nắp.',
                'baseUnit' => 'ML',
                'category_slug' => 'nuoc-tuong-nguyen-lieu',
                'conversions' => [
                    ['from' => 'CHAI', 'to' => 'ML', 'value' => '500.00'],
                    ['from' => 'LOC', 'to' => 'CHAI', 'value' => '6.00'],
                    ['from' => 'THUNG', 'to' => 'LOC', 'value' => '4.00'],
                ]
            ],
        ];

        // Tạo/cập nhật các thực thể Merchandise lồng nhau
        $nestedMerchandise = [];
        foreach ($nestedUnitIngredients as $code => $data) {
            $merchandise = $merchandiseRepo->findOneBy(['code' => $code]) ?? new Merchandise();
            $merchandise->setCode($code);
            $merchandise->setName($data['name']);
            $merchandise->setType('ingredient');
            $merchandise->setProfit($data['profit']);
            $merchandise->setStockAlertQuantity($data['stockAlertQuantity']);
            $merchandise->setDescription($data['description']);
            $merchandise->setNotes($data['notes']);
            $merchandise->setStatus(1);

            $baseUnit = $unitRepo->findOneBy(['code' => $data['baseUnit']]);
            if ($baseUnit) {
                $merchandise->setBaseUnit($baseUnit);
            }

            $category = $categoryRepo->findOneBy(['slug' => $data['category_slug'], 'type' => 'ingredient']);
            if ($category) {
                $merchandise->setCategory($category);
            }

            $manager->persist($merchandise);
            $nestedMerchandise[$code] = $merchandise;
        }

        $manager->flush();

        // Dọn dẹp quy đổi cũ trước khi thêm mới
        $conversionRepo = $manager->getRepository(\App\Entity\MerchandiseUnitConversion::class);
        $oldConversions = $conversionRepo->createQueryBuilder('c')
            ->innerJoin('c.merchandise', 'm')
            ->where('m.code IN (:codes)')
            ->setParameter('codes', array_keys($nestedUnitIngredients))
            ->getQuery()
            ->getResult();
        foreach ($oldConversions as $oldConv) {
            $manager->remove($oldConv);
        }
        $manager->flush();

        // Thêm các quy đổi đơn vị (Conversions) từ cấu hình
        foreach ($nestedUnitIngredients as $code => $data) {
            $merchandise = $nestedMerchandise[$code];
            foreach ($data['conversions'] as $sortOrder => $convData) {
                $fromUnit = $unitRepo->findOneBy(['code' => $convData['from']]);
                $toUnit = $unitRepo->findOneBy(['code' => $convData['to']]);

                if ($fromUnit && $toUnit) {
                    $conversion = new \App\Entity\MerchandiseUnitConversion();
                    $conversion->setMerchandise($merchandise);
                    $conversion->setFromUnit($fromUnit);
                    $conversion->setFromValue('1.00');
                    $conversion->setToUnit($toUnit);
                    $conversion->setToValue($convData['value']);
                    $conversion->setSortOrder($sortOrder + 1);
                    $manager->persist($conversion);
                }
            }
        }

        $manager->flush();
    }
}
