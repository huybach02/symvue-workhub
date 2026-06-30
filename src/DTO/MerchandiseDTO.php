<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MerchandiseDTO
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
        public readonly ?int $categoryId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: ['ingredient', 'finished_product'], groups: ['create', 'update'])]
        public readonly ?string $type = null,

        public readonly ?string $profit = null,

        public readonly ?string $description = null,

        public readonly ?string $notes = null,

        public readonly ?string $stockAlertQuantity = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = null,

        public readonly ?int $baseUnitId = null,

        public readonly ?array $conversions = null,

        public readonly ?array $providers = null,

        public readonly ?bool $isSingleUnit = false,
    ) {}
}
