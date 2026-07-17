<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;

class CreateEquipmentAction
{
    public function execute(EquipmentDTO $dto): Equipment
    {
        $data = $dto->toArray();
        if (auth()->check()) {
            $data['user_id'] = auth()->id();
            $data['division_id'] = $data['division_id'] ?? auth()->user()->division_id;
        }
        return Equipment::create($data);
    }
}
