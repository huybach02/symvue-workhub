<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class StockReceiptInspectingDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Positive(groups: ['create'])]
        public ?int $providerId = null,

        #[Assert\Choice(
            choices: ['ACCEPT_SHORTAGE', 'CREATE_BACKORDER'],
            groups: ['create'],
        )]
        public ?string $shortageResolution = null,

        /** @var StockReceiptInspectingItemDTO[] */
        #[Assert\Valid(groups: ['create'])]
        #[Assert\Count(min: 1, groups: ['create'])]
        public array $items = [],
    ) {}
}
