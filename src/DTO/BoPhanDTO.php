<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class BoPhanDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $quanLyBoPhanId = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?string $tenBoPhan = null,

        #[Assert\Length(max: 500, groups: ['create', 'update'])]
        public readonly ?string $maBoPhan = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $status = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?array $permissions = null,
    ) {}
}
