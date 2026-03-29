<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserPositionDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $departmentId = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        public readonly ?int $positionId = null,

        public readonly mixed $salary = null,
        public readonly ?array $allowances = null,

        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $effectiveFrom = null,

        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $effectiveTo = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $probationFrom = null,

        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $probationTo = null,

        public readonly mixed $insuranceSalary = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $insuranceCode = null,

        public readonly ?string $note = null,
    ) {}
}
