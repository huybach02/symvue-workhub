<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WorkingTimeDTO
{
    public function __construct(
        #[Assert\NotBlank()]
        public readonly string $gioBatDau,

        #[Assert\NotBlank()]
        public readonly string $gioKetThuc,

        #[Assert\Type(type: "string")]
        public readonly string $ghiChu,
    ) {}
}
