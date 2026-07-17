<?php

namespace App\Domain\Supplies\DTOs;

class SupplyDTO
{
    public function __construct(
        public ?string $category,
        public ?string $article,
        public ?string $description,
        public ?string $stock_number,
        public ?string $unit_of_measure,
        public ?float $unit_value,
        public ?int $balance_per_card,
        public ?int $on_hand_per_count,
        public ?int $shortage_overage_qty,
        public ?float $shortage_overage_value,
        public ?float $total_amount,
        public ?string $status,
        public ?int $division_id,
        public ?int $area_id,
        public ?string $expiry_date
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['category'] ?? null,
            $data['article'] ?? null,
            $data['description'] ?? null,
            $data['stock_number'] ?? null,
            $data['unit_of_measure'] ?? null,
            isset($data['unit_value']) ? (float)$data['unit_value'] : null,
            isset($data['balance_per_card']) ? (int)$data['balance_per_card'] : null,
            isset($data['on_hand_per_count']) ? (int)$data['on_hand_per_count'] : null,
            isset($data['shortage_overage_qty']) ? (int)$data['shortage_overage_qty'] : null,
            isset($data['shortage_overage_value']) ? (float)$data['shortage_overage_value'] : null,
            isset($data['total_amount']) ? (float)$data['total_amount'] : null,
            $data['status'] ?? null,
            isset($data['division_id']) ? (int)$data['division_id'] : null,
            isset($data['area_id']) ? (int)$data['area_id'] : null,
            $data['expiry_date'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'article' => $this->article,
            'description' => $this->description,
            'stock_number' => $this->stock_number,
            'unit_of_measure' => $this->unit_of_measure,
            'unit_value' => $this->unit_value,
            'balance_per_card' => $this->balance_per_card,
            'on_hand_per_count' => $this->on_hand_per_count,
            'shortage_overage_qty' => $this->shortage_overage_qty,
            'shortage_overage_value' => $this->shortage_overage_value,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'division_id' => $this->division_id,
            'area_id' => $this->area_id,
            'expiry_date' => $this->expiry_date,
        ];
    }
}
