<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;

class UpdateEquipmentAction
{
    public function execute(Equipment $equipment, EquipmentDTO $dto, bool $filterNulls = true): Equipment
    {
        $data = $dto->toArray();
        if ($filterNulls) {
            // Only update fields that have non-null values from the import
            // This prevents overwriting existing data with nulls on partial re-imports
            $data = array_filter($data, fn($value) => $value !== null);
        }
        $equipment->update($data);
        return $equipment;
    }
}
