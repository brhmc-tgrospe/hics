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
            'head_first_name' => $dto->head_first_name,
            'head_middle_initial' => $dto->head_middle_initial,
            'head_last_name' => $dto->head_last_name,
            'head_nominal_letters' => $dto->head_nominal_letters,
            'head_designation' => $dto->head_designation,
        ]);
    }
}
