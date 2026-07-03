<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\EntityExists;
use App\Entity\Unit;

class RecipeDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'integer', groups: ['create', 'update'])]
        #[EntityExists(entityClass: Unit::class, groups: ['create', 'update'])]
        public readonly ?int $outputUnitId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        #[Assert\GreaterThan(value: 0, groups: ['create', 'update'])]
        public readonly string|int|float|null $outputQuantity = '1',

        #[Assert\Valid(groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        /** @var \App\DTO\RecipeItemDTO[]|null */
        public readonly ?array $items = null,

        public readonly ?int $version = 1,

        public readonly ?string $notes = null,
    ) {}
}
