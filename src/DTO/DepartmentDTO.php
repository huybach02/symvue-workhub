<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DepartmentDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $branchId = null,

        // #[Assert\NotBlank(groups: ['create', 'update'])]
        // public readonly ?int $quanLyBoPhanId = null,

        #[Assert\NotBlank(groups: ['create'])]
        public readonly ?string $tenBoPhan = null,

        #[Assert\Length(max: 500, groups: ['create', 'update'])]
        public readonly ?string $maBoPhan = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $status = null,

        public readonly ?string $ghiChu = null,

        // #[Assert\NotBlank(groups: ['create', 'update'])]
        // public readonly ?array $permissions = null,
    ) {}
}
