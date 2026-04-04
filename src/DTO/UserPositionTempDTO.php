<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserPositionTempDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $departmentId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $positionId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $startTempDate = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $startTempTime = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $endTempDate = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $endTempTime = null,

        public readonly ?bool $isDelete = null,
    ) {}
}
