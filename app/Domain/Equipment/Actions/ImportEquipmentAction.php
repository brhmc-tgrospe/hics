<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;

class ImportEquipmentAction
{
    public function __construct(
        private CreateEquipmentAction $createAction,
        private UpdateEquipmentAction $updateAction
    ) {}

    public function execute(EquipmentDTO $dto): Equipment
    {
        $equipment = null;

        // Prioritize matching by serial_number
        if (!empty($dto->serial_number)) {
            $equipment = Equipment::where('serial_number', $dto->serial_number)->first();
        }

        // Fallback to matching by property_number if serial_number isn't found
        if (!$equipment && !empty($dto->property_number)) {
            $equipment = Equipment::where('property_number', $dto->property_number)->first();
        }

        if ($equipment) {
            return $this->updateAction->execute($equipment, $dto);
        } else {
            return $this->createAction->execute($dto);
        }
    }
}
