<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Shared\Models\Category;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportSupplyAction
{
    public function execute(array $filters): StreamedResponse
    {
        $format = $filters['format'] ?? 'template';
        $divisionId = $filters['division_id'] ?? null;
        $areaId = $filters['area_id'] ?? null;
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;

        $query = Supply::query()->with(['division', 'area']);

        if (!empty($divisionId)) {
            $query->where('division_id', $divisionId);
        }

        if (!empty($areaId)) {
            $query->where('area_id', $areaId);
        }

        if (!empty($category) && $category !== 'All') {
            $query->where('category', $category);
        }

        if (!empty($status) && $status !== 'All') {
            $query->where('status', $status);
        }

        $query->orderBy('division_id')->orderBy('area_id')->orderBy('article');

        $categoriesMap = Category::where('type', 'supply')->pluck('name', 'code')->toArray();

        $timestamp = date('Y-m-d_His');
        $filename = "supplies_export_{$format}_{$timestamp}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query, $format, $categoriesMap) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($format === 'full') {
                $columns = [
                    'ID',
                    'Category Code',
                    'Category Name',
                    'Article',
                    'Description',
                    'Stock Number',
                    'Expiry Date',
                    'Unit of Measure',
                    'Unit Value',
                    'Balance per Card',
                    'On Hand per Count',
                    'Shortage/Overage Qty',
                    'Shortage/Overage Value',
                    'Total Amount',
                    'Status',
                    'Division ID',
                    'Division Name',
                    'Area ID',
                    'Area Name',
                    'Created At',
                    'Updated At',
                ];
            } else {
                // Matches the import template format exactly
                $columns = [
                    'category',
                    'article',
                    'description',
                    'stock_number',
                    'expiry_date',
                    'unit_of_measure',
                    'unit_value',
                    'balance_per_card',
                    'on_hand_per_count',
                    'status',
                    'division_id',
                    'area_id',
                ];
            }

            fputcsv($output, $columns);

            foreach ($query->cursor() as $item) {
                $expiryFormatted = $item->expiry_date instanceof \DateTimeInterface 
                    ? $item->expiry_date->format('Y-m-d') 
                    : ($item->expiry_date ? substr((string)$item->expiry_date, 0, 10) : '');

                if ($format === 'full') {
                    $categoryName = $categoriesMap[$item->category] ?? $item->category;
                    fputcsv($output, [
                        $item->id,
                        $item->category,
                        $categoryName,
                        $item->article,
                        $item->description,
                        $item->stock_number,
                        $expiryFormatted,
                        $item->unit_of_measure,
                        $item->unit_value,
                        $item->balance_per_card,
                        $item->on_hand_per_count,
                        $item->shortage_overage_qty,
                        $item->shortage_overage_value,
                        $item->total_amount,
                        $item->status,
                        $item->division_id,
                        $item->division?->div_name ?? '',
                        $item->area_id,
                        $item->area?->area_name ?? '',
                        $item->created_at?->toDateTimeString() ?? '',
                        $item->updated_at?->toDateTimeString() ?? '',
                    ]);
                } else {
                    fputcsv($output, [
                        $item->category,
                        $item->article,
                        $item->description,
                        $item->stock_number,
                        $expiryFormatted,
                        $item->unit_of_measure,
                        $item->unit_value,
                        $item->balance_per_card,
                        $item->on_hand_per_count,
                        $item->status,
                        $item->division_id,
                        $item->area_id,
                    ]);
                }
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
