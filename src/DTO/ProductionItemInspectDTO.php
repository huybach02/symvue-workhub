<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ProductionItemInspectDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Uuid(groups: ['create'])]
        public ?string $clientRequestUuid = null,

        #[Assert\Choice(choices: ['ACCEPT_SHORTAGE', 'SUPPLEMENT_LATER'], groups: ['create'])]
        public ?string $shortageResolution = null,

        /** @var ProductionItemInspectLotDTO[] */
        #[Assert\Valid(groups: ['create'])]
        #[Assert\Count(min: 1, groups: ['create'])]
        public array $lots = [],
    ) {
    }
}
