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

    public function execute(SupplyDTO $dto): array
    {
        $supply = null;

        // Match by stock_number if provided
        if (!empty($dto->stock_number)) {
            $supply = Supply::where('stock_number', $dto->stock_number)->first();
        }

        // Fallback: Match by division, area, and description/article if stock_number is not present
        if (!$supply && !empty($dto->division_id) && !empty($dto->area_id) && !empty($dto->description)) {
            $query = Supply::where('division_id', $dto->division_id)
                ->where('area_id', $dto->area_id)
                ->where('description', $dto->description);
            
            if (!empty($dto->article)) {
                $query->where('article', $dto->article);
            }
            $supply = $query->first();
        }

        if ($supply) {
            return ['record' => $this->updateAction->execute($supply, $dto), 'action' => 'updated'];
        } else {
            return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
        }
    }
}
