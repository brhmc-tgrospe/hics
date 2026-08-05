<?php

namespace App\Domain\Equipment\Actions;

use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentReport;
use Illuminate\Support\Facades\Storage;

class GetEquipmentReportDataAction
{
    public function execute(EquipmentReport $report): array
    {
        if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
            $json = Storage::disk('local')->get($report->file_path);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Snapshot missing or corrupt - reconstruct from database
        $query = Equipment::where('category', $report->category);

        if ($report->report_type === 'Division') {
            $query->where('division_id', $report->scope_id);
        } elseif ($report->report_type === 'Area') {
            $query->where('area_id', $report->scope_id);
        }

        $equipment = $query->get();

        $filePath = $report->file_path;
        if (empty($filePath)) {
            $filename = 'equipment_report_' . time() . '_' . uniqid() . '.json';
            $filePath = "reports/{$filename}";
            $report->update(['file_path' => $filePath]);
        }

        Storage::disk('local')->put($filePath, $equipment->toJson());

        return $equipment->toArray();
    }
}
