<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;

class DeleteEquipmentAction
{
    public function execute(Equipment $equipment, ?string $deleteRemarks = null): void
    {
        if ($deleteRemarks !== null) {
            $equipment->delete_remarks = $deleteRemarks;
            $equipment->saveQuietly();
        }
        $equipment->delete();
    }
}
