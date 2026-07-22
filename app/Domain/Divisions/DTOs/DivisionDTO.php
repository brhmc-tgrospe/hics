<?php

namespace App\Domain\Divisions\DTOs;

class DivisionDTO
{
    public function __construct(
        public string $div_code,
        public string $div_name,
        public string $head_first_name,
        public string $head_middle_initial,
        public string $head_last_name,
        public ?string $head_nominal_letters,
        public string $head_designation,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            div_code: $data['div_code'],
            div_name: $data['div_name'],
            head_first_name: $data['head_first_name'],
            head_middle_initial: $data['head_middle_initial'],
            head_last_name: $data['head_last_name'],
            head_nominal_letters: $data['head_nominal_letters'] ?? null,
            head_designation: $data['head_designation'],
        );
    }
}
