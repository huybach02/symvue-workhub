<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class UnitFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['unit'];
    }

    public function load(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        $connection->executeStatement('DELETE FROM unit');

        $units = [
            ['name' => 'Kilôgam', 'code' => 'KG', 'symbol' => 'kg'],
            ['name' => 'Gam', 'code' => 'G', 'symbol' => 'g'],
            ['name' => 'Lít', 'code' => 'L', 'symbol' => 'l'],
            ['name' => 'Mililít', 'code' => 'ML', 'symbol' => 'ml'],
            ['name' => 'Quả', 'code' => 'QUA', 'symbol' => 'quả'],
            ['name' => 'Trái', 'code' => 'TRAI', 'symbol' => 'trái'],
            ['name' => 'Con', 'code' => 'CON', 'symbol' => 'con'],
            ['name' => 'Bó', 'code' => 'BO', 'symbol' => 'bó'],
            ['name' => 'Lon', 'code' => 'LON', 'symbol' => 'lon'],
            ['name' => 'Chai', 'code' => 'CHAI', 'symbol' => 'chai'],
            ['name' => 'Hộp', 'code' => 'HOP', 'symbol' => 'hộp'],
            ['name' => 'Gói', 'code' => 'GOI', 'symbol' => 'gói'],
            ['name' => 'Hũ', 'code' => 'HU', 'symbol' => 'hũ'],
            ['name' => 'Muỗng', 'code' => 'MUONG', 'symbol' => 'muỗng'],
            ['name' => 'Thìa', 'code' => 'THIA', 'symbol' => 'thìa'],
            ['name' => 'Chén', 'code' => 'CHEN', 'symbol' => 'chén'],
            ['name' => 'Bát', 'code' => 'BAT', 'symbol' => 'bát'],
            ['name' => 'Tô', 'code' => 'TO', 'symbol' => 'tô'],
            ['name' => 'Dĩa', 'code' => 'DIA', 'symbol' => 'dĩa'],
            ['name' => 'Phần', 'code' => 'PHAN', 'symbol' => 'phần'],
            ['name' => 'Khay', 'code' => 'KHAY', 'symbol' => 'khay'],
            ['name' => 'Thùng', 'code' => 'THUNG', 'symbol' => 'thùng'],
            ['name' => 'Két', 'code' => 'KET', 'symbol' => 'két'],
            ['name' => 'Củ', 'code' => 'CU', 'symbol' => 'củ'],
            ['name' => 'Tép', 'code' => 'TEP', 'symbol' => 'tép'],
            ['name' => 'Nhánh', 'code' => 'NHANH', 'symbol' => 'nhánh'],
            ['name' => 'Lát', 'code' => 'LAT', 'symbol' => 'lát'],
            ['name' => 'Miếng', 'code' => 'MIENG', 'symbol' => 'miếng'],
            ['name' => 'Sợi', 'code' => 'SOI', 'symbol' => 'sợi'],
            ['name' => 'Viên', 'code' => 'VIEN', 'symbol' => 'viên'],
            ['name' => 'Cây', 'code' => 'CAY', 'symbol' => 'cây'],
            ['name' => 'Túi', 'code' => 'TUI', 'symbol' => 'túi'],
        ];

        foreach ($units as $data) {
            $unit = new Unit();
            $unit->setName($data['name']);
            $unit->setCode($data['code']);
            $unit->setSymbol($data['symbol']);
            $unit->setStatus(1); // Hoạt động

            $manager->persist($unit);
        }

        $manager->flush();
    }
}
