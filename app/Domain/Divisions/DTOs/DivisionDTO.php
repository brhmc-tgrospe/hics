<?php

namespace App\Domain\Divisions\DTOs;

class DivisionDTO
{
    public function __construct(
        public string $div_code,
        public string $div_name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            div_code: $data['div_code'],
            div_name: $data['div_name'],
        );
    }
}
