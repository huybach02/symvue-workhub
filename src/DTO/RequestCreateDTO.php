<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class RequestCreateDTO
{
    public function __construct(
        public string $type,
        public array $payload = [],
        public array $watcherIds = [],
        public string $source = 'manual',
    ) {}
}
