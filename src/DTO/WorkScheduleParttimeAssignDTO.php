<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WorkScheduleParttimeAssignDTO
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        public readonly int $workShiftId,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "array")]
        public readonly array $userIds,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "string")]
        public readonly string $date,
    ) {}
}
