<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;

class UpdateEquipmentAction
{
    public function execute(Equipment $equipment, EquipmentDTO $dto): Equipment
    {
        $equipment->update($dto->toArray());
        return $equipment;
    }
}
