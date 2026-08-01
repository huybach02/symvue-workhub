<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class ProductionRequestPayloadDTO
{
    /**
     * @param array|null $items
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        public ?array $items = null,
    ) {}

    #[Assert\Callback]
    public function validateStructure(ExecutionContextInterface $context): void
    {
        if (!is_array($this->items) || $this->items === []) {
            $context->buildViolation('Vui lòng thêm ít nhất 1 đề xuất sản xuất thành phẩm')
                ->atPath('items')
                ->addViolation();

            return;
        }

        foreach ($this->items as $index => $item) {
            if (!is_array($item)) {
                $context->buildViolation('Dữ liệu sản xuất không hợp lệ')
                    ->atPath(sprintf('items[%d]', $index))
                    ->addViolation();
                continue;
            }

            $lineId = $item['lineId'] ?? null;
            if ($lineId !== null && $lineId !== '') {
                if (!\Ramsey\Uuid\Uuid::isValid((string) $lineId)) {
                    $context->buildViolation('Mã dòng (lineId) phải là UUID hợp lệ')
                        ->atPath(sprintf('items[%d].lineId', $index))
                        ->addViolation();
                }
            }

            if (($item['finishedProductId'] ?? null) === null || $item['finishedProductId'] === '') {
                $context->buildViolation('Vui lòng chọn thành phẩm')
                    ->atPath(sprintf('items[%d].finishedProductId', $index))
                    ->addViolation();
            }

            $quantity = $item['quantity'] ?? null;
            if ($quantity === null || $quantity === '' || !is_numeric($quantity) || (float) $quantity <= 0) {
                $context->buildViolation('Số lượng sản xuất phải lớn hơn 0')
                    ->atPath(sprintf('items[%d].quantity', $index))
                    ->addViolation();
            }

            if (($item['outputUnitId'] ?? null) === null || $item['outputUnitId'] === '') {
                $context->buildViolation('Vui lòng chọn đơn vị tính đầu ra')
                    ->atPath(sprintf('items[%d].outputUnitId', $index))
                    ->addViolation();
            }

            $expectedWastePercent = $item['expectedWastePercent'] ?? null;
            if ($expectedWastePercent !== null && $expectedWastePercent !== '' && (!is_numeric($expectedWastePercent) || (float) $expectedWastePercent < 0)) {
                $context->buildViolation('Tỷ lệ hao hụt dự kiến phải lớn hơn hoặc bằng 0')
                    ->atPath(sprintf('items[%d].expectedWastePercent', $index))
                    ->addViolation();
            }

            $materials = $item['materials'] ?? null;
            if ($materials !== null && !is_array($materials)) {
                $context->buildViolation('Danh sách nguyên liệu không hợp lệ')
                    ->atPath(sprintf('items[%d].materials', $index))
                    ->addViolation();
            } elseif (is_array($materials)) {
                foreach ($materials as $mIndex => $material) {
                    if (!is_array($material)) {
                        continue;
                    }
                    if (($material['ingredientId'] ?? null) === null || $material['ingredientId'] === '') {
                        $context->buildViolation('Vui lòng chọn nguyên liệu')
                            ->atPath(sprintf('items[%d].materials[%d].ingredientId', $index, $mIndex))
                            ->addViolation();
                    }
                    $mQty = $material['quantity'] ?? null;
                    if ($mQty === null || $mQty === '' || !is_numeric($mQty) || (float) $mQty <= 0) {
                        $context->buildViolation('Số lượng nguyên liệu phải lớn hơn 0')
                            ->atPath(sprintf('items[%d].materials[%d].quantity', $index, $mIndex))
                            ->addViolation();
                    }
                    if (($material['unitId'] ?? null) === null || $material['unitId'] === '') {
                        $context->buildViolation('Vui lòng chọn đơn vị tính nguyên liệu')
                            ->atPath(sprintf('items[%d].materials[%d].unitId', $index, $mIndex))
                            ->addViolation();
                    }
                }
            }
        }
    }

    public function toArray(): array
    {
        $items = [];

        foreach ($this->items ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $lineId = isset($item['lineId']) ? trim((string) $item['lineId']) : '';
            if ($lineId === '' || !\Ramsey\Uuid\Uuid::isValid($lineId)) {
                $lineId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            }

            $materials = [];
            foreach ($item['materials'] ?? [] as $mat) {
                if (!is_array($mat)) {
                    continue;
                }
                $materials[] = [
                    'ingredientId' => (int) ($mat['ingredientId'] ?? 0) ?: null,
                    'ingredientName' => trim((string) ($mat['ingredientName'] ?? '')),
                    'quantity' => is_numeric($mat['quantity'] ?? null) ? (float) $mat['quantity'] : null,
                    'unitId' => (int) ($mat['unitId'] ?? 0) ?: null,
                    'unitName' => trim((string) ($mat['unitName'] ?? '')),
                    'wasteRate' => is_numeric($mat['wasteRate'] ?? null) ? (float) $mat['wasteRate'] : 0,
                    'note' => trim((string) ($mat['note'] ?? '')),
                ];
            }

            $items[] = [
                'lineId' => $lineId,
                'finishedProductId' => (int) ($item['finishedProductId'] ?? 0) ?: null,
                'finishedProductName' => trim((string) ($item['finishedProductName'] ?? '')),
                'quantity' => is_numeric($item['quantity'] ?? null) ? (float) $item['quantity'] : null,
                'outputUnitId' => (int) ($item['outputUnitId'] ?? 0) ?: null,
                'outputUnitName' => trim((string) ($item['outputUnitName'] ?? '')),
                'expectedWastePercent' => is_numeric($item['expectedWastePercent'] ?? null) ? (float) $item['expectedWastePercent'] : null,
                'materials' => $materials,
            ];
        }

        return [
            'items' => $items,
        ];
    }
}
