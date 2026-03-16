<?php

namespace App\Service;

use App\DTO\GeneralSettingDTO;
use App\Entity\GeneralSetting;
use App\Repository\GeneralSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

final class GeneralSettingService
{
    public function __construct(
        private readonly GeneralSettingRepository $cauHinhChungRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(): array
    {
        $cauHinhChungList = $this->cauHinhChungRepository->findAll();

        return array_map(
            fn(GeneralSetting $item) => $item->jsonSerialize(),
            $cauHinhChungList
        );
    }

    public function update(GeneralSettingDTO $cauHinhChungDTO): void
    {
        try {
            $mapping = [
                'soLanDangNhapSai' => 'SO_LAN_DANG_NHAP_SAI_TOI_DA',
                'thoiGianTamKhoaTaiKhoan' => 'THOI_GIAN_KHOA_TAI_KHOAN',
                'xacThuc2YeuTo' => 'XAC_THUC_2_YEU_TO',
                'thoiGianHetHanMaOtp' => 'THOI_GIAN_HET_HAN_OTP',
                'thoiHanXacThucLaiThietBi' => 'THOI_HAN_XAC_THUC_LAI_THIET_BI',
                'kiemTraThoiGianLamViec' => 'CHECK_THOI_GIAN_LAM_VIEC',
                'soThietBiDangNhapToiDa' => 'SO_THIET_BI_DANG_NHAP_TOI_DA',
            ];

            $cauHinhChungList = $this->cauHinhChungRepository->findAll();

            $cauHinhChungMap = [];
            foreach ($cauHinhChungList as $item) {
                $cauHinhChungMap[$item->getTenCauHinh()] = $item;
            }

            foreach ($mapping as $dtoField => $tenCauHinh) {
                $giaTri = $cauHinhChungDTO->$dtoField;

                if (is_bool($giaTri)) {
                    $giaTri = $giaTri ? '1' : '0';
                }

                if (isset($cauHinhChungMap[$tenCauHinh])) {
                    $cauHinhChungMap[$tenCauHinh]->setGiaTri((string) $giaTri);
                }
            }
            $this->entityManager->flush();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
