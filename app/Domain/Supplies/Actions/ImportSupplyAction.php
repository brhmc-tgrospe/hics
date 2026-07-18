<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;

class ImportSupplyAction
{
    public function __construct(
        private CreateSupplyAction $createAction,
        private UpdateSupplyAction $updateAction
    ) {}

    public function execute(SupplyDTO $dto): Supply
    {
        $supply = null;

        // Match by stock_number if provided
        if (!empty($dto->stock_number)) {
            $supply = Supply::where('stock_number', $dto->stock_number)->first();
        }

        if ($supply) {
            return $this->updateAction->execute($supply, $dto);
        } else {
            return $this->createAction->execute($dto);
        }
    }
}
