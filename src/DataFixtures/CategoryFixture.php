<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategoryFixture extends Fixture implements FixtureGroupInterface
{
    private const PATH_SEPARATOR = '/';

    public static function getGroups(): array
    {
        return ['category'];
    }

    public function load(ObjectManager $manager): void
    {
        $items = [
            [
                'key' => 'ingredient-meat',
                'name' => 'Thịt tươi',
                'slug' => 'thit-tuoi',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-beef',
                'parent' => 'ingredient-meat',
                'name' => 'Thịt bò',
                'slug' => 'thit-bo',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-pork',
                'parent' => 'ingredient-meat',
                'name' => 'Thịt heo',
                'slug' => 'thit-heo',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-chicken',
                'parent' => 'ingredient-meat',
                'name' => 'Thịt gà',
                'slug' => 'thit-ga',
                'type' => 'ingredient',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-vegetable',
                'name' => 'Rau ăn kèm',
                'slug' => 'rau-an-kem',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-bok-choy',
                'parent' => 'ingredient-vegetable',
                'name' => 'Cải thìa',
                'slug' => 'cai-thia',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-napa-cabbage',
                'parent' => 'ingredient-vegetable',
                'name' => 'Cải thảo',
                'slug' => 'cai-thao',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-enoki',
                'parent' => 'ingredient-vegetable',
                'name' => 'Nấm kim châm',
                'slug' => 'nam-kim-cham',
                'type' => 'ingredient',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-spice',
                'name' => 'Gia vị',
                'slug' => 'gia-vi',
                'type' => 'ingredient',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-scallion',
                'parent' => 'ingredient-spice',
                'name' => 'Hành lá',
                'slug' => 'hanh-la',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-pepper',
                'parent' => 'ingredient-spice',
                'name' => 'Tiêu xay',
                'slug' => 'tieu-xay',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-chili-powder',
                'parent' => 'ingredient-spice',
                'name' => 'Ớt bột Hàn Quốc',
                'slug' => 'ot-bot-han-quoc',
                'type' => 'ingredient',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-seafood',
                'name' => 'Hải sản tươi',
                'slug' => 'hai-san-tuoi',
                'type' => 'ingredient',
                'position' => 4,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-shrimp',
                'parent' => 'ingredient-seafood',
                'name' => 'Tôm tươi',
                'slug' => 'tom-tuoi',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-squid',
                'parent' => 'ingredient-seafood',
                'name' => 'Mực tươi',
                'slug' => 'muc-tuoi',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-fishball',
                'parent' => 'ingredient-seafood',
                'name' => 'Cá viên',
                'slug' => 'ca-vien',
                'type' => 'ingredient',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-noodle',
                'name' => 'Mì & Bánh',
                'slug' => 'mi-va-banh',
                'type' => 'ingredient',
                'position' => 5,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-instant-noodle',
                'parent' => 'ingredient-noodle',
                'name' => 'Mì gói',
                'slug' => 'mi-goi-nguyen-lieu',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-udon',
                'parent' => 'ingredient-noodle',
                'name' => 'Mì udon',
                'slug' => 'mi-udon-nguyen-lieu',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-sauce',
                'name' => 'Sốt & Gia vị lỏng',
                'slug' => 'sot-va-gia-vi-long',
                'type' => 'ingredient',
                'position' => 6,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-fishsauce',
                'parent' => 'ingredient-sauce',
                'name' => 'Nước mắm',
                'slug' => 'nuoc-mam-nguyen-lieu',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-soysauce',
                'parent' => 'ingredient-sauce',
                'name' => 'Nước tương',
                'slug' => 'nuoc-tuong-nguyen-lieu',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-beverage',
                'name' => 'Sữa & Nước giải khát',
                'slug' => 'sua-va-nuoc-giai-khat',
                'type' => 'ingredient',
                'position' => 7,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-milk',
                'parent' => 'ingredient-beverage',
                'name' => 'Sữa đặc & Sữa tươi',
                'slug' => 'sua-dac-va-sua-tuoi',
                'type' => 'ingredient',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'ingredient-softdrink',
                'parent' => 'ingredient-beverage',
                'name' => 'Nước giải khát',
                'slug' => 'nuoc-giai-khat-nguyen-lieu',
                'type' => 'ingredient',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'finished-noodle-base',
                'name' => 'Nền mì',
                'slug' => 'nen-mi',
                'type' => 'finished_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'finished-instant-noodle',
                'parent' => 'finished-noodle-base',
                'name' => 'Mì gói',
                'slug' => 'mi-goi',
                'type' => 'finished_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'finished-udon-noodle',
                'parent' => 'finished-noodle-base',
                'name' => 'Mì udon',
                'slug' => 'mi-udon',
                'type' => 'finished_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'finished-topping',
                'name' => 'Topping chế biến sẵn',
                'slug' => 'topping-che-bien-san',
                'type' => 'finished_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'finished-fish-cake',
                'parent' => 'finished-topping',
                'name' => 'Chả cá',
                'slug' => 'cha-ca',
                'type' => 'finished_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'finished-sausage',
                'parent' => 'finished-topping',
                'name' => 'Xúc xích',
                'slug' => 'xuc-xich',
                'type' => 'finished_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'finished-beef-ball',
                'parent' => 'finished-topping',
                'name' => 'Bò viên',
                'slug' => 'bo-vien',
                'type' => 'finished_product',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'finished-sauce',
                'name' => 'Sốt và nước dùng',
                'slug' => 'sot-va-nuoc-dung',
                'type' => 'finished_product',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'finished-spicy-soup',
                'parent' => 'finished-sauce',
                'name' => 'Nước lẩu mì cay',
                'slug' => 'nuoc-lau-mi-cay',
                'type' => 'finished_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'finished-mixed-noodle-sauce',
                'parent' => 'finished-sauce',
                'name' => 'Sốt mì trộn',
                'slug' => 'sot-mi-tron',
                'type' => 'finished_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'business-spicy-noodle',
                'name' => 'Mì cay',
                'slug' => 'mi-cay',
                'type' => 'business_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'business-spicy-noodle-beef',
                'parent' => 'business-spicy-noodle',
                'name' => 'Mì cay bò Mỹ',
                'slug' => 'mi-cay-bo-my',
                'type' => 'business_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'business-spicy-noodle-seafood',
                'parent' => 'business-spicy-noodle',
                'name' => 'Mì cay hải sản',
                'slug' => 'mi-cay-hai-san',
                'type' => 'business_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'business-spicy-noodle-combo',
                'parent' => 'business-spicy-noodle',
                'name' => 'Mì cay thập cẩm',
                'slug' => 'mi-cay-thap-cam',
                'type' => 'business_product',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'business-mixed-noodle',
                'name' => 'Mì trộn',
                'slug' => 'mi-tron',
                'type' => 'business_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'business-mixed-noodle-fish-cake',
                'parent' => 'business-mixed-noodle',
                'name' => 'Mì trộn chả cá',
                'slug' => 'mi-tron-cha-ca',
                'type' => 'business_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'business-mixed-noodle-sausage',
                'parent' => 'business-mixed-noodle',
                'name' => 'Mì trộn xúc xích',
                'slug' => 'mi-tron-xuc-xich',
                'type' => 'business_product',
                'position' => 2,
                'isActive' => true,
            ],
            [
                'key' => 'business-mixed-noodle-special',
                'parent' => 'business-mixed-noodle',
                'name' => 'Mì trộn đặc biệt',
                'slug' => 'mi-tron-dac-biet',
                'type' => 'business_product',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'business-side-dish',
                'name' => 'Món ăn thêm',
                'slug' => 'mon-an-them',
                'type' => 'business_product',
                'position' => 3,
                'isActive' => true,
            ],
            [
                'key' => 'business-fried-fish-cake',
                'parent' => 'business-side-dish',
                'name' => 'Chả cá chiên',
                'slug' => 'cha-ca-chien',
                'type' => 'business_product',
                'position' => 1,
                'isActive' => true,
            ],
            [
                'key' => 'business-fried-sausage',
                'parent' => 'business-side-dish',
                'name' => 'Xúc xích chiên',
                'slug' => 'xuc-xich-chien',
                'type' => 'business_product',
                'position' => 2,
                'isActive' => false,
            ],
        ];

        $categories = [];

        foreach ($items as $item) {
            $parent = isset($item['parent']) ? $categories[$item['parent']] : null;
            $category = $this->findCategory($manager, $item) ?? new Category();

            $category->setName($item['name']);
            $category->setSlug($item['slug']);
            $category->setType($item['type']);
            $category->setParent($parent);
            $category->setLevel($parent ? $parent->getLevel() + 1 : 0);
            $category->setPosition($item['position']);
            $category->setIsActive($item['isActive']);
            $now = new \DateTime();
            $category->setCreatedAt($now);
            $category->setUpdatedAt($now);

            $manager->persist($category);
            $categories[$item['key']] = $category;
        }

        $manager->flush();

        foreach ($items as $item) {
            $category = $categories[$item['key']];
            $parent = isset($item['parent']) ? $categories[$item['parent']] : null;

            $category->setPath(
                $parent
                    ? $parent->getPath() . self::PATH_SEPARATOR . $category->getId()
                    : (string) $category->getId()
            );
        }

        $manager->flush();
    }

    private function findCategory(ObjectManager $manager, array $item): ?Category
    {
        return $manager->getRepository(Category::class)->findOneBy([
            'type' => $item['type'],
            'slug' => $item['slug'],
        ]);
    }
}
