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

    private static function parseNumeric(mixed $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_numeric($val)) {
            return (float)$val;
        }
        $cleaned = preg_replace('/[^\d.-]/', '', (string)$val);
        return $cleaned !== '' && is_numeric($cleaned) ? (float)$cleaned : null;
    }

    private static function parseInt(mixed $val): ?int
    {
        $num = self::parseNumeric($val);
        return $num !== null ? (int)round($num) : null;
    }

    private static function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return 'Serviceable';
        }
        $lower = strtolower(trim($status));
        return match ($lower) {
            'unserviceable', 'damaged', 'condemned', 'depleted', 'inactive' => 'Unserviceable',
            default => 'Serviceable',
        };
    }

    public static function fromArray(array $data): self
    {
        $unitValue = self::parseNumeric($data['unit_value'] ?? null);
        $propCard = self::parseInt($data['quantity_per_property_card'] ?? null);
        $physCount = self::parseInt($data['quantity_per_physical_count'] ?? null);

        $shortageOverageQty = isset($data['shortage_overage_qty']) && $data['shortage_overage_qty'] !== null
            ? self::parseInt($data['shortage_overage_qty'])
            : (($propCard !== null && $physCount !== null) ? ($propCard - $physCount) : null);

        $shortageOverageValue = isset($data['shortage_overage_value']) && $data['shortage_overage_value'] !== null
            ? self::parseNumeric($data['shortage_overage_value'])
            : (($shortageOverageQty !== null && $unitValue !== null) ? round($shortageOverageQty * $unitValue, 2) : null);

        $totalValue = isset($data['total_value']) && $data['total_value'] !== null
            ? self::parseNumeric($data['total_value'])
            : (($physCount !== null && $unitValue !== null) ? round($physCount * $unitValue, 2) : null);

        $status = self::normalizeStatus($data['status'] ?? null);

        return new self(
            $data['category'] ?? null,
            $data['article'] ?? null,
            $data['description'] ?? null,
            $data['date_acquired'] ?? null,
            $data['property_number'] ?? null,
            $data['serial_number'] ?? null,
            $data['unit_of_measure'] ?? null,
            $unitValue,
            $totalValue,
            $propCard,
            $physCount,
            $shortageOverageQty,
            $shortageOverageValue,
            $data['remarks'] ?? null,
            $data['end_user'] ?? null,
            $status,
            isset($data['division_id']) && $data['division_id'] !== null ? self::parseInt($data['division_id']) : null,
            isset($data['area_id']) && $data['area_id'] !== null ? self::parseInt($data['area_id']) : null
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
