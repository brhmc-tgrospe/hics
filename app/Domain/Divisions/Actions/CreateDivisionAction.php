<?php

namespace App\Domain\Divisions\Actions;

use App\Models\Division;
use App\Domain\Divisions\DTOs\DivisionDTO;

class CreateDivisionAction
{
    public function execute(DivisionDTO $dto): Division
    {
        return Division::create([
            'div_code' => $dto->div_code,
            'div_name' => $dto->div_name,
        ]);
    }
}
