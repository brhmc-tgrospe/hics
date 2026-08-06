<?php

namespace App\Domain\Shared\DTOs;

class CategoryDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $code = null,
        public bool $has_expiration_date = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            type: strtolower(trim($data['type'] ?? 'equipment')),
            code: isset($data['code']) && trim($data['code']) !== '' ? trim($data['code']) : null,
            has_expiration_date: filter_var($data['has_expiration_date'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }
}
