<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class GeneralSettingDTO
{
    public function __construct(
        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $soLanDangNhapSai,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $thoiGianTamKhoaTaiKhoan,

        #[Assert\Type(type: "bool")] public readonly bool $xacThuc2YeuTo,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $thoiGianHetHanMaOtp,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $soThietBiDangNhapToiDa,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $thoiHanXacThucLaiThietBi,

        #[
            Assert\Type(type: "bool"),
        ]
        public readonly bool $kiemTraThoiGianLamViec,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkInGraceMinutes,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $lateLimitMinutes,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkInEarliestMinutes,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkOutGraceMinutes,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkOutLatestMinutes,

        #[Assert\NotBlank] #[
            Assert\Type(type: "numeric"),
        ]
        public readonly float $latitude,

        #[Assert\NotBlank] #[
            Assert\Type(type: "numeric"),
        ]
        public readonly float $longitude,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $radiusMeters,

        #[Assert\NotBlank(allowNull: true)] #[
            Assert\Type(type: "string"),
        ]
        public readonly ?string $addressDisplay = null,

        #[Assert\NotBlank(allowNull: true)] #[
            Assert\Type(type: "string"),
        ]
        public readonly ?string $ipAddress = null,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $qrTtlSeconds,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $photoRetentionDays,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $maxDevicesPerEmployee,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThan(value: 0),
        ]
        public readonly int $sameDeviceMaxEmployees,

        #[Assert\Type(type: "bool")] public readonly bool $remindMissingCheckIn,

        #[
            Assert\Type(type: "bool"),
        ]
        public readonly bool $remindMissingCheckOut,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkInReminderMinutesBefore,

        #[Assert\NotBlank] #[Assert\Type(type: "integer")] #[
            Assert\GreaterThanOrEqual(value: 0),
        ]
        public readonly int $checkOutReminderMinutesBefore,

        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Type(type: "string")]
        public readonly ?string $currency = 'VND',

        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Type(type: "string")]
        public readonly ?string $receiveFromProviderWarehouseId = null,

        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Type(type: "string")]
        public readonly ?string $productionMaterialWarehouseId = null,

        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Type(type: "string")]
        public readonly ?string $productionFinishedGoodsWarehouseId = null,
    ) {}
}
