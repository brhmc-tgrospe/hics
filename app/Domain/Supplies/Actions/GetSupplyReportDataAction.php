<?php

namespace App\Domain\Supplies\Actions;

use App\Domain\Supplies\Models\Supply;
use App\Domain\Supply\Models\SupplyReport;
use Illuminate\Support\Facades\Storage;

class GetSupplyReportDataAction
{
    public function execute(SupplyReport $report): array
    {
        if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
            $json = Storage::disk('local')->get($report->file_path);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Snapshot missing or corrupt - reconstruct from database
        $query = Supply::where('category', $report->category);

        if ($report->report_type === 'Division') {
            $query->where('division_id', $report->scope_id);
        } elseif ($report->report_type === 'Area') {
            $query->where('area_id', $report->scope_id);
        }

        $supplies = $query->get();

        $filePath = $report->file_path;
        if (empty($filePath)) {
            $filename = 'supply_report_' . time() . '_' . uniqid() . '.json';
            $filePath = "reports/{$filename}";
            $report->update(['file_path' => $filePath]);
        }

        Storage::disk('local')->put($filePath, $supplies->toJson());

        return $supplies->toArray();
    }
}
