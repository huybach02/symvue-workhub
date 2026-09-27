<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DiningTableDTO
{
    public function __construct(
        #[Assert\Positive(groups: ['update'])]
        public readonly ?int $tableNumber = null,

        public readonly ?string $qrCode = null,

        public readonly ?bool $isUsing = null,

        #[Assert\Choice(choices: [0, 1], groups: ['update'])]
        public readonly ?int $status = null,

        public readonly ?int $branchId = null,
    ) {}
}
