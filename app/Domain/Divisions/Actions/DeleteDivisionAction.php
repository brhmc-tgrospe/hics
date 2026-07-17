<?php

namespace App\Domain\Divisions\Actions;

use App\Models\Division;

class DeleteDivisionAction
{
    public function execute(Division $division): void
    {
        $division->delete();
    }
}
