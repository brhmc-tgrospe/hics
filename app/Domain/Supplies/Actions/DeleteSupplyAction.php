<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;

class DeleteSupplyAction
{
    public function execute(Supply $supply): void
    {
        $supply->delete();
    }
}
