<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WorkShiftStatusDTO
{
    public function __construct(
        #[Assert\Type(type: "bool")]
        public readonly bool $status,
    ) {}
}

