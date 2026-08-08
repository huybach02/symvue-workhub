<?php

declare(strict_types=1);

namespace App\Exception;

class InsufficientMaterialException extends \RuntimeException
{
    /**
     * @param array<int, array{
     *     ingredientId: int|null,
     *     ingredientName: string|null,
     *     requiredBase: string,
     *     availableBase: string,
     *     shortageBase: string,
     * }> $shortages
     */
    public function __construct(
        string $message,
        private readonly array $shortages = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, array{
     *     ingredientId: int|null,
     *     ingredientName: string|null,
     *     requiredBase: string,
     *     availableBase: string,
     *     shortageBase: string,
     * }>
     */
    public function getShortages(): array
    {
        return $this->shortages;
    }
}
