<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AttendanceDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ["create", "update"])]
        #[Assert\Length(min: 3, max: 255, groups: ["create", "update"])]
        public readonly ?string $qrCode = null,

        #[Assert\NotBlank(groups: ["create"])]
        public readonly ?float $latitude = null,

        #[Assert\NotBlank(groups: ["create"])]
        public readonly ?float $longitude = null,

        #[Assert\Length(max: 500, groups: ["create", "update"])]
        public readonly ?float $accuracy = null,
    ) {
    }
}
