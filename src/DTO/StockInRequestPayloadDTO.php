<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class StockInRequestPayloadDTO
{
    /**
     * @param array|null $providers
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        public ?array $providers = null,
    ) {}

    #[Assert\Callback]
    public function validateStructure(ExecutionContextInterface $context): void
    {
        if (!is_array($this->providers) || $this->providers === []) {
            $context->buildViolation('Vui lòng thêm ít nhất 1 nhà cung cấp')
                ->atPath('providers')
                ->addViolation();

            return;
        }

        foreach ($this->providers as $providerIndex => $providerGroup) {
            if (!is_array($providerGroup)) {
                $context->buildViolation('Dữ liệu nhà cung cấp không hợp lệ')
                    ->atPath(sprintf('providers[%d]', $providerIndex))
                    ->addViolation();
                continue;
            }

            $providerId = $providerGroup['providerId'] ?? null;
            if ($providerId === null || $providerId === '') {
                $context->buildViolation('Vui lòng chọn nhà cung cấp')
                    ->atPath(sprintf('providers[%d].providerId', $providerIndex))
                    ->addViolation();
            }

            $items = $providerGroup['items'] ?? null;
            if (!is_array($items) || $items === []) {
                $context->buildViolation('Vui lòng thêm ít nhất 1 nguyên liệu/thành phẩm')
                    ->atPath(sprintf('providers[%d].items', $providerIndex))
                    ->addViolation();
                continue;
            }

            foreach ($items as $itemIndex => $item) {
                if (!is_array($item)) {
                    $context->buildViolation('Dòng hàng không hợp lệ')
                        ->atPath(sprintf('providers[%d].items[%d]', $providerIndex, $itemIndex))
                        ->addViolation();
                    continue;
                }

                if (($item['merchandiseId'] ?? null) === null || $item['merchandiseId'] === '') {
                    $context->buildViolation('Vui lòng chọn nguyên liệu/thành phẩm')
                        ->atPath(sprintf('providers[%d].items[%d].merchandiseId', $providerIndex, $itemIndex))
                        ->addViolation();
                }

                $quantity = $item['quantity'] ?? null;
                if ($quantity === null || $quantity === '' || !is_numeric($quantity) || (float) $quantity <= 0) {
                    $context->buildViolation('Số lượng phải lớn hơn 0')
                        ->atPath(sprintf('providers[%d].items[%d].quantity', $providerIndex, $itemIndex))
                        ->addViolation();
                }

                if (($item['unitId'] ?? null) === null || $item['unitId'] === '') {
                    $context->buildViolation('Vui lòng chọn đơn vị tính')
                        ->atPath(sprintf('providers[%d].items[%d].unitId', $providerIndex, $itemIndex))
                        ->addViolation();
                }

                $price = $item['price'] ?? null;
                if ($price === null || $price === '' || !is_numeric($price) || (float) $price < 0) {
                    $context->buildViolation('Giá nhập phải lớn hơn hoặc bằng 0')
                        ->atPath(sprintf('providers[%d].items[%d].price', $providerIndex, $itemIndex))
                        ->addViolation();
                }
            }
        }
    }

    public function toArray(): array
    {
        $providers = [];

        foreach ($this->providers ?? [] as $providerGroup) {
            if (!is_array($providerGroup)) {
                continue;
            }

            $items = [];
            foreach ($providerGroup['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $items[] = [
                    'merchandiseId' => (int) ($item['merchandiseId'] ?? 0) ?: null,
                    'quantity' => is_numeric($item['quantity'] ?? null)
                        ? (string) $item['quantity']
                        : null,
                    'unitId' => (int) ($item['unitId'] ?? 0) ?: null,
                    'price' => is_numeric($item['price'] ?? null)
                        ? (string) $item['price']
                        : null,
                    'currency' => trim((string) ($item['currency'] ?? 'VND')),
                    'factorToBase' => is_numeric($item['factorToBase'] ?? null)
                        ? (float) $item['factorToBase']
                        : null,
                    'note' => trim((string) ($item['note'] ?? '')),
                ];
            }

            $providers[] = [
                'providerId' => (int) ($providerGroup['providerId'] ?? 0) ?: null,
                'items' => $items,
            ];
        }

        return [
            'providers' => $providers,
        ];
    }
}
