<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class LeaveRequestPayloadDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $leaveType = null,

        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $startDate = null,

        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $endDate = null,

        #[Assert\NotBlank]
        public ?string $reason = null,

        public ?string $handoverNote = null,
    ) {}

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if (!$this->startDate || !$this->endDate) {
            return;
        }

        $start = \DateTime::createFromFormat('Y-m-d', $this->startDate);
        $end = \DateTime::createFromFormat('Y-m-d', $this->endDate);

        if (!$start || !$end) {
            return;
        }

        if ($start > $end) {
            $context->buildViolation('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc')
                ->atPath('startDate')
                ->addViolation();
        }
    }

    public function toArray(): array
    {
        $start = \DateTime::createFromFormat('Y-m-d', (string) $this->startDate);
        $end = \DateTime::createFromFormat('Y-m-d', (string) $this->endDate);

        return [
            'leaveType' => trim((string) $this->leaveType),
            'startDate' => $start ? $start->format('Y-m-d') : (string) $this->startDate,
            'endDate' => $end ? $end->format('Y-m-d') : (string) $this->endDate,
            'reason' => trim((string) $this->reason),
            'handoverNote' => trim((string) ($this->handoverNote ?? '')),
        ];
    }
}
