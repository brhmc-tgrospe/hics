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

    public function execute(EquipmentDTO $dto): array
    {
        $equipment = null;
        $serialNumber = trim((string) ($dto->serial_number ?? ''));
        $propertyNumber = trim((string) ($dto->property_number ?? ''));

        // Prioritize matching by serial_number if non-empty
        if ($serialNumber !== '') {
            $query = Equipment::where('serial_number', $serialNumber);
            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }
            $equipment = $query->first();
        }

        // Fallback to matching by property_number if serial_number isn't found/provided
        if (!$equipment && $propertyNumber !== '') {
            $query = Equipment::where('property_number', $propertyNumber);
            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }
            $equipment = $query->first();
        }

        if ($equipment) {
            return ['record' => $this->updateAction->execute($equipment, $dto), 'action' => 'updated'];
        }

        return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
    }
}
