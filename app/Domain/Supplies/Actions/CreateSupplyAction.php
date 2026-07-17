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
            $data['division_id'] = $data['division_id'] ?? auth()->user()->division_id;
        }
        return Supply::create($data);
    }
}
