<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Unit;
use App\Validator\EntityExists;
use Symfony\Component\Validator\Constraints as Assert;

class BusinessProductVariantDTO
{
    public function __construct(
        public readonly ?int $id = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $code = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 1, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'integer', groups: ['create', 'update'])]
        #[EntityExists(entityClass: Unit::class, groups: ['create', 'update'])]
        public readonly ?int $unitId = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $barcode = null,

        public readonly ?bool $isDefault = false,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = 1,

        public readonly ?int $sortOrder = 0,

        #[Assert\NotNull(groups: ['create', 'update'])]
        #[Assert\Valid(groups: ['create', 'update'])]
        public readonly ?BusinessProductVariantRecipeDTO $recipe = null,

        #[Assert\NotNull(groups: ['create', 'update'])]
        #[Assert\Valid(groups: ['create', 'update'])]
        public readonly ?BusinessProductVariantPriceDTO $priceConfig = null,

        #[Assert\Length(max: 10, groups: ['create', 'update'])]
        public readonly ?string $currency = 'VND',
    ) {}
}
