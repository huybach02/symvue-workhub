<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Merchandise;
use App\Entity\Unit;
use App\Validator\EntityExists;
use Symfony\Component\Validator\Constraints as Assert;

class BusinessProductVariantRecipeItemDTO
{
    public function __construct(
        public readonly ?int $id = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'integer', groups: ['create', 'update'])]
        #[EntityExists(entityClass: Merchandise::class, groups: ['create', 'update'])]
        public readonly ?int $finishedProductId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        #[Assert\GreaterThan(value: 0, groups: ['create', 'update'])]
        public readonly string|int|float|null $quantity = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'integer', groups: ['create', 'update'])]
        #[EntityExists(entityClass: Unit::class, groups: ['create', 'update'])]
        public readonly ?int $unitId = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        #[Assert\GreaterThanOrEqual(value: 0, groups: ['create', 'update'])]
        public readonly string|int|float|null $wasteRate = '0',

        public readonly ?string $notes = null,

        public readonly ?int $sortOrder = null,
    ) {}
}
