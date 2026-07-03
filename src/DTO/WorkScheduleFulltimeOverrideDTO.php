<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\EntityExists;
use App\Entity\User;

class WorkScheduleFulltimeOverrideDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ["create", "update"])]
        #[Assert\Type("integer", groups: ["create", "update"])]
        #[EntityExists(entityClass: User::class, groups: ["create", "update"])]
        public readonly int $userId = 0,

        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $startDate = null,

        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $endDate = null,

        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $startTime = null,

        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $endTime = null,

        public readonly ?string $note = null,

        public readonly string $weekendOption = "keep",

        #[Assert\Type("array", groups: ["create", "update"])]
        public readonly array $selectedDates = [],

    ) {}
}
