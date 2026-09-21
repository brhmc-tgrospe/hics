<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Shared\Models\Category;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportEquipmentAction
{
    public function execute(array $filters): StreamedResponse
    {
        $format = $filters['format'] ?? 'template';
        $divisionId = $filters['division_id'] ?? null;
        $areaId = $filters['area_id'] ?? null;
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;

        $query = Equipment::query()->with(['division', 'area']);

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

        $categoriesMap = Category::where('type', 'equipment')->pluck('name', 'code')->toArray();

        $timestamp = date('Y-m-d_His');
        $filename = "equipment_export_{$format}_{$timestamp}.csv";

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
                    'Date Acquired',
                    'Property Number',
                    'Serial Number',
                    'Unit of Measure',
                    'Unit Value',
                    'Qty per Property Card',
                    'Qty per Physical Count',
                    'Shortage/Overage Qty',
                    'Shortage/Overage Value',
                    'Total Value',
                    'Remarks',
                    'End User',
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
                    'date_acquired',
                    'property_number',
                    'serial_number',
                    'unit_of_measure',
                    'unit_value',
                    'quantity_per_property_card',
                    'quantity_per_physical_count',
                    'remarks',
                    'end_user',
                    'status',
                    'division_id',
                    'area_id',
                ];
            }

            fputcsv($output, $columns);

            foreach ($query->cursor() as $item) {
                if ($format === 'full') {
                    $categoryName = $categoriesMap[$item->category] ?? $item->category;
                    fputcsv($output, [
                        $item->id,
                        $item->category,
                        $categoryName,
                        $item->article,
                        $item->description,
                        $item->date_acquired,
                        $item->property_number,
                        $item->serial_number,
                        $item->unit_of_measure,
                        $item->unit_value,
                        $item->quantity_per_property_card,
                        $item->quantity_per_physical_count,
                        $item->shortage_overage_qty,
                        $item->shortage_overage_value,
                        $item->total_value,
                        $item->remarks,
                        $item->end_user,
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
                        $item->date_acquired,
                        $item->property_number,
                        $item->serial_number,
                        $item->unit_of_measure,
                        $item->unit_value,
                        $item->quantity_per_property_card,
                        $item->quantity_per_physical_count,
                        $item->remarks,
                        $item->end_user,
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
