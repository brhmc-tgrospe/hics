<?php

namespace App\Http\Controllers;

use App\Domain\Equipment\Actions\CreateEquipmentAction;
use App\Domain\Equipment\Actions\DeleteEquipmentAction;
use App\Domain\Equipment\Actions\GetEquipmentAction;
use App\Domain\Equipment\Actions\GetEquipmentReportDataAction;
use App\Domain\Equipment\Actions\ImportEquipmentAction;
use App\Domain\Equipment\Actions\UpdateEquipmentAction;
use App\Domain\Equipment\DTOs\EquipmentDTO;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\Models\EquipmentReport;
use App\Domain\Shared\Models\Category;
use App\Http\Requests\EquipmentImportRequest;
use App\Models\Area;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipment = app(GetEquipmentAction::class)->execute($request->all());
        $categories = Category::where('type', 'equipment')->get()->toArray();
        $divisions = Division::select(['id', 'div_name as name'])->get()->toArray();
        $areas = Area::select(['id', 'area_name as name', 'division_id'])->get()->toArray();

        $user = $request->user();
        $missingQuery = Equipment::where(function ($q) {
            $q->whereNull('unit_value')->orWhere('unit_value', 0);
        });
        if ($user->hasRole('Encoder')) {
            $missingQuery->where('division_id', $user->division_id)->where('area_id', $user->area_id);
        } elseif ($user->hasRole('Admin')) {
            $missingQuery->where('division_id', $user->division_id);
        }

        return Inertia::render('Inventory/Equipment/Index', [
            'equipment' => $equipment,
            'filters' => $request->only(['search', 'category', 'status', 'my_division_only', 'my_area_only', 'division_id', 'area_id', 'per_page', 'sort_field', 'sort_direction']),
            'categories' => $categories,
            'divisions' => $divisions,
            'areas' => $areas,
            'missingUnitValueCount' => $missingQuery->count(),
        ]);
    }

    public function store(Request $request, CreateEquipmentAction $action)
    {
        Gate::authorize('create', Equipment::class);

        $validated = $request->validate([
            'category' => 'nullable|string',
            'article' => 'required|string',
            'description' => 'required|string',
            'date_acquired' => 'nullable|string',
            'property_number' => 'nullable|string',
            'serial_number' => 'required|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'required|numeric|gt:0',
            'total_value' => 'nullable|numeric',
            'quantity_per_property_card' => 'required|integer|gt:0',
            'quantity_per_physical_count' => 'required|integer|gt:0',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'end_user' => 'nullable|string',
            'status' => 'nullable|string',
            'division_id' => [
                'required',
                'integer',
                'exists:divisions,id',
                function ($attribute, $value, $fail) use ($request) {
                    $user = $request->user();
                    if ($user->hasRole('Superadmin') || $user->hasRole('Developer')) {
                        return;
                    }
                    if ($value != $user->division_id) {
                        $fail('You are only allowed to add data for your assigned division.');
                    }
                },
            ],
            'area_id' => [
                'required',
                'integer',
                'exists:areas,id',
                function ($attribute, $value, $fail) use ($request) {
                    $user = $request->user();
                    if ($user->hasRole('Superadmin') || $user->hasRole('Developer') || $user->hasRole('Admin')) {
                        return;
                    }
                    if ($user->hasRole('Encoder') && $value != $user->area_id) {
                        $fail('You are only allowed to add data for your assigned area.');
                    }
                },
            ],
        ]);

        $dto = EquipmentDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->route('equipment.index')->with('success', 'Equipment created.');
    }

    public function update(Request $request, Equipment $equipment, UpdateEquipmentAction $action)
    {
        Gate::authorize('update', $equipment);

        $validated = $request->validate([
            'category' => 'nullable|string',
            'article' => 'nullable|string',
            'description' => 'nullable|string',
            'date_acquired' => 'nullable|string',
            'property_number' => 'nullable|string',
            'serial_number' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'required|numeric|gt:0',
            'total_value' => 'nullable|numeric',
            'quantity_per_property_card' => 'nullable|integer',
            'quantity_per_physical_count' => 'nullable|integer',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
            'remarks' => 'nullable|string',
            'end_user' => 'nullable|string',
            'status' => 'nullable|string',
            'division_id' => 'required|integer|exists:divisions,id',
            'area_id' => 'required|integer|exists:areas,id',
        ]);

        $dto = EquipmentDTO::fromArray($validated);
        $action->execute($equipment, $dto);

        return redirect()->route('equipment.index')->with('success', "{$equipment->article} has been successfully updated.");
    }

    public function destroy(Request $request, Equipment $equipment, DeleteEquipmentAction $action)
    {
        Gate::authorize('delete', $equipment);

        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        $action->execute($equipment, $validated['remarks']);

        return redirect()->route('equipment.index')->with('success', "{$equipment->article} has been successfully deleted.");
    }

    public function bulkDestroy(Request $request, DeleteEquipmentAction $action)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:equipment,id',
            'remarks' => 'required|string|max:1000',
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $equipment = Equipment::find($id);
            if ($equipment && Gate::allows('delete', $equipment)) {
                $action->execute($equipment, $validated['remarks']);
                $count++;
            }
        }

        return redirect()->route('equipment.index')->with('success', "{$count} items have been deleted.");
    }

    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="equipment_template.csv"',
        ];

        $columns = [
            'category', 'article', 'description', 'date_acquired', 'property_number',
            'serial_number', 'unit_of_measure', 'unit_value',
            'quantity_per_property_card', 'quantity_per_physical_count',
            'remarks', 'end_user', 'status',
            'division_id', 'area_id',
        ];

        $hints = [
            'Hint: Category Code (e.g. fandf, ictequip) (Required)',
            'Name of the item (Required)',
            'Detailed description (Required)',
            'YYYY-MM-DD',
            'Property Number',
            'Serial Number (Required)',
            'e.g. unit, pc',
            'Numeric value (Required)',
            'Must be > 0 (Required)',
            'Must be > 0 (Required)',
            'Any remarks',
            'End User Name',
            'e.g. Serviceable, Unserviceable (Required)',
            'Division ID Number (Required)',
            'Area ID Number (Required)',
        ];

        $callback = function () use ($columns, $hints) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $hints);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(EquipmentImportRequest $request, ImportEquipmentAction $action)
    {
        Gate::authorize('create', Equipment::class);

        $rows = $request->input('rows', []);
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $action, &$created, &$updated) {
            foreach ($rows as $data) {
                unset($data['_line']);
                $dto = EquipmentDTO::fromArray($data);
                $result = $action->execute($dto);
                if ($result['action'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        $messages = [];
        if ($created > 0) $messages[] = "{$created} new records created";
        if ($updated > 0) $messages[] = "{$updated} existing records updated";
        $summary = implode(', ', $messages);

        return redirect()->route('equipment.index')->with('success', "Successfully imported: {$summary}.");
    }

    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'date_of_accountability' => 'required|date',
            'year_of_report' => 'required|integer',
            'report_type' => 'required|string|in:General,Division,Area',
            'scope_id' => 'nullable|integer',
        ]);

        $query = Equipment::where('category', $validated['category']);

        if ($validated['report_type'] === 'Division') {
            $query->where('division_id', $validated['scope_id']);
        } elseif ($validated['report_type'] === 'Area') {
            $query->where('area_id', $validated['scope_id']);
        }

        $equipment = $query->get();

        $filename = 'equipment_report_'.time().'_'.uniqid().'.json';
        Storage::disk('local')->put("reports/{$filename}", $equipment->toJson());

        $report = EquipmentReport::create([
            'category' => $validated['category'],
            'date_of_accountability' => $validated['date_of_accountability'],
            'year_of_report' => $validated['year_of_report'],
            'file_path' => "reports/{$filename}",
            'report_type' => $validated['report_type'],
            'scope_id' => $validated['scope_id'] ?? null,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['id' => $report->id]);
    }

    public function showReport(int $id, GetEquipmentReportDataAction $action)
    {
        $report = EquipmentReport::findOrFail($id);

        $equipment = $action->execute($report);

        $categoryName = Category::where('code', $report->category)
            ->where('type', 'equipment')
            ->value('name') ?? $report->category;

        $scopeName = '';
        $divisionHeadName = null;
        $divisionHeadDesignation = null;

        if ($report->report_type === 'Division') {
            $division = Division::query()->find($report->scope_id);
            if ($division) {
                $scopeName = "Division: {$division->div_name}";
                $mi = $division->head_middle_initial ? ' '.trim($division->head_middle_initial).'.' : '';
                $nom = $division->head_nominal_letters ? ', '.trim($division->head_nominal_letters) : '';
                $divisionHeadName = strtoupper(trim("{$division->head_first_name}{$mi} {$division->head_last_name}").$nom);
                $divisionHeadDesignation = $division->head_designation;
            }
        } elseif ($report->report_type === 'Area') {
            $area = Area::with('division')->find($report->scope_id);
            if ($area) {
                $divName = $area->division ? $area->division->div_name : '';
                $scopeName = "Division: {$divName} | Area: {$area->area_name}";
                if ($area->division) {
                    $mi = $area->division->head_middle_initial ? ' '.trim($area->division->head_middle_initial).'.' : '';
                    $nom = $area->division->head_nominal_letters ? ', '.trim($area->division->head_nominal_letters) : '';
                    $divisionHeadName = strtoupper(trim("{$area->division->head_first_name}{$mi} {$area->division->head_last_name}").$nom);
                    $divisionHeadDesignation = $area->division->head_designation;
                }
            }
        }

        return Inertia::render('Inventory/Equipment/Report', [
            'report' => $report,
            'equipment' => $equipment,
            'categoryName' => $categoryName,
            'scopeName' => $scopeName,
            'divisionHeadName' => $divisionHeadName,
            'divisionHeadDesignation' => $divisionHeadDesignation,
        ]);
    }

    public function bulkEditUnitValues()
    {
        $user = auth()->user();
        
        $query = Equipment::where(function ($q) {
            $q->whereNull('unit_value')->orWhere('unit_value', 0);
        });

        // Scope by user role
        if ($user->hasRole('Encoder')) {
            $query->where('division_id', $user->division_id)
                  ->where('area_id', $user->area_id);
        } elseif ($user->hasRole('Admin')) {
            $query->where('division_id', $user->division_id);
        }
        // Superadmin/Developer see all

        $equipment = $query
            ->select('id', 'article', 'description', 'property_number', 'category', 'unit_of_measure', 'on_hand_per_count', 'balance_per_card', 'unit_value', 'division_id', 'area_id')
            ->with(['division:id,div_name', 'area:id,area_name'])
            ->orderBy('category')
            ->orderBy('article')
            ->get();

        return Inertia::render('Inventory/Equipment/BulkEditUnitValues', [
            'equipment' => $equipment,
        ]);
    }

    public function bulkUpdateUnitValues(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:equipment,id',
            'items.*.unit_value' => 'required|numeric|gt:0',
        ]);

        $updated = 0;
        $skipped = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, &$updated, &$skipped) {
            foreach ($validated['items'] as $item) {
                $equipment = Equipment::find($item['id']);
                if ($equipment && $request->user()->can('update', $equipment)) {
                    $equipment->unit_value = $item['unit_value'];
                    $equipment->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        $message = "Successfully updated unit values for {$updated} records.";
        if ($skipped > 0) {
            $message .= " {$skipped} records were skipped (no permission).";
        }

        return redirect()->route('equipment.index')->with('success', $message);
    }
}
