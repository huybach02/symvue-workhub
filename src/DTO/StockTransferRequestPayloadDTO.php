<?php

declare(strict_types=1);

namespace App\DTO;

use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class StockTransferRequestPayloadDTO
{
    public function __construct(
        public ?int $destinationWarehouseId = null,
        public array $items = [],
        public ?string $reason = null,
        public ?string $note = null,
    ) {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!$this->destinationWarehouseId) {
            $context->buildViolation('Vui lòng chọn kho đích')
                ->atPath('destinationWarehouseId')
                ->addViolation();
        }

        if (trim((string) $this->reason) === '') {
            $context->buildViolation('Vui lòng nhập lý do chuyển kho')
                ->atPath('reason')
                ->addViolation();
        }

        if (mb_strlen(trim((string) $this->reason)) > 1000) {
            $context->buildViolation('Lý do chuyển kho không được vượt quá 1000 ký tự')
                ->atPath('reason')
                ->addViolation();
        }

        if (mb_strlen(trim((string) $this->note)) > 1000) {
            $context->buildViolation('Ghi chú không được vượt quá 1000 ký tự')
                ->atPath('note')
                ->addViolation();
        }

        if ($this->items === []) {
            $context->buildViolation('Vui lòng chọn ít nhất 1 lô hàng cần chuyển')
                ->atPath('items')
                ->addViolation();

            return;
        }

        $balanceIds = [];
        foreach ($this->items as $index => $item) {
            if (!is_array($item)) {
                $context->buildViolation('Dòng chuyển kho không hợp lệ')
                    ->atPath(sprintf('items[%d]', $index))
                    ->addViolation();
                continue;
            }

            $lineId = trim((string) ($item['lineId'] ?? ''));
            if ($lineId === '' || !Uuid::isValid($lineId)) {
                $context->buildViolation('Mã dòng chuyển kho phải là UUID hợp lệ')
                    ->atPath(sprintf('items[%d].lineId', $index))
                    ->addViolation();
            }

            $balanceId = (int) ($item['sourceBalanceId'] ?? 0);
            if ($balanceId <= 0) {
                $context->buildViolation('Vui lòng chọn lô hàng cần chuyển')
                    ->atPath(sprintf('items[%d].sourceBalanceId', $index))
                    ->addViolation();
            } elseif (isset($balanceIds[$balanceId])) {
                $context->buildViolation('Không được chọn trùng lô hàng')
                    ->atPath(sprintf('items[%d].sourceBalanceId', $index))
                    ->addViolation();
            }
            $balanceIds[$balanceId] = true;

            $quantity = $item['quantity'] ?? null;
            if ($quantity === null || $quantity === '' || !is_numeric($quantity) || (float) $quantity <= 0) {
                $context->buildViolation('Số lượng chuyển phải lớn hơn 0')
                    ->atPath(sprintf('items[%d].quantity', $index))
                    ->addViolation();
            }
        }
    }

    public function toArray(): array
    {
        return [
            'destinationWarehouseId' => $this->destinationWarehouseId,
            'items' => array_map(
                static fn (array $item): array => [
                    'lineId' => trim((string) ($item['lineId'] ?? '')),
                    'sourceBalanceId' => (int) ($item['sourceBalanceId'] ?? 0),
                    'quantity' => (string) ($item['quantity'] ?? '0'),
                ],
                array_filter($this->items, 'is_array'),
            ),
            'reason' => trim((string) $this->reason),
            'note' => trim((string) $this->note),
        ];
    }
}
