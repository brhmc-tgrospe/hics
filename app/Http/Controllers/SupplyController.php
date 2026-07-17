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

        return Inertia::render('Inventory/Supplies/Index', [
            'supplies' => $supplies,
            'filters' => $request->only(['search', 'category']),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, CreateSupplyAction $action)
    {
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
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->route('supplies.index')->with('success', 'Supply created.');
    }

    public function update(Request $request, Supply $supply, UpdateSupplyAction $action)
    {
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
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($supply, $dto);

        return redirect()->route('supplies.index')->with('success', 'Supply updated.');
    }

    public function destroy(Supply $supply, DeleteSupplyAction $action)
    {
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
            'category', 'article', 'description', 'stock_number', 'unit_of_measure', 
            'unit_value', 'balance_per_card', 'on_hand_per_count', 
            'shortage_overage_qty', 'shortage_overage_value', 'total_amount', 'status'
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
        ]);

        $supplies = \App\Domain\Supplies\Models\Supply::where('category', $validated['category'])->get();
        
        $filename = 'supply_report_' . time() . '_' . uniqid() . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put("reports/{$filename}", $supplies->toJson());

        $report = \App\Domain\Supply\Models\SupplyReport::create([
            'category' => $validated['category'],
            'date_of_accountability' => $validated['date_of_accountability'],
            'year_of_report' => $validated['year_of_report'],
            'fund_cluster' => $validated['fund_cluster'],
            'file_path' => "reports/{$filename}",
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
