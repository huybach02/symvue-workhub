<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductionOrderItemStatusDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: [
            'CREATED',
            'MATERIAL_ISSUED',
            'STARTED',
            'IN_PROGRESS',
            'INSPECTING',
            'COMPLETED',
        ])]
        public ?string $status = null,
    ) {
    }
}
