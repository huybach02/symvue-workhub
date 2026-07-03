<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\EntityExists;
use App\Entity\WorkShift;
use App\Entity\User;

class WorkScheduleParttimeAssignDTO
{
    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Type(type: "integer")]
        #[EntityExists(entityClass: WorkShift::class)]
        public readonly int $workShiftId,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "array")]
        #[Assert\All([
            new EntityExists(entityClass: User::class)
        ])]
        public readonly array $userIds,

        #[Assert\NotBlank()]
        #[Assert\Type(type: "string")]
        public readonly string $date,
    ) {}
}
