<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockReceiptProviderStatusDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['CREATED', 'AWAITING_SHIPMENT', 'IN_TRANSIT', 'ARRIVED', 'INSPECTING', 'COMPLETED', 'CANCELLED'])]
        public ?string $status = null,
    ) {}
}
