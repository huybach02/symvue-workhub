<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UnitDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $code = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $symbol = null,

        public readonly ?int $status = null,
    ) {}
}
