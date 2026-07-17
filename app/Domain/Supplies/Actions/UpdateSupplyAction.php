<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;

class UpdateSupplyAction
{
    public function execute(Supply $supply, SupplyDTO $dto): Supply
    {
        $supply->update($dto->toArray());
        return $supply;
    }
}
