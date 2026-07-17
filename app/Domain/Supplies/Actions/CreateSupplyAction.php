<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;

class CreateSupplyAction
{
    public function execute(SupplyDTO $dto): Supply
    {
        $data = $dto->toArray();
        if (auth()->check()) {
            $data['user_id'] = auth()->id();
            $data['department_id'] = auth()->user()->department_id;
        }
        return Supply::create($data);
    }
}
