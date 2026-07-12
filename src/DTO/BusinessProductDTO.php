<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Category;
use App\Validator\EntityExists;
use Symfony\Component\Validator\Constraints as Assert;

class BusinessProductDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $code = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotNull(groups: ['create', 'update'])]
        #[Assert\Type(type: 'integer', groups: ['create', 'update'])]
        #[EntityExists(entityClass: Category::class, groups: ['create', 'update'])]
        public readonly ?int $categoryId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        #[Assert\Range(min: 0, max: 100, groups: ['create', 'update'])]
        public readonly string|int|float|null $targetProfitMargin = null,

        #[Assert\Length(max: 500, groups: ['create', 'update'])]
        public readonly ?string $description = null,

        #[Assert\Length(max: 500, groups: ['create', 'update'])]
        public readonly ?string $notes = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $imageUrl = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = 1,

        public readonly ?int $sortOrder = 0,

        #[Assert\NotNull(groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        #[Assert\Valid(groups: ['create', 'update'])]
        /** @var BusinessProductVariantDTO[]|null */
        public readonly ?array $variants = null,
    ) {}
}
