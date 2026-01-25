<?php

namespace App\DataFixtures;

use App\Class\Constanst;
use App\Entity\ThoiGianLamViec;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThoiGianLamViecFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = Constanst::THOI_GIAN_LAM_VIEC;

        foreach ($data as $key => $item) {
            $thoiGianLamViec = new ThoiGianLamViec();
            $thoiGianLamViec->setThu($key);
            $thoiGianLamViec->setGioBatDau($item['GIO_BAT_DAU']);
            $thoiGianLamViec->setGioKetThuc($item['GIO_KET_THUC']);
            $thoiGianLamViec->setGhiChu($item['GHI_CHU']);

            $manager->persist($thoiGianLamViec);
        }

        $manager->flush();
    }
}
