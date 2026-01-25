<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CauHinhChungDTO
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[Assert\GreaterThan(value: 0)]
        public readonly int $soLanDangNhapSai,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[Assert\GreaterThan(value: 0)]
        public readonly int $thoiGianTamKhoaTaiKhoan,

        #[Assert\Type(type: "bool")]
        public readonly bool $xacThuc2YeuTo,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[Assert\GreaterThan(value: 0)]
        public readonly int $thoiGianHetHanMaOtp,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[Assert\GreaterThan(value: 0)]
        public readonly int $soThietBiDangNhapToiDa,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[Assert\GreaterThan(value: 0)]
        public readonly int $thoiHanXacThucLaiThietBi,

        #[Assert\Type(type: "bool")]
        public readonly bool $kiemTraThoiGianLamViec,
    ) {}
}
