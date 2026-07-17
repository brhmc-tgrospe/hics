<?php

namespace App\Domain\Users\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $contact_number,
        public readonly ?int $department_id,
        public readonly ?string $password = null,
        public readonly ?string $role = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            username: $data['username'],
            email: $data['email'],
            contact_number: $data['contact_number'] ?? null,
            department_id: $data['department_id'] ?? null,
            password: $data['password'] ?? null,
            role: $data['role'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'department_id' => $this->department_id,
        ];

        if ($this->password) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($this->password);
        }

        return $data;
    }
}
