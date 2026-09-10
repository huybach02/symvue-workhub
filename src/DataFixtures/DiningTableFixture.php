<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\DiningTable;
use App\Service\DiningTableService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class DiningTableFixture extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly DiningTableService $diningTableService,
    ) {}

    public static function getGroups(): array
    {
        return ['dining_table', 'dining-table'];
    }

    public function load(ObjectManager $manager): void
    {
        $repo = $manager->getRepository(DiningTable::class);

        // Khởi tạo các bàn mẫu từ 1 đến 15
        for ($i = 1; $i <= 15; $i++) {
            $table = $repo->findOneBy(['tableNumber' => $i]);
            if ($table) {
                continue;
            }

            $table = new DiningTable();
            $table->setTableNumber($i);
            $table->setQrCode($this->diningTableService->generateQrCode($i));

            // Bàn 14 đặt ở trạng thái INACTIVE để làm mẫu test
            if ($i === 14) {
                $table->setStatus(DiningTable::STATUS_INACTIVE);
                $table->setIsUsing(false);
            } elseif ($i === 2) {
                // Bàn 2 đang có order đang hoạt động
                $table->setStatus(DiningTable::STATUS_ACTIVE);
                $table->setIsUsing(true);
            } else {
                $table->setStatus(DiningTable::STATUS_ACTIVE);
                $table->setIsUsing(false);
            }

            $manager->persist($table);
        }

        $manager->flush();
    }
}
