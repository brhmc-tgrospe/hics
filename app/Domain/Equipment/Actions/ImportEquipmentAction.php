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

        // Prioritize matching by serial_number
        if (!empty($dto->serial_number)) {
            $equipment = Equipment::where('serial_number', $dto->serial_number)->first();
        }

        // Fallback to matching by property_number if serial_number isn't found
        if (!$equipment && !empty($dto->property_number)) {
            $equipment = Equipment::where('property_number', $dto->property_number)->first();
        }

        // Fallback: Match by division, area, and description/article
        if (!$equipment && !empty($dto->division_id) && !empty($dto->area_id) && !empty($dto->description)) {
            $query = Equipment::where('division_id', $dto->division_id)
                ->where('area_id', $dto->area_id)
                ->where('description', $dto->description);
            
            if (!empty($dto->article)) {
                $query->where('article', $dto->article);
            }
            $equipment = $query->first();
        }

        if ($equipment) {
            return ['record' => $this->updateAction->execute($equipment, $dto), 'action' => 'updated'];
        } else {
            return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
        }
    }
}
