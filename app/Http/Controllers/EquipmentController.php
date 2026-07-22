<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Equipment\Models\Equipment;
use App\Domain\Equipment\DTOs\EquipmentDTO;
use App\Domain\Equipment\Actions\GetEquipmentAction;
use App\Domain\Equipment\Actions\CreateEquipmentAction;
use App\Domain\Equipment\Actions\UpdateEquipmentAction;
use App\Domain\Equipment\Actions\DeleteEquipmentAction;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $equipment = app(GetEquipmentAction::class)->execute($request->all());
        $categories = \App\Domain\Shared\Models\Category::where('type', 'equipment')->get()->toArray();
        $divisions = \App\Models\Division::select(['id', 'div_name as name'])->get()->toArray();
        $areas = \App\Models\Area::select(['id', 'area_name as name', 'division_id'])->get()->toArray();

        return Inertia::render('Inventory/Equipment/Index', [
            'equipment' => $equipment,
            'filters' => $request->only(['search', 'category', 'status', 'my_division_only', 'my_area_only']),
            'categories' => $categories,
            'divisions' => $divisions,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request, CreateEquipmentAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Equipment::class);

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
                        $fail("You are only allowed to add data for your assigned division.");
                    }
                }
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
                        $fail("You are only allowed to add data for your assigned area.");
                    }
                }
            ],
        ]);

        $dto = EquipmentDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->route('equipment.index')->with('success', 'Equipment created.');
    }

    public function update(Request $request, Equipment $equipment, UpdateEquipmentAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $equipment);

        $validated = $request->validate([
            'category' => 'nullable|string',
            'article' => 'nullable|string',
            'description' => 'nullable|string',
            'date_acquired' => 'nullable|string',
            'property_number' => 'nullable|string',
            'serial_number' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'nullable|numeric',
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

    public function destroy(Equipment $equipment, DeleteEquipmentAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $equipment);

        $action->execute($equipment);
        return redirect()->route('equipment.index')->with('success', "{$equipment->article} has been successfully deleted.");
    }

    public function bulkDestroy(Request $request, DeleteEquipmentAction $action)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:equipment,id'
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $equipment = Equipment::find($id);
            if ($equipment && \Illuminate\Support\Facades\Gate::allows('delete', $equipment)) {
                $action->execute($equipment);
                $count++;
            }
        }

        return redirect()->route('equipment.index')->with('success', "{$count} have been deleted.");
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
            'division_id', 'area_id'
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
            'e.g. Serviceable, Unserviceable',
            'Division ID Number (Required)',
            'Area ID Number (Required)'
        ];

        $callback = function () use ($columns, $hints) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $hints);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(\App\Http\Requests\EquipmentImportRequest $request, \App\Domain\Equipment\Actions\ImportEquipmentAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Equipment::class);

        $rows = $request->input('rows', []);
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $action) {
            foreach ($rows as $data) {
                unset($data['_line']);
                $dto = EquipmentDTO::fromArray($data);
                $action->execute($dto);
            }
        });

        $imported = count($rows);

        return redirect()->route('equipment.index')->with('success', "Successfully imported {$imported} equipment records.");
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

        $query = \App\Domain\Equipment\Models\Equipment::where('category', $validated['category']);

        if ($validated['report_type'] === 'Division') {
            $query->where('division_id', $validated['scope_id']);
        } elseif ($validated['report_type'] === 'Area') {
            $query->where('area_id', $validated['scope_id']);
        }

        $equipment = $query->get();
        
        $filename = 'equipment_report_' . time() . '_' . uniqid() . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put("reports/{$filename}", $equipment->toJson());

        $report = \App\Domain\Equipment\Models\EquipmentReport::create([
            'category' => $validated['category'],
            'date_of_accountability' => $validated['date_of_accountability'],
            'year_of_report' => $validated['year_of_report'],
            'file_path' => "reports/{$filename}",
            'report_type' => $validated['report_type'],
            'scope_id' => $validated['scope_id'] ?? null,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json(['id' => $report->id]);
    }

    public function showReport(int $id)
    {
        $report = \App\Domain\Equipment\Models\EquipmentReport::findOrFail($id);
        
        $json = \Illuminate\Support\Facades\Storage::disk('local')->get($report->file_path);
        $equipment = json_decode($json, true);
        
        $categoryName = \App\Domain\Shared\Models\Category::where('code', $report->category)
            ->where('type', 'equipment')
            ->value('name') ?? $report->category;

        $scopeName = '';
        $divisionHeadName = null;
        $divisionHeadDesignation = null;

        if ($report->report_type === 'Division') {
            $division = \App\Models\Division::query()->find($report->scope_id);
            if ($division) {
                $scopeName = "Division: {$division->div_name}";
                $mi = $division->head_middle_initial ? ' ' . trim($division->head_middle_initial) . '.' : '';
                $nom = $division->head_nominal_letters ? ', ' . trim($division->head_nominal_letters) : '';
                $divisionHeadName = strtoupper(trim("{$division->head_first_name}{$mi} {$division->head_last_name}") . $nom);
                $divisionHeadDesignation = $division->head_designation;
            }
        } elseif ($report->report_type === 'Area') {
            $area = \App\Models\Area::with('division')->find($report->scope_id);
            if ($area) {
                $divName = $area->division ? $area->division->div_name : '';
                $scopeName = "Division: {$divName} | Area: {$area->area_name}";
                if ($area->division) {
                    $mi = $area->division->head_middle_initial ? ' ' . trim($area->division->head_middle_initial) . '.' : '';
                    $nom = $area->division->head_nominal_letters ? ', ' . trim($area->division->head_nominal_letters) : '';
                    $divisionHeadName = strtoupper(trim("{$area->division->head_first_name}{$mi} {$area->division->head_last_name}") . $nom);
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
}
