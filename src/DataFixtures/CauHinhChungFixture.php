<?php

namespace App\DataFixtures;

use App\Entity\CauHinhChung;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CauHinhChungFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                "ten_cau_hinh" => "SO_LAN_DANG_NHAP_SAI_TOI_DA",
                "gia_tri" => "5",
                "mo_ta" => "Số lần đăng nhập sai tối đa",
            ],
            [
                "ten_cau_hinh" => "THOI_GIAN_KHOA_TAI_KHOAN",
                "gia_tri" => "3",
                "mo_ta" => "Thời gian khóa tài khoản (phút)",
            ],
            [
                "ten_cau_hinh" => "XAC_THUC_2_YEU_TO",
                "gia_tri" => "1",
                "mo_ta" => "Xác thực 2 yếu tố (0: không, 1: có)",
            ],
            [
                "ten_cau_hinh" => "THOI_GIAN_HET_HAN_OTP",
                "gia_tri" => "10",
                "mo_ta" => "Thời gian hết hạn OTP (phút)",
            ],
            [
                "ten_cau_hinh" => "THOI_HAN_XAC_THUC_LAI_THIET_BI",
                "gia_tri" => "90",
                "mo_ta" => "Thời hạn xác thực lại thiết bị (ngày)",
            ],
            [
                "ten_cau_hinh" => "CHECK_THOI_GIAN_LAM_VIEC",
                "gia_tri" => "1",
                "mo_ta" => "Thời gian làm việc (0: không, 1: có)",
            ],
        ];

        foreach ($data as $item) {
            $cauhinh = new CauHinhChung();
            $cauhinh->setTenCauHinh($item['ten_cau_hinh']);
            $cauhinh->setGiaTri($item['gia_tri']);
            $cauhinh->setMoTa($item['mo_ta']);
            $cauhinh->setCreatedAt(new \DateTimeImmutable());
            $cauhinh->setUpdatedAt(new \DateTimeImmutable());
            $manager->persist($cauhinh);
        }

        $manager->flush();
    }
}
