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

        // Tier 1: Match and update if a non-empty stock_number is provided
        if ($stockNumber !== '') {
            $query = Supply::where('stock_number', $stockNumber);

            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }

            $supply = $query->first();
        } else {
            // Tier 2: Composite fallback match (Category + Article + Description) within Division and Area
            $query = Supply::where('category', $dto->category);

            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }

            $cleanArticle = strtolower(trim((string) ($dto->article ?? '')));
            $cleanDesc = strtolower(trim((string) ($dto->description ?? '')));

            $query->whereRaw('LOWER(TRIM(COALESCE(article, ""))) = ?', [$cleanArticle])
                  ->whereRaw('LOWER(TRIM(COALESCE(description, ""))) = ?', [$cleanDesc]);

            $matches = $query->get();

            if ($matches->count() > 1) {
                $articleDisplay = $dto->article ?: '(No Article)';
                throw new \DomainException(
                    "Multiple existing records match Category '{$dto->category}', Article '{$articleDisplay}', and Description in this area. Please assign a unique Stock Number to update."
                );
            }

            $supply = $matches->first();
        }

        if ($supply) {
            return ['record' => $this->updateAction->execute($supply, $dto), 'action' => 'updated'];
        }

        return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
    }
}
