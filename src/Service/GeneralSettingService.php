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
        private readonly CacheService $cacheService,
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
                'checkInGraceMinutes' => 'CHECK_IN_GRACE_MINUTES',
                'lateLimitMinutes' => 'LATE_LIMIT_MINUTES',
                'checkInEarliestMinutes' => 'CHECK_IN_EARLIEST_MINUTES',
                'checkOutGraceMinutes' => 'CHECK_OUT_GRACE_MINUTES',
                'checkOutLatestMinutes' => 'CHECK_OUT_LATEST_MINUTES',
                'latitude' => 'LATITUDE',
                'longitude' => 'LONGITUDE',
                'radiusMeters' => 'RADIUS_METERS',
                'addressDisplay' => 'ADDRESS_DISPLAY',
                'ipAddress' => 'IP_ADDRESS',
                'qrTtlSeconds' => 'QR_TTL_SECONDS',
                'photoRetentionDays' => 'PHOTO_RETENTION_DAYS',
                'maxDevicesPerEmployee' => 'MAX_DEVICES_PER_EMPLOYEE',
                'sameDeviceMaxEmployees' => 'SAME_DEVICE_MAX_EMPLOYEES',
                'remindMissingCheckIn' => 'REMIND_MISSING_CHECK_IN',
                'remindMissingCheckOut' => 'REMIND_MISSING_CHECK_OUT',
                'checkInReminderMinutesBefore' => 'CHECK_IN_REMINDER_MINUTES_BEFORE',
                'checkOutReminderMinutesBefore' => 'CHECK_OUT_REMINDER_MINUTES_BEFORE',
            ];

            $cauHinhChungList = $this->cauHinhChungRepository->findAll();

            $cauHinhChungMap = [];
            $cauHinhChungCacheData = [];
            foreach ($cauHinhChungList as $item) {
                $cauHinhChungMap[$item->getTenCauHinh()] = $item;
                $cauHinhChungCacheData[$item->getTenCauHinh()] = $item->getGiaTri();
            }

            foreach ($mapping as $dtoField => $tenCauHinh) {
                if (!property_exists($cauHinhChungDTO, $dtoField)) {
                    continue;
                }

                $giaTri = $cauHinhChungDTO->$dtoField;

                if (is_bool($giaTri)) {
                    $giaTri = $giaTri ? '1' : '0';
                }

                if (isset($cauHinhChungMap[$tenCauHinh])) {
                    $cauHinhChungMap[$tenCauHinh]->setGiaTri((string) $giaTri);
                    $cauHinhChungCacheData[$tenCauHinh] = (string) $giaTri;
                }
            }
            $this->entityManager->flush();

            $this->cacheService->set(GeneralSettingRepository::CACHE_KEY, $cauHinhChungCacheData, 60 * 60 * 24 * 365 * 5, true);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
