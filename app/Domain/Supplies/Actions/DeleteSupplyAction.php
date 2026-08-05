<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;

class DeleteSupplyAction
{
    public function execute(Supply $supply, ?string $deleteRemarks = null): void
    {
        if ($deleteRemarks !== null) {
            $supply->delete_remarks = $deleteRemarks;
            $supply->saveQuietly();
        }
        $supply->delete();
    }
}
