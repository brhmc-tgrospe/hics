<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;

class ImportEquipmentAction
{
    public function __construct(
        private CreateEquipmentAction $createAction,
        private UpdateEquipmentAction $updateAction
    ) {}

    public function execute(EquipmentDTO $dto): array
    {
        $equipment = null;
        $serialNumber = $dto->serial_number ? trim($dto->serial_number) : null;
        $propertyNumber = $dto->property_number ? trim($dto->property_number) : null;

        $hasIdentifier = ($serialNumber !== null && $serialNumber !== '') || ($propertyNumber !== null && $propertyNumber !== '');

        $scopedQuery = function () use ($dto) {
            $query = Equipment::query();
            if (!empty($dto->division_id)) {
                $query->where('division_id', $dto->division_id);
            }
            if (!empty($dto->area_id)) {
                $query->where('area_id', $dto->area_id);
            }
            return $query;
        };

        // Tier 1: Match by serial_number if provided
        if ($serialNumber !== null && $serialNumber !== '') {
            $equipment = $scopedQuery()->where('serial_number', $serialNumber)->first();
        }

        // Tier 2: Match by property_number if serial_number wasn't matched/provided
        if (!$equipment && $propertyNumber !== null && $propertyNumber !== '') {
            $equipment = $scopedQuery()->where('property_number', $propertyNumber)->first();
        }

        // Tier 3: Composite fallback (Category + Article + Description) only when NO identifier was provided
        if (!$equipment && !$hasIdentifier) {
            $cleanArticle = strtolower(trim((string) ($dto->article ?? '')));
            $cleanDesc = strtolower(trim((string) ($dto->description ?? '')));

            $matches = $scopedQuery()
                ->where('category', $dto->category)
                ->whereRaw('LOWER(TRIM(COALESCE(article, ""))) = ?', [$cleanArticle])
                ->whereRaw('LOWER(TRIM(COALESCE(description, ""))) = ?', [$cleanDesc])
                ->get();

            if ($matches->count() > 1) {
                $articleDisplay = $dto->article ?: '(No Article)';
                throw new \DomainException(
                    "Multiple existing equipment records match Category '{$dto->category}', Article '{$articleDisplay}', and Description in this area. Please assign a unique Serial Number or Property Number to update."
                );
            }

            $equipment = $matches->first();
        }

        if ($equipment) {
            return ['record' => $this->updateAction->execute($equipment, $dto), 'action' => 'updated'];
        }

        return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
    }
}
