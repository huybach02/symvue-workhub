<?php

namespace App\Service;

use App\DTO\CauHinhChungDTO;
use App\Entity\CauHinhChung;
use App\Repository\CauHinhChungRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CauHinhChungService
{
    public function __construct(
        private readonly CauHinhChungRepository $cauHinhChungRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(): array
    {
        $cauHinhChungList = $this->cauHinhChungRepository->findAll();

        return array_map(
            fn(CauHinhChung $item) => $item->jsonSerialize(),
            $cauHinhChungList
        );
    }

    public function update(CauHinhChungDTO $cauHinhChungDTO): void
    {
        try {
            $mapping = [
                'soLanDangNhapSai' => 'SO_LAN_DANG_NHAP_SAI_TOI_DA',
                'thoiGianTamKhoaTaiKhoan' => 'THOI_GIAN_KHOA_TAI_KHOAN',
                'xacThuc2YeuTo' => 'XAC_THUC_2_YEU_TO',
                'thoiGianHetHanMaOtp' => 'THOI_GIAN_HET_HAN_OTP',
                'thoiHanXacThucLaiThietBi' => 'THOI_HAN_XAC_THUC_LAI_THIET_BI',
                'kiemTraThoiGianLamViec' => 'CHECK_THOI_GIAN_LAM_VIEC',
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
