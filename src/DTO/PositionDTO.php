<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PositionDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(min: 2, max: 255, groups: ['create', 'update'])]
        public readonly ?string $name = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $code = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(
            choices: ['FULL_TIME', 'PART_TIME', 'INTERN', 'CONTRACTOR'],
            groups: ['create', 'update'],
        )]
        public readonly ?string $employmentType = null,

        public readonly ?string $description = null,
        public readonly mixed $minSalary = null,
        public readonly mixed $maxSalary = null,

        #[Assert\Length(max: 255, groups: ['create', 'update'])]
        public readonly ?string $currency = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        public readonly mixed $probationMonths = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        public readonly mixed $probationSalaryRate = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        public readonly mixed $annualLeaveDays = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        public readonly mixed $reviewCycleMonths = null,

        #[Assert\Type(type: 'numeric', groups: ['create', 'update'])]
        public readonly mixed $noticePeriodDays = null,

        public readonly ?int $isManager = null,

        public readonly ?array $allowances = null,

        #[Assert\NotBlank(groups: ['create', 'update'])]
        #[Assert\Choice(choices: [0, 1], groups: ['create', 'update'])]
        public readonly ?int $status = null,
    ) {}
}
