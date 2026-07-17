<?php

namespace App\Domain\Equipment\DTOs;

class EquipmentDTO
{
    public function __construct(
        public ?string $category,
        public ?string $article,
        public ?string $description,
        public ?string $date_acquired,
        public ?string $property_number,
        public ?string $serial_number,
        public ?string $unit_of_measure,
        public ?float $unit_value,
        public ?float $total_value,
        public ?int $quantity_per_property_card,
        public ?int $quantity_per_physical_count,
        public ?int $shortage_overage_qty,
        public ?float $shortage_overage_value,
        public ?string $remarks,
        public ?string $end_user,
        public ?string $status,
        public ?int $division_id,
        public ?int $area_id
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['category'] ?? null,
            $data['article'] ?? null,
            $data['description'] ?? null,
            $data['date_acquired'] ?? null,
            $data['property_number'] ?? null,
            $data['serial_number'] ?? null,
            $data['unit_of_measure'] ?? null,
            isset($data['unit_value']) ? (float)$data['unit_value'] : null,
            isset($data['total_value']) ? (float)$data['total_value'] : null,
            isset($data['quantity_per_property_card']) ? (int)$data['quantity_per_property_card'] : null,
            isset($data['quantity_per_physical_count']) ? (int)$data['quantity_per_physical_count'] : null,
            isset($data['shortage_overage_qty']) ? (int)$data['shortage_overage_qty'] : null,
            isset($data['shortage_overage_value']) ? (float)$data['shortage_overage_value'] : null,
            $data['remarks'] ?? null,
            $data['end_user'] ?? null,
            $data['status'] ?? null,
            isset($data['division_id']) ? (int)$data['division_id'] : null,
            isset($data['area_id']) ? (int)$data['area_id'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'article' => $this->article,
            'description' => $this->description,
            'date_acquired' => $this->date_acquired,
            'property_number' => $this->property_number,
            'serial_number' => $this->serial_number,
            'unit_of_measure' => $this->unit_of_measure,
            'unit_value' => $this->unit_value,
            'total_value' => $this->total_value,
            'quantity_per_property_card' => $this->quantity_per_property_card,
            'quantity_per_physical_count' => $this->quantity_per_physical_count,
            'shortage_overage_qty' => $this->shortage_overage_qty,
            'shortage_overage_value' => $this->shortage_overage_value,
            'remarks' => $this->remarks,
            'end_user' => $this->end_user,
            'status' => $this->status,
            'division_id' => $this->division_id,
            'area_id' => $this->area_id,
        ];
    }
}
