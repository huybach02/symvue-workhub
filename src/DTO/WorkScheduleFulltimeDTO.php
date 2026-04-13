<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WorkScheduleFulltimeDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $startDate = null,

        #[Assert\NotBlank(groups: ["create", "update"])]
        public readonly ?string $endDate = null,

        #[Assert\NotNull(groups: ["create", "update"])]
        #[Assert\Count(
            min: 1,
            groups: ["create", "update"]
        )]
        public readonly array $userIds = [],
    ) {}
}
