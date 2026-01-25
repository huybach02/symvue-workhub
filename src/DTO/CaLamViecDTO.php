<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CaLamViecDTO
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        public readonly int $thoiGianLamViecId,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "string")]
        public readonly string $gioBatDau,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "string")]
        public readonly string $gioKetThuc,

        #[Assert\Type(type: "string")]
        public readonly string $ghiChu,
    ) {}
}
