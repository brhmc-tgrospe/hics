<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;

class DeleteEquipmentAction
{
    public function execute(Equipment $equipment): void
    {
        $equipment->delete();
    }
}
