<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryDTO
{
    public function __construct(

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 3, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?string $type = null,

        public readonly ?int $status = null,

        public readonly ?int $parentId = null,
    ) {}
}
