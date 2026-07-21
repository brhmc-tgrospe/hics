<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Supply\Models\SupplyReport;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Shared\Models\Category;
use App\Models\Division;
use App\Models\Area;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $supplyQuery = DB::table('supply_reports')
            ->select('id', 'category', 'date_of_accountability', 'year_of_report', 'file_path', 'report_type', 'scope_id', 'user_id', 'created_at', DB::raw("'supply' as type"), 'report_period', 'custom_month');

        $equipmentQuery = DB::table('equipment_reports')
            ->select('id', 'category', 'date_of_accountability', 'year_of_report', 'file_path', 'report_type', 'scope_id', 'user_id', 'created_at', DB::raw("'equipment' as type"), DB::raw("NULL as report_period"), DB::raw("NULL as custom_month"));

        $query = $supplyQuery->unionAll($equipmentQuery);

        $wrappedQuery = DB::table(DB::raw("({$query->toSql()}) as reports"))
            ->mergeBindings($query);

        // Role-Based Visibility
        if ($user->hasRole(['Encoder', 'Software']) && !$user->hasRole(['Developer', 'Superadmin', 'Admin'])) {
            $wrappedQuery->where(function($q) use ($user) {
                $q->where('report_type', 'General')
                  ->orWhere(function($subq) use ($user) {
                      $subq->where('report_type', 'Area')
                           ->where('scope_id', $user->area_id);
                  });
            });
        }

        // Toggles
        if ($request->boolean('my_division_only')) {
            $wrappedQuery->where(function($q) use ($user) {
                $areaIds = Area::where('division_id', $user->division_id)->pluck('id')->toArray();
                $q->where(function($subq) use ($user) {
                      $subq->where('report_type', 'Division')
                           ->where('scope_id', $user->division_id);
                  })
                  ->orWhere(function($subq) use ($areaIds) {
                      $subq->where('report_type', 'Area')
                           ->whereIn('scope_id', $areaIds);
                  });
            });
        }

        if ($request->boolean('my_area_only')) {
            $wrappedQuery->where('report_type', 'Area')
                         ->where('scope_id', $user->area_id);
        }

        // Filters
        if ($request->filled('date_from')) {
            $wrappedQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $wrappedQuery->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('category') && $request->category !== 'All') {
            $wrappedQuery->where('category', $request->category);
        }

        $perPage = $request->input('per_page', 10);
        $paginated = $wrappedQuery->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $categoryNames = Category::pluck('name', 'code')->toArray();
        $divisionNames = Division::pluck('div_name', 'id')->toArray();
        $areaNames = Area::pluck('area_name', 'id')->toArray();
        $areaDivisions = Area::pluck('division_id', 'id')->toArray();
        $userNames = User::select('id', 'first_name', 'last_name')->get()->mapWithKeys(function($u) {
            return [$u->id => "{$u->first_name} {$u->last_name}"];
        })->toArray();

        $paginated->getCollection()->transform(function ($report) use ($categoryNames, $divisionNames, $areaNames, $areaDivisions, $userNames) {
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
            if ($report->type === 'equipment') {
                $parts = ['RCPPE', $report->report_type ?: 'General', $catName];
                if ($scopeStr) $parts[] = $scopeStr;
                $parts[] = $report->year_of_report;
                $name = implode(' ', $parts);
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
                'type' => $report->type,
                'name' => $name,
                'created_by' => $report->user_id && isset($userNames[$report->user_id]) ? $userNames[$report->user_id] : 'System',
                'created_at' => date('Y-m-d H:i:s', strtotime($report->created_at)),
                'can_delete' => $this->canDeleteReport(auth()->user(), $report, $areaDivisions)
            ];
        });

        $categories = Category::all()->toArray();

        return Inertia::render('Reports/Index', [
            'reports' => $paginated,
            'filters' => $request->only(['date_from', 'date_to', 'category', 'my_division_only', 'my_area_only', 'per_page']),
            'categories' => $categories
        ]);
    }

    public function destroy($type, $id)
    {
        $user = auth()->user();
        $areaDivisions = Area::pluck('division_id', 'id')->toArray();

        $report = null;
        if ($type === 'supply') {
            $report = SupplyReport::find($id);
        } elseif ($type === 'equipment') {
            $report = EquipmentReport::find($id);
        }

        if (!$report) {
            return redirect()->back()->with('error', 'Report not found.');
        }

        if ($this->canDeleteReport($user, (object) $report->toArray(), $areaDivisions)) {
            if (Storage::disk('local')->exists($report->file_path)) {
                Storage::disk('local')->delete($report->file_path);
            }
            $report->delete();
            return redirect()->back()->with('success', 'Report deleted successfully.');
        }

        return redirect()->back()->with('error', 'Unauthorized to delete this report.');
    }

    public function destroyMultiple(Request $request)
    {
        $validated = $request->validate([
            'reports' => 'required|array',
            'reports.*.id' => 'required|integer',
            'reports.*.type' => 'required|string|in:supply,equipment',
        ]);

        $user = auth()->user();
        $areaDivisions = Area::pluck('division_id', 'id')->toArray();

        foreach ($validated['reports'] as $item) {
            $report = null;
            if ($item['type'] === 'supply') {
                $report = SupplyReport::find($item['id']);
            } elseif ($item['type'] === 'equipment') {
                $report = EquipmentReport::find($item['id']);
            }

            if ($report) {
                // To reuse canDeleteReport with the same signature, we need an object that mimics the DB row structure we used in index.
                if ($this->canDeleteReport($user, (object) $report->toArray(), $areaDivisions)) {
                    if (Storage::disk('local')->exists($report->file_path)) {
                        Storage::disk('local')->delete($report->file_path);
                    }
                    $report->delete();
                }
            }
        }

        return redirect()->back()->with('success', 'Selected reports deleted successfully. Unauthorized deletions were skipped.');
    }

    private function canDeleteReport($user, $report, $areaDivisions)
    {
        if ($user->hasRole(['Developer', 'Superadmin'])) {
            return true;
        }

        if ($user->hasRole('Admin')) {
            if ($report->report_type === 'Division') {
                return $report->scope_id == $user->division_id;
            } elseif ($report->report_type === 'Area') {
                $reportDivisionId = $areaDivisions[$report->scope_id] ?? null;
                return $reportDivisionId == $user->division_id;
            }
            return false;
        }

        if ($user->hasRole(['Encoder', 'Software'])) {
            if ($report->report_type === 'Area') {
                return $report->scope_id == $user->area_id;
            }
            return false;
        }

        return false;
    }
}
