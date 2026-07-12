<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BusinessProductVariantPriceDTO
{
    public function __construct(
        public readonly ?int $id = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        #[Assert\GreaterThan(value: 0, groups: ['create', 'update'])]
        public readonly string|int|float|null $price = null,

        #[Assert\Length(max: 10, groups: ['create', 'update'])]
        public readonly ?string $currency = 'VND',

        public readonly ?string $effectiveFrom = null,

        public readonly ?string $effectiveTo = null,

        public readonly ?bool $isCurrent = true,

        public readonly ?string $note = null,
    ) {}
}
