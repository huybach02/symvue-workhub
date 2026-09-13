<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\BusinessProduct;
use App\Entity\BusinessProductVariant;
use App\Validator\EntityExists;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSaleOrderItemDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type(type: 'integer', groups: ['create'])]
        #[EntityExists(entityClass: BusinessProduct::class, groups: ['create'])]
        public readonly ?int $productId = null,

        #[Assert\Type(type: 'integer', groups: ['create'])]
        #[EntityExists(entityClass: BusinessProductVariant::class, groups: ['create'])]
        public readonly ?int $variantId = null,

        public readonly ?string $productCode = null,
        public readonly ?string $productName = null,
        public readonly ?string $variantCode = null,
        public readonly ?string $variantName = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type(type: 'numeric', groups: ['create'])]
        #[Assert\Positive(groups: ['create'])]
        public readonly string|int|float|null $quantity = 1,

        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type(type: 'numeric', groups: ['create'])]
        #[Assert\PositiveOrZero(groups: ['create'])]
        public readonly string|int|float|null $price = 0,

        #[Assert\Length(max: 500, groups: ['create'])]
        public readonly ?string $note = null,
    ) {}
}
