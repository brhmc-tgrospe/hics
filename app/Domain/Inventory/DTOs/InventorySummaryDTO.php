<?php

namespace App\Domain\Inventory\DTOs;

class InventorySummaryDTO
{
    public function __construct(
        public readonly int $totalEquipment,
        public readonly int $totalSupplies,
        public readonly float $equipmentValue,
        public readonly float $suppliesValue,
        public readonly array $equipmentByCategory,
        public readonly array $suppliesByCategory
    ) {}

    public function toArray(): array
    {
        return [
            'total_equipment' => $this->totalEquipment,
            'total_supplies' => $this->totalSupplies,
            'equipment_value' => $this->equipmentValue,
            'supplies_value' => $this->suppliesValue,
            'equipment_by_category' => $this->equipmentByCategory,
            'supplies_by_category' => $this->suppliesByCategory,
        ];
    }
}
