<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BusinessProductVariantRecipeDTO
{
    public function __construct(
        public readonly ?int $id = null,

        public readonly ?int $version = 1,

        #[Assert\Valid(groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        /** @var BusinessProductVariantRecipeItemDTO[]|null */
        public readonly ?array $items = null,

        public readonly ?bool $isActive = true,

        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = 1,

        public readonly ?string $notes = null,
    ) {}
}
