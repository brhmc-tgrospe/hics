<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Shared\Models\Category;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Supply\Models\SupplyReport;
use App\Models\User;
use App\Models\Division;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

class RecycleBinController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'categories');
        $perPage = $request->query('per_page', 10);
        
        $data = null;
        
        switch ($tab) {
            case 'categories':
                $data = Category::onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'type' => 'categories',
                ]);
                break;
            case 'equipment':
                $categories = Category::pluck('name', 'code')->toArray();
                $data = Equipment::onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'category' => $categories[$item->category] ?? $item->category,
                    'article' => $item->article,
                    'type' => 'equipment',
                ]);
                break;
            case 'supplies':
                $categories = Category::pluck('name', 'code')->toArray();
                $data = Supply::onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'category' => $categories[$item->category] ?? $item->category,
                    'article' => $item->article,
                    'type' => 'supplies',
                ]);
                break;
            case 'reports':
                // Reuse union logic for reports, but adding onlyTrashed conditions
                $supplyQuery = DB::table('supply_reports')
                    ->whereNotNull('deleted_at')
                    ->select('id', 'category', 'date_of_accountability', 'year_of_report', 'file_path', 'report_type', 'scope_id', 'user_id', 'created_at', DB::raw("'supply' as report_model_type"), 'report_period', 'custom_month', 'deleted_at');

                $equipmentQuery = DB::table('equipment_reports')
                    ->whereNotNull('deleted_at')
                    ->select('id', 'category', 'date_of_accountability', 'year_of_report', 'file_path', 'report_type', 'scope_id', 'user_id', 'created_at', DB::raw("'equipment' as report_model_type"), DB::raw("NULL as report_period"), DB::raw("NULL as custom_month"), 'deleted_at');

                $query = $supplyQuery->unionAll($equipmentQuery);
                
                $wrappedQuery = DB::table(DB::raw("({$query->toSql()}) as reports"))
                    ->mergeBindings($query);

                $paginated = $wrappedQuery->orderBy('deleted_at', 'desc')->paginate($perPage);

                $categoryNames = Category::pluck('name', 'code')->toArray();
                $divisionNames = Division::pluck('div_name', 'id')->toArray();
                $areaNames = Area::pluck('area_name', 'id')->toArray();
                $areaDivisions = Area::pluck('division_id', 'id')->toArray();
                
                $data = $paginated->through(function($report) use ($categoryNames, $divisionNames, $areaNames, $areaDivisions) {
                    $scopeStr = '';
                    if ($report->report_type === 'Division') {
                        $scopeStr = $divisionNames[$report->scope_id] ?? '';
                    } elseif ($report->report_type === 'Area') {
                        $divName = '';
                        if (isset($areaDivisions[$report->scope_id])) {
                            $divName = $divisionNames[$areaDivisions[$report->scope_id]] ?? '';
                        }
                        $areaName = $areaNames[$report->scope_id] ?? '';
                        $scopeStr = "{$divName} | {$areaName}";
                    }

                    $catName = $categoryNames[$report->category] ?? $report->category;
                    
                    $name = '';
                    if ($report->report_model_type === 'equipment') {
                        $parts = ['RCPPE', $report->report_type ?: 'General', $catName];
                        if ($scopeStr) $parts[] = $scopeStr;
                        $parts[] = $report->year_of_report;
                        $name = implode(' ', array_filter($parts));
                    } else {
                        $parts = ['RCPI', $report->report_type ?: 'General', $catName];
                        if ($scopeStr) $parts[] = $scopeStr;
                        
                        if ($report->report_period === 'Custom Month') {
                            $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                            $parts[] = $months[$report->custom_month - 1] ?? '';
                        } elseif ($report->report_period) {
                            $parts[] = $report->report_period;
                        }
                        $parts[] = $report->year_of_report;
                        $name = implode(' ', array_filter($parts));
                    }

                    return [
                        'id' => $report->id,
                        'name' => $name,
                        'created_at' => date('Y-m-d H:i:s', strtotime($report->created_at)),
                        'report_model_type' => $report->report_model_type,
                        'type' => 'reports'
                    ];
                });
                break;
            case 'users':
                $data = User::onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'first_name' => $item->first_name,
                    'last_name' => $item->last_name,
                    'username' => $item->username,
                    'type' => 'users',
                ]);
                break;
            case 'divisions':
                $data = Division::onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'code' => $item->div_code,
                    'name' => $item->div_name,
                    'type' => 'divisions',
                ]);
                break;
            case 'areas':
                $data = Area::with('division')->onlyTrashed()->paginate($perPage)->through(fn($item) => [
                    'id' => $item->id,
                    'area_name' => $item->area_name,
                    'division' => $item->division ? $item->division->div_name : '',
                    'type' => 'areas',
                ]);
                break;
        }

        return Inertia::render('RecycleBin/Index', [
            'tab' => $tab,
            'data' => $data,
            'filters' => $request->only(['per_page']),
        ]);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|string',
            'items.*.report_model_type' => 'nullable|string'
        ]);

        $count = 0;
        foreach ($validated['items'] as $item) {
            $modelClass = $this->getModelClass($item['type'], $item['report_model_type'] ?? null);
            if ($modelClass) {
                $record = $modelClass::onlyTrashed()->find($item['id']);
                if ($record) {
                    $record->restore();
                    $count++;
                }
            }
        }

        return redirect()->back()->with('success', "{$count} items restored successfully.");
    }

    public function forceDelete(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|string',
            'items.*.report_model_type' => 'nullable|string'
        ]);

        $count = 0;
        $failedCount = 0;
        foreach ($validated['items'] as $item) {
            $modelClass = $this->getModelClass($item['type'], $item['report_model_type'] ?? null);
            if ($modelClass) {
                $record = $modelClass::onlyTrashed()->find($item['id']);
                if ($record) {
                    try {
                        $record->forceDelete();
                        $count++;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($e->getCode() == 23000) {
                            $failedCount++;
                        } else {
                            throw $e;
                        }
                    }
                }
            }
        }

        if ($failedCount > 0) {
            if ($count > 0) {
                return redirect()->back()->with('error', "{$count} items deleted. {$failedCount} item(s) could not be deleted because they are still referenced by other records.");
            }
            return redirect()->back()->with('error', "Cannot delete selected item(s) because they are referenced by other records (e.g. Area, Equipment, etc.). Please permanently delete those related records first.");
        }

        return redirect()->back()->with('success', "{$count} items permanently deleted.");
    }

    private function getModelClass($type, $reportModelType = null)
    {
        switch ($type) {
            case 'categories': return Category::class;
            case 'equipment': return Equipment::class;
            case 'supplies': return Supply::class;
            case 'reports': 
                return $reportModelType === 'equipment' ? EquipmentReport::class : SupplyReport::class;
            case 'users': return User::class;
            case 'divisions': return Division::class;
            case 'areas': return Area::class;
            default: return null;
        }
    }
}
