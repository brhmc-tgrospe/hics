<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;
use App\Domain\Supplies\Actions\GetSuppliesAction;
use App\Domain\Supplies\Actions\CreateSupplyAction;
use App\Domain\Supplies\Actions\UpdateSupplyAction;
use App\Domain\Supplies\Actions\DeleteSupplyAction;

class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = app(GetSuppliesAction::class)->execute($request->all());
        $categories = \App\Domain\Shared\Models\Category::where('type', 'supply')->get()->toArray();
        $divisions = \App\Models\Division::select('id', 'div_name as name')->get()->toArray();
        $areas = \App\Models\Area::select('id', 'area_name as name', 'division_id')->get()->toArray();

        return Inertia::render('Inventory/Supplies/Index', [
            'supplies' => $supplies,
            'filters' => $request->only(['search', 'category', 'my_division_only', 'my_area_only']),
            'categories' => $categories,
            'divisions' => $divisions,
            'areas' => $areas,
        ]);
    }

    public function store(Request $request, CreateSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Supply::class);

        $validated = $request->validate([
            'category' => 'required|string',
            'article' => 'nullable|string',
            'description' => 'required|string',
            'stock_number' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'nullable|numeric',
            'balance_per_card' => 'required|integer|gt:0',
            'on_hand_per_count' => 'required|integer|gt:0',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
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
            'expiry_date' => 'required_if:category,mssup,enteral,drugs|nullable|date',
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->route('supplies.index')->with('success', 'Supply created.');
    }

    public function update(Request $request, Supply $supply, UpdateSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $supply);

        $validated = $request->validate([
            'category' => 'nullable|string',
            'article' => 'nullable|string',
            'description' => 'nullable|string',
            'stock_number' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'nullable|numeric',
            'balance_per_card' => 'nullable|integer',
            'on_hand_per_count' => 'nullable|integer',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
            'status' => 'nullable|string',
            'division_id' => 'required|integer|exists:divisions,id',
            'area_id' => 'required|integer|exists:areas,id',
            'expiry_date' => 'required_unless:category,ictsupply,officesup,hksupp|nullable|date',
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($supply, $dto);

        return redirect()->route('supplies.index')->with('success', "{$supply->article} has been successfully updated.");
    }

    public function destroy(Supply $supply, DeleteSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $supply);

        $action->execute($supply);
        return redirect()->route('supplies.index')->with('success', "{$supply->article} has been successfully deleted.");
    }

    public function bulkDestroy(Request $request, DeleteSupplyAction $action)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:supplies,id'
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $supply = Supply::find($id);
            if ($supply && \Illuminate\Support\Facades\Gate::allows('delete', $supply)) {
                $action->execute($supply);
                $count++;
            }
        }

        return redirect()->route('supplies.index')->with('success', "{$count} have been deleted.");
    }

    public function template()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="supplies_template.csv"',
        ];

        $columns = [
            'category', 'article', 'description', 'stock_number', 'expiry_date', 'unit_of_measure', 
            'unit_value', 'balance_per_card', 'on_hand_per_count', 
            'status', 'division_id', 'area_id'
        ];

        $hints = [
            'Hint: Category Code (e.g. officesup, drugs) (Required)',
            'Name of the item (Required)',
            'Detailed description (Required)',
            'e.g. 12345',
            'YYYY-MM-DD (Required for Medical and Surgical Supplies, Enteral Supplies & Drugs and Medicines)',
            'e.g. box, pc',
            'Numeric value',
            'Must be > 0 (Required)',
            'Must be > 0 (Required)',
            'e.g. Available, Depleted',
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

    public function import(\App\Http\Requests\SupplyImportRequest $request, \App\Domain\Supplies\Actions\ImportSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Supply::class);

        $rows = $request->input('rows', []);
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $action) {
            foreach ($rows as $data) {
                unset($data['_line']);
                $dto = SupplyDTO::fromArray($data);
                $action->execute($dto);
            }
        });

        $imported = count($rows);

        return redirect()->route('supplies.index')->with('success', "Successfully imported {$imported} supplies records.");
    }

    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'date_of_accountability' => 'required|date',
            'year_of_report' => 'required|integer',
            'fund_cluster' => 'nullable|string',
            'report_type' => 'required|string|in:General,Division,Area',
            'report_period' => 'nullable|string',
            'custom_month' => 'nullable|integer',
            'scope_id' => 'nullable|integer',
        ]);

        $query = \App\Domain\Supplies\Models\Supply::where('category', $validated['category']);

        if ($validated['report_type'] === 'Division') {
            $query->where('division_id', $validated['scope_id']);
        } elseif ($validated['report_type'] === 'Area') {
            $query->where('area_id', $validated['scope_id']);
        }

        $supplies = $query->get();
        
        $filename = 'supply_report_' . time() . '_' . uniqid() . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put("reports/{$filename}", $supplies->toJson());

        $report = \App\Domain\Supply\Models\SupplyReport::create([
            'category' => $validated['category'],
            'date_of_accountability' => $validated['date_of_accountability'],
            'year_of_report' => $validated['year_of_report'],
            'fund_cluster' => $validated['fund_cluster'],
            'file_path' => "reports/{$filename}",
            'report_type' => $validated['report_type'],
            'report_period' => $validated['report_period'] ?? null,
            'custom_month' => $validated['custom_month'] ?? null,
            'scope_id' => $validated['scope_id'] ?? null,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json(['id' => $report->id]);
    }

    public function showReport($id)
    {
        $report = \App\Domain\Supply\Models\SupplyReport::findOrFail($id);
        
        $json = \Illuminate\Support\Facades\Storage::disk('local')->get($report->file_path);
        $supplies = json_decode($json, true);
        
        $categoryName = \App\Domain\Shared\Models\Category::where('code', $report->category)
            ->where('type', 'supply')
            ->value('name') ?? $report->category;

        $scopeName = '';
        if ($report->report_type === 'Division') {
            $division = \App\Models\Division::find($report->scope_id);
            if ($division) {
                $scopeName = "Division: {$division->div_name}";
            }
        } elseif ($report->report_type === 'Area') {
            $area = \App\Models\Area::with('division')->find($report->scope_id);
            if ($area) {
                $divName = $area->division ? $area->division->div_name : '';
                $scopeName = "Division: {$divName} | Area: {$area->area_name}";
            }
        }

        return Inertia::render('Inventory/Supplies/Report', [
            'report' => $report,
            'supplies' => $supplies,
            'categoryName' => $categoryName,
            'scopeName' => $scopeName,
        ]);
    }
}
