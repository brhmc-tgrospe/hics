<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;

class UpdateSupplyAction
{
    public function execute(Supply $supply, SupplyDTO $dto): Supply
    {
        // Only update fields that have non-null values from the import
        // This prevents overwriting existing data with nulls on partial re-imports
        $data = array_filter($dto->toArray(), fn($value) => $value !== null);
        $supply->update($data);
        return $supply;
    }
}
