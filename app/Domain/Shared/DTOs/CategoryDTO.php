<?php

namespace App\Domain\Shared\DTOs;

class CategoryDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $code = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            type: strtolower(trim($data['type'] ?? 'equipment')),
            code: isset($data['code']) && trim($data['code']) !== '' ? trim($data['code']) : null,
        );
    }
}
