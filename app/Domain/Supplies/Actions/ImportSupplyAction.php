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
        $stockNumber = trim((string) ($dto->stock_number ?? ''));

        // Match and update ONLY if a non-empty stock_number is provided
        if ($stockNumber !== '') {
            $query = Supply::where('stock_number', $stockNumber);

            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }

            $supply = $query->first();
        }

        if ($supply) {
            return ['record' => $this->updateAction->execute($supply, $dto), 'action' => 'updated'];
        }

        return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
    }
}
