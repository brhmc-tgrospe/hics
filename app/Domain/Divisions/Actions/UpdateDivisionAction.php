<?php

namespace App\Domain\Divisions\Actions;

use App\Models\Division;
use App\Domain\Divisions\DTOs\DivisionDTO;

class UpdateDivisionAction
{
    public function execute(Division $division, DivisionDTO $dto): Division
    {
        $division->update([
            'div_code' => $dto->div_code,
            'div_name' => $dto->div_name,
        ]);

        return $division;
    }
}
