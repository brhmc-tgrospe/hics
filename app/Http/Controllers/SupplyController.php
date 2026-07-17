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

        return redirect()->route('supplies.index')->with('success', 'Supply updated.');
    }

    public function destroy(Supply $supply, DeleteSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $supply);

        $action->execute($supply);
        return redirect()->route('supplies.index')->with('success', 'Supply deleted.');
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
            'shortage_overage_qty', 'shortage_overage_value', 'total_amount', 'status',
            'division_id', 'area_id'
        ];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request, CreateSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('create', Supply::class);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('file')->getRealPath();
        $file = fopen($path, 'r');
        
        $header = fgetcsv($file);

        if (!$header) {
            return redirect()->back()->with('error', 'Invalid CSV file');
        }

        $imported = 0;
        while (($row = fgetcsv($file)) !== false) {
            if (count($header) === count($row)) {
                $data = array_combine($header, $row);
                
                // Clean empty strings to null for nullable fields
                foreach ($data as $key => $value) {
                    if (trim($value) === '') {
                        $data[$key] = null;
                    }
                }

                $dto = SupplyDTO::fromArray($data);
                $action->execute($dto);
                $imported++;
            }
        }
        fclose($file);

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

        return Inertia::render('Inventory/Supplies/Report', [
            'report' => $report,
            'supplies' => $supplies,
            'categoryName' => $categoryName,
        ]);
    }
}
