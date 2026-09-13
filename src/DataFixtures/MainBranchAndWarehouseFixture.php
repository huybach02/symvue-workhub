<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Class\WarehouseType;
use App\Entity\Branch;
use App\Entity\GeneralSetting;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MainBranchAndWarehouseFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['main-branch-and-warehouse'];
    }

    public function getDependencies(): array
    {
        return [
            GeneralSettingFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $mainBranch = $manager->getRepository(Branch::class)->findOneBy(['code' => 'MAIN_BRANCH']) ?? new Branch();
        $mainBranch->setCode('MAIN_BRANCH');
        $mainBranch->setType(WarehouseType::Main->value);
        $mainBranch->setName('Main Branch');
        $mainBranch->setPhone('0987654321');
        $mainBranch->setEmail('main@branch.com');
        $mainBranch->setAddress('123 Nguyễn Văn Cừ, Phường An Hòa, Quận Ninh Kiều, Thành phố Cần Thơ');
        $mainBranch->setStatus(true);
        $mainBranch->setNote('Main Branch');

        $mainWarehouse = $manager->getRepository(Warehouse::class)->findOneBy(['code' => 'MAIN_WAREHOUSE']) ?? new Warehouse();
        $mainWarehouse->setCode('MAIN_WAREHOUSE');
        $mainWarehouse->setName('Main Warehouse');
        $mainWarehouse->setType(WarehouseType::Main->value);
        $mainWarehouse->setStatus(true);
        $mainWarehouse->setNote('Main Warehouse Note');
        $mainWarehouse->setBranch($mainBranch);

        $manager->persist($mainWarehouse);
        $manager->persist($mainBranch);
        $manager->flush();

        // Cập nhật cấu hình kho mặc định vào GeneralSetting
        $settingKeys = [
            'RECEIVE_FROM_PROVIDER_WAREHOUSE_ID',
            'PRODUCTION_MATERIAL_WAREHOUSE_ID',
            'PRODUCTION_FINISHED_GOODS_WAREHOUSE_ID',
        ];
        foreach ($settingKeys as $key) {
            $setting = $manager->getRepository(GeneralSetting::class)->findOneBy(['tenCauHinh' => $key]);
            if ($setting) {
                $setting->setGiaTri((string) $mainWarehouse->getId());
                $manager->persist($setting);
            }
        }
        $manager->flush();
    }
}
