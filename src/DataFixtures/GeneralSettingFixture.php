<?php

namespace App\DataFixtures;

use App\Entity\GeneralSetting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class GeneralSettingFixture extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ["general-setting"];
    }

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
                "gia_tri" => "0",
                "mo_ta" => "Xác thực 2 yếu tố (0: không, 1: có)",
            ],
            [
                "ten_cau_hinh" => "THOI_GIAN_HET_HAN_OTP",
                "gia_tri" => "10",
                "mo_ta" => "Thời gian hết hạn OTP (phút)",
            ],
            [
                "ten_cau_hinh" => "SO_THIET_BI_DANG_NHAP_TOI_DA",
                "gia_tri" => "5",
                "mo_ta" => "Số thiết bị đăng nhập tối đa",
            ],
            [
                "ten_cau_hinh" => "THOI_HAN_XAC_THUC_LAI_THIET_BI",
                "gia_tri" => "90",
                "mo_ta" => "Thời hạn xác thực lại thiết bị (ngày)",
            ],
            [
                "ten_cau_hinh" => "CHECK_THOI_GIAN_LAM_VIEC",
                "gia_tri" => "0",
                "mo_ta" => "Thời gian làm việc (0: không, 1: có)",
            ],
            [
                "ten_cau_hinh" => "CHECK_IN_GRACE_MINUTES",
                "gia_tri" => "15",
                "mo_ta" => "Cho phép vào trễ không tính trễ (phút)",
            ],
            [
                "ten_cau_hinh" => "LATE_LIMIT_MINUTES",
                "gia_tri" => "120",
                "mo_ta" => "Thời gian trễ tối đa (phút)",
            ],
            [
                "ten_cau_hinh" => "CHECK_IN_EARLIEST_MINUTES",
                "gia_tri" => "60",
                "mo_ta" => "Cho phép chấm công sớm tối đa (phút)",
            ],
            [
                "ten_cau_hinh" => "CHECK_OUT_GRACE_MINUTES",
                "gia_tri" => "15",
                "mo_ta" => "Cho phép ra sớm không bị tính về sớm (phút)",
            ],
            [
                "ten_cau_hinh" => "CHECK_OUT_LATEST_MINUTES",
                "gia_tri" => "120",
                "mo_ta" => "Cho phép chấm công muộn tối đa (phút)",
            ],
            [
                "ten_cau_hinh" => "LATITUDE",
                "gia_tri" => "0",
                "mo_ta" => "Vĩ độ (độ)",
            ],
            [
                "ten_cau_hinh" => "LONGITUDE",
                "gia_tri" => "0",
                "mo_ta" => "Kinh độ (độ)",
            ],
            [
                "ten_cau_hinh" => "RADIUS_METERS",
                "gia_tri" => "100",
                "mo_ta" => "Bán kính hợp lệ (mét)",
            ],
            [
                "ten_cau_hinh" => "ADDRESS_DISPLAY",
                "gia_tri" => "",
                "mo_ta" => "Địa chỉ hiển thị",
            ],
            [
                "ten_cau_hinh" => "IP_ADDRESS",
                "gia_tri" => "",
                "mo_ta" => "Địa chỉ IP hợp lệ",
            ],
            [
                "ten_cau_hinh" => "QR_TTL_SECONDS",
                "gia_tri" => "30",
                "mo_ta" => "Thời gian hết hạn mã QR (giây)",
            ],
            [
                "ten_cau_hinh" => "PHOTO_RETENTION_DAYS",
                "gia_tri" => "90",
                "mo_ta" => "Thời hạn lưu trữ ảnh chấm công (ngày)",
            ],
            [
                "ten_cau_hinh" => "MAX_DEVICES_PER_EMPLOYEE",
                "gia_tri" => "2",
                "mo_ta" =>
                    "Số thiết bị chấm công tối đa cho mỗi nhân viên (thiết bị)",
            ],
            [
                "ten_cau_hinh" => "SAME_DEVICE_MAX_EMPLOYEES",
                "gia_tri" => "1",
                "mo_ta" =>
                    "Số nhân viên tối đa chấm công cùng thiết bị (nhân viên)",
            ],
            [
                "ten_cau_hinh" => "REMIND_MISSING_CHECK_IN",
                "gia_tri" => "0",
                "mo_ta" => "Nhắc nhở đến giờ chấm công vào (0: không, 1: có)",
            ],
            [
                "ten_cau_hinh" => "REMIND_MISSING_CHECK_OUT",
                "gia_tri" => "0",
                "mo_ta" => "Nhắc nhở đến giờ chấm công ra (0: không, 1: có)",
            ],
            [
                "ten_cau_hinh" => "CHECK_IN_REMINDER_MINUTES_BEFORE",
                "gia_tri" => "10",
                "mo_ta" => "Nhắc nhở chấm công vào trước (phút)",
            ],
            [
                "ten_cau_hinh" => "CHECK_OUT_REMINDER_MINUTES_BEFORE",
                "gia_tri" => "10",
                "mo_ta" => "Nhắc nhở chấm công ra trước (phút)",
            ],
        ];

        foreach ($data as $item) {
            $cauhinh = new GeneralSetting();
            $cauhinh->setTenCauHinh($item["ten_cau_hinh"]);
            $cauhinh->setGiaTri($item["gia_tri"]);
            $cauhinh->setMoTa($item["mo_ta"]);
            $manager->persist($cauhinh);
        }

        $manager->flush();
    }
}
