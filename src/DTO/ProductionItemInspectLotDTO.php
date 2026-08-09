<?php

declare(strict_types=1);

namespace App\DTO;

use App\Class\MathHelper;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\Callback(callback: 'validateLot', groups: ['create'])]
final readonly class ProductionItemInspectLotDTO
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Uuid(groups: ['create'])]
        public ?string $clientLineUuid = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Regex(pattern: '/^\d{1,12}(?:\.\d{1,6})?$/', groups: ['create'])]
        public string|int|float|null $receivedQuantity = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Regex(pattern: '/^\d{1,12}(?:\.\d{1,6})?$/', groups: ['create'])]
        public string|int|float|null $acceptedQuantity = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create'])]
        public ?string $manufactureDate = null,

        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create'])]
        public ?string $expiryDate = null,

        #[Assert\Length(max: 100, groups: ['create'])]
        public ?string $productionLotCode = null,

        public ?string $rejectionReason = null,
        public ?string $note = null,
    ) {
    }

    public function validateLot(ExecutionContextInterface $context): void
    {
        $received = (string) $this->receivedQuantity;
        $accepted = (string) $this->acceptedQuantity;
        if (preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $received)
            && MathHelper::comp($received, '0') <= 0
        ) {
            $context->buildViolation('Số lượng nhận phải lớn hơn 0')->atPath('receivedQuantity')->addViolation();
        }
        if (preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $received)
            && preg_match('/^\d{1,12}(?:\.\d{1,6})?$/', $accepted)
            && MathHelper::comp($accepted, $received) > 0
        ) {
            $context->buildViolation('Số lượng chấp nhận không được lớn hơn số lượng nhận')->atPath('acceptedQuantity')->addViolation();
        }
    }
}
