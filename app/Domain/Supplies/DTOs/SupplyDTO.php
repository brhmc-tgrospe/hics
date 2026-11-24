<?php

namespace App\Domain\Supplies\DTOs;

use Carbon\Carbon;

class SupplyDTO
{
    public function __construct(
        public ?string $category,
        public ?string $article,
        public ?string $description,
        public ?string $stock_number,
        public ?string $unit_of_measure,
        public ?float $unit_value,
        public ?int $balance_per_card,
        public ?int $on_hand_per_count,
        public ?int $shortage_overage_qty,
        public ?float $shortage_overage_value,
        public ?float $total_amount,
        public ?string $status,
        public ?int $division_id,
        public ?int $area_id,
        public ?string $expiry_date
    ) {}

    private static function parseNumeric(mixed $val): ?float
    {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_numeric($val)) {
            return (float)$val;
        }
        $cleaned = preg_replace('/[^\d.-]/', '', (string)$val);
        return $cleaned !== '' && is_numeric($cleaned) ? (float)$cleaned : null;
    }

    private static function parseInt(mixed $val): ?int
    {
        $num = self::parseNumeric($val);
        return $num !== null ? (int)round($num) : null;
    }

    private static function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return 'Available';
        }
        $lower = strtolower(trim($status));
        return match ($lower) {
            'depleted', 'out of stock', 'unserviceable', 'inactive' => 'Depleted',
            default => 'Available',
        };
    }

    /**
     * Normalize an expiry date input into YYYY-MM-DD format.
     */
    public static function normalizeExpiryDate(mixed $expiryDate): ?string
    {
        if ($expiryDate === null) {
            return null;
        }

        if ($expiryDate instanceof \DateTimeInterface) {
            return $expiryDate->format('Y-m-d');
        }

        $trimmed = trim((string) $expiryDate);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return $trimmed;
        }
    }

    /**
     * Format a unit value as a fixed 2-decimal string matching DECIMAL(15, 2) storage.
     */
    public static function formatUnitValue(?float $unitValue): ?string
    {
        return $unitValue !== null
            ? number_format(round($unitValue, 2), 2, '.', '')
            : null;
    }

    public static function fromArray(array $data): self
    {
        $unitValue = self::parseNumeric($data['unit_value'] ?? null);
        $unitValue = $unitValue !== null ? round($unitValue, 2) : null;
        $balancePerCard = self::parseInt($data['balance_per_card'] ?? null);
        $onHandPerCount = self::parseInt($data['on_hand_per_count'] ?? null);

        $shortageOverageQty = isset($data['shortage_overage_qty']) && $data['shortage_overage_qty'] !== null
            ? self::parseInt($data['shortage_overage_qty'])
            : (($balancePerCard !== null && $onHandPerCount !== null) ? ($balancePerCard - $onHandPerCount) : null);

        $shortageOverageValue = isset($data['shortage_overage_value']) && $data['shortage_overage_value'] !== null
            ? self::parseNumeric($data['shortage_overage_value'])
            : (($shortageOverageQty !== null && $unitValue !== null) ? round($shortageOverageQty * $unitValue, 2) : null);

        $totalAmount = isset($data['total_amount']) && $data['total_amount'] !== null
            ? self::parseNumeric($data['total_amount'])
            : (($onHandPerCount !== null && $unitValue !== null) ? round($onHandPerCount * $unitValue, 2) : null);

        $status = self::normalizeStatus($data['status'] ?? null);
        $expiryDate = self::normalizeExpiryDate($data['expiry_date'] ?? null);

        return new self(
            $data['category'] ?? null,
            $data['article'] ?? null,
            $data['description'] ?? null,
            $data['stock_number'] ?? null,
            $data['unit_of_measure'] ?? null,
            $unitValue,
            $balancePerCard,
            $onHandPerCount,
            $shortageOverageQty,
            $shortageOverageValue,
            $totalAmount,
            $status,
            isset($data['division_id']) && $data['division_id'] !== null ? self::parseInt($data['division_id']) : null,
            isset($data['area_id']) && $data['area_id'] !== null ? self::parseInt($data['area_id']) : null,
            $expiryDate
        );
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'article' => $this->article,
            'description' => $this->description,
            'stock_number' => $this->stock_number,
            'unit_of_measure' => $this->unit_of_measure,
            'unit_value' => $this->unit_value,
            'balance_per_card' => $this->balance_per_card,
            'on_hand_per_count' => $this->on_hand_per_count,
            'shortage_overage_qty' => $this->shortage_overage_qty,
            'shortage_overage_value' => $this->shortage_overage_value,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'division_id' => $this->division_id,
            'area_id' => $this->area_id,
            'expiry_date' => $this->expiry_date,
        ];
    }
}
