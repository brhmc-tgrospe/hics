<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Supplies\Models\Supply;
use App\Domain\Supplies\DTOs\SupplyDTO;
use App\Domain\Supplies\Actions\GetSuppliesAction;
use App\Domain\Supplies\Actions\GetExpiringSuppliesAction;
use App\Domain\Supplies\Actions\GetSupplyReportDataAction;
use App\Domain\Supplies\Actions\CreateSupplyAction;
use App\Domain\Supplies\Actions\UpdateSupplyAction;
use App\Domain\Supplies\Actions\DeleteSupplyAction;
use App\Domain\Supplies\Actions\ExportSupplyAction;

class SupplyController extends Controller
{
    public function index(Request $request)
    {
        $supplies = app(GetSuppliesAction::class)->execute($request->all());
        $categories = \App\Domain\Shared\Models\Category::where('type', 'supply')->get()->toArray();
        $divisions = \App\Models\Division::select('id', 'div_name as name')->get()->toArray();
        $areas = \App\Models\Area::select('id', 'area_name as name', 'division_id')->get()->toArray();

        $user = $request->user();
        $missingQuery = Supply::where(function ($q) {
            $q->whereNull('unit_value')->orWhere('unit_value', 0);
        });
        if ($user->hasRole('Encoder')) {
            $missingQuery->where('division_id', $user->division_id)->where('area_id', $user->area_id);
        } elseif ($user->hasRole('Admin')) {
            $missingQuery->where('division_id', $user->division_id);
        }

        $expiringSupplies = app(GetExpiringSuppliesAction::class)->execute($request->user(), $request->all(), false);

        return Inertia::render('Inventory/Supplies/Index', [
            'supplies' => $supplies,
            'expiringSupplies' => $expiringSupplies,
            'filters' => $request->only(['search', 'category', 'my_division_only', 'my_area_only', 'division_id', 'area_id', 'per_page', 'sort_field', 'sort_direction']),
            'categories' => $categories,
            'divisions' => $divisions,
            'areas' => $areas,
            'missingUnitValueCount' => $missingQuery->count(),
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
            'unit_value' => 'required|numeric|gt:0',
            'balance_per_card' => 'required|integer',
            'on_hand_per_count' => 'required|integer',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
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
                    $area = \App\Models\Area::find($value);
                    if ($area && strtolower(trim($area->area_name)) === 'general area') {
                        $fail('Items cannot be assigned to the General Area. Please select a designated area.');
                        return;
                    }
                    $user = $request->user();
                    if ($user->hasRole('Superadmin') || $user->hasRole('Developer') || $user->hasRole('Admin')) {
                        return;
                    }
                    if ($user->hasRole('Encoder') && $value != $user->area_id) {
                        $fail("You are only allowed to add data for your assigned area.");
                    }
                }
            ],
            'expiry_date' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => \App\Domain\Supplies\Services\SupplyCategoryExpirationPolicy::isExpiryRequired($request->input('category'))),
                'nullable',
                'date',
            ],
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($dto);

        return redirect()->route('supplies.index')->with('success', 'Supply created.');
    }

    public function update(Request $request, Supply $supply, UpdateSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $supply);

        $validated = $request->validate([
            'category' => 'required|string',
            'article' => 'nullable|string',
            'description' => 'nullable|string',
            'stock_number' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_value' => 'required|numeric|gt:0',
            'balance_per_card' => 'required|integer',
            'on_hand_per_count' => 'required|integer',
            'shortage_overage_qty' => 'nullable|integer',
            'shortage_overage_value' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'status' => 'nullable|string',
            'division_id' => 'required|integer|exists:divisions,id',
            'area_id' => [
                'required',
                'integer',
                'exists:areas,id',
                function ($attribute, $value, $fail) {
                    $area = \App\Models\Area::find($value);
                    if ($area && strtolower(trim($area->area_name)) === 'general area') {
                        $fail('Items cannot be assigned to the General Area. Please select a designated area.');
                    }
                },
            ],
            'expiry_date' => [
                \Illuminate\Validation\Rule::requiredIf(fn() => \App\Domain\Supplies\Services\SupplyCategoryExpirationPolicy::isExpiryRequired($request->input('category'))),
                'nullable',
                'date',
            ],
        ]);

        $dto = SupplyDTO::fromArray($validated);
        $action->execute($supply, $dto);

        return redirect()->route('supplies.index')->with('success', "{$supply->article} has been successfully updated.");
    }

    public function destroy(Request $request, Supply $supply, DeleteSupplyAction $action)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $supply);

        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ]);

        $action->execute($supply, $validated['remarks']);
        return redirect()->route('supplies.index')->with('success', "{$supply->article} has been successfully deleted.");
    }

    public function bulkDestroy(Request $request, DeleteSupplyAction $action)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:supplies,id',
            'remarks' => 'required|string|max:1000',
        ]);

        $count = 0;
        foreach ($validated['ids'] as $id) {
            $supply = Supply::find($id);
            if ($supply && \Illuminate\Support\Facades\Gate::allows('delete', $supply)) {
                $action->execute($supply, $validated['remarks']);
                $count++;
            }
        }

        return redirect()->route('supplies.index')->with('success', "{$count} items have been deleted.");
    }

    public function export(Request $request, ExportSupplyAction $action)
    {
        abort_unless($request->user()->hasRole(['Developer', 'Superadmin']), 403, 'Unauthorized access.');

        $validated = $request->validate([
            'format' => 'nullable|string|in:template,full',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'category' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        return $action->execute($validated);
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
            'Hint: Category Code (e.g. officesup, drmeds, mssup) (Required)',
            'Name of the item (Required)',
            'Detailed description (Required)',
            'e.g. 12345 (Optional - leave empty to match by Category, Article, Description, Expiry Date & Unit Value)',
            'YYYY-MM-DD (Required for Medical and Surgical, Enteral, Drugs and Medicines, Food Supplies)',
            'e.g. box, pc',
            'Numeric value (Required',
            'Must be > 0 (Required)',
            'Must be > 0 (Required)',
            'e.g. Available, Depleted (Required)',
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
        $created = 0;
        $updated = 0;
        
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($rows, $action, &$created, &$updated) {
                foreach ($rows as $data) {
                    $line = $data['_line'] ?? null;
                    unset($data['_line']);
                    $dto = SupplyDTO::fromArray($data);
                    try {
                        $result = $action->execute($dto);
                    } catch (\DomainException $e) {
                        $linePrefix = $line ? "Line {$line}: " : '';
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'file' => "Upload Failed. {$linePrefix}{$e->getMessage()}",
                        ]);
                    }
                    if ($result['action'] === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                }
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Supplies import failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return redirect()->route('supplies.index')->with('error', 'Import failed: ' . $e->getMessage());
        }

        $messages = [];
        if ($created > 0) $messages[] = "{$created} new records created";
        if ($updated > 0) $messages[] = "{$updated} existing records updated";
        $summary = implode(', ', $messages);

        return redirect()->route('supplies.index')->with('success', "Successfully imported: {$summary}.");
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
            'scope_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('report_type') === 'Area' && $value) {
                        $area = \App\Models\Area::find($value);
                        if ($area && strtolower(trim($area->area_name)) === 'general area') {
                            $fail('Reports cannot be generated for the General Area. Please select a designated area.');
                        }
                    }
                },
            ],
        ]);

        $query = \App\Domain\Supplies\Models\Supply::where('category', $validated['category']);

        if ($validated['report_type'] === 'Division') {
            $query->where('division_id', $validated['scope_id']);
        } elseif ($validated['report_type'] === 'Area') {
            $query->where('area_id', $validated['scope_id']);
        }

        $supplies = $query
            ->orderByRaw('LOWER(article) ASC')
            ->orderByRaw('LOWER(description) ASC')
            ->get();
        
        $filename = 'supply_report_' . time() . '_' . uniqid() . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put("reports/{$filename}", $supplies->toJson());

        $report = \App\Domain\Supply\Models\SupplyReport::create([
            'category' => $validated['category'],
            'date_of_accountability' => $validated['date_of_accountability'],
            'year_of_report' => $validated['year_of_report'],
            'fund_cluster' => $validated['fund_cluster'] ?? null,
            'file_path' => "reports/{$filename}",
            'report_type' => $validated['report_type'],
            'report_period' => $validated['report_period'] ?? null,
            'custom_month' => $validated['custom_month'] ?? null,
            'scope_id' => $validated['scope_id'] ?? null,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json(['id' => $report->id]);
    }

    public function showReport($id, GetSupplyReportDataAction $action)
    {
        $report = \App\Domain\Supply\Models\SupplyReport::with('user')->findOrFail($id);

        if ($report->report_period === 'Expiring Supplies') {
            $snapshot = [];
            if ($report->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($report->file_path)) {
                $decoded = json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get($report->file_path), true);
                if (is_array($decoded)) {
                    $snapshot = $decoded;
                }
            }

            $creator = $report->user;
            $creatorName = $creator
                ? trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''))
                : '';
            if ($creatorName === '') {
                $creatorName = $snapshot['generatedBy'] ?? ($creator?->username ?? 'System');
            }

            return Inertia::render('Inventory/Supplies/ExpiringReport', [
                'expiringSupplies' => $snapshot['expiringSupplies'] ?? [
                    'total_items_count' => 0,
                    'total_quantity' => 0,
                    'total_amount' => 0,
                    'current_date_utc8' => '',
                    'cutoff_date_utc8' => '',
                    'categories' => [],
                ],
                'categoryName' => $snapshot['categoryName'] ?? ($report->category === 'All' ? 'ALL CATEGORIES' : $report->category),
                'scopeName' => $snapshot['scopeName'] ?? '',
                'selectedCategories' => $snapshot['selectedCategories'] ?? [$report->category],
                'statusFilter' => $snapshot['statusFilter'] ?? 'all',
                'reportFilters' => $snapshot['reportFilters'] ?? [],
                'generatedBy' => $creatorName,
                'generatedAt' => $snapshot['generatedAt'] ?? $report->created_at?->timezone('+08:00')->format('F j, Y h:i A'),
                'savedReportId' => $report->id,
            ]);
        }
        
        $supplies = $action->execute($report);
        
        $categoryName = \App\Domain\Shared\Models\Category::where('code', $report->category)
            ->where('type', 'supply')
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

        return Inertia::render('Inventory/Supplies/Report', [
            'report' => $report,
            'supplies' => $supplies,
            'categoryName' => $categoryName,
            'scopeName' => $scopeName,
            'divisionHeadName' => $divisionHeadName,
            'divisionHeadDesignation' => $divisionHeadDesignation,
        ]);
    }

    public function bulkEditUnitValues()
    {
        $user = auth()->user();
        
        $query = Supply::where(function ($q) {
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

        $supplies = $query
            ->select('id', 'article', 'description', 'category', 'unit_of_measure', 'on_hand_per_count', 'balance_per_card', 'unit_value', 'division_id', 'area_id')
            ->with(['division:id,div_name', 'area:id,area_name'])
            ->orderBy('category')
            ->orderBy('article')
            ->get();

        return Inertia::render('Inventory/Supplies/BulkEditUnitValues', [
            'supplies' => $supplies,
        ]);
    }

    public function bulkUpdateUnitValues(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:supplies,id',
            'items.*.unit_value' => 'required|numeric|gt:0',
        ]);

        $updated = 0;
        $skipped = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, &$updated, &$skipped) {
            foreach ($validated['items'] as $item) {
                $supply = Supply::find($item['id']);
                if ($supply && $request->user()->can('update', $supply)) {
                    $supply->unit_value = $item['unit_value'];
                    $supply->save();
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

        return redirect()->route('supplies.index')->with('success', $message);
    }

    public function previewExpiringReport(Request $request)
    {
        $payload = $this->buildExpiringReportPayload($request);

        return Inertia::render('Inventory/Supplies/ExpiringReport', [
            ...$payload,
            'savedReportId' => null,
        ]);
    }

    public function saveExpiringReport(Request $request)
    {
        $request->validate([
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'status_filter' => 'nullable|string|in:all,expired,expiring_soon',
            'from_supplies_index' => 'nullable|boolean',
            'division_id' => 'nullable|integer|exists:divisions,id',
            'area_id' => 'nullable|integer|exists:areas,id',
            'my_division_only' => 'nullable',
            'my_area_only' => 'nullable',
        ]);

        $payload = $this->buildExpiringReportPayload($request);

        $filename = 'expiring_supply_report_' . time() . '_' . uniqid() . '.json';
        \Illuminate\Support\Facades\Storage::disk('local')->put(
            "reports/{$filename}",
            json_encode($payload)
        );

        $selectedCategories = $payload['selectedCategories'];
        $categoryCol = (count($selectedCategories) === 1 && $selectedCategories[0] !== 'All')
            ? $selectedCategories[0]
            : 'All';

        if ($request->filled('area_id')) {
            $reportType = 'Area';
            $scopeId = (int) $request->input('area_id');
        } elseif ($request->filled('division_id')) {
            $reportType = 'Division';
            $scopeId = (int) $request->input('division_id');
        } else {
            $reportType = 'General';
            $scopeId = null;
        }

        $nowUtc8 = \Carbon\Carbon::now('+08:00');

        $report = \App\Domain\Supply\Models\SupplyReport::create([
            'category' => $categoryCol,
            'date_of_accountability' => $nowUtc8->toDateString(),
            'year_of_report' => (int) $nowUtc8->year,
            'fund_cluster' => null,
            'file_path' => "reports/{$filename}",
            'report_type' => $reportType,
            'report_period' => 'Expiring Supplies',
            'custom_month' => null,
            'scope_id' => $scopeId,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        return response()->json([
            'id' => $report->id,
            'message' => 'Expiring supplies report saved to Reports Area.',
        ]);
    }

    private function buildExpiringReportPayload(Request $request): array
    {
        $user = $request->user();
        $rawCategories = $request->input('categories', []);
        if (is_string($rawCategories)) {
            $rawCategories = explode(',', $rawCategories);
        }

        $selectedCategories = array_values(array_filter(
            array_map('trim', (array) $rawCategories),
            fn($c) => $c !== ''
        ));
        $isAllCategories = empty($selectedCategories) || in_array('All', $selectedCategories, true);

        $statusFilter = $request->input('status_filter', 'all');
        if (!in_array($statusFilter, ['all', 'expired', 'expiring_soon'], true)) {
            $statusFilter = 'all';
        }

        $reportFilters = array_filter([
            'from_supplies_index' => $request->boolean('from_supplies_index') ? 1 : null,
            'division_id' => $request->input('division_id'),
            'area_id' => $request->input('area_id'),
            'my_division_only' => $request->input('my_division_only'),
            'my_area_only' => $request->input('my_area_only'),
        ], fn($v) => $v !== null && $v !== '');

        $expiringData = app(GetExpiringSuppliesAction::class)->execute(
            $user,
            $request->only(['division_id', 'area_id', 'my_division_only', 'my_area_only']),
            !$request->boolean('from_supplies_index')
        );

        $filteredCategories = [];
        $totalItemsCount = 0;
        $totalQuantity = 0;
        $totalAmount = 0.0;

        foreach ($expiringData['categories'] as $cat) {
            if (!$isAllCategories && !in_array($cat['category_code'], $selectedCategories, true)) {
                continue;
            }

            $items = $cat['items'];
            if ($statusFilter === 'expired') {
                $items = array_values(array_filter($items, fn($item) => !empty($item['is_expired'])));
            } elseif ($statusFilter === 'expiring_soon') {
                $items = array_values(array_filter($items, fn($item) => empty($item['is_expired'])));
            }

            if (empty($items)) {
                continue;
            }

            usort($items, function ($a, $b) {
                $cmp = strcasecmp((string) ($a['article'] ?? ''), (string) ($b['article'] ?? ''));
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp((string) ($a['description'] ?? ''), (string) ($b['description'] ?? ''));
            });

            $catQty = 0;
            $catAmount = 0.0;
            foreach ($items as $item) {
                $catQty += (int) ($item['on_hand_per_count'] ?? 0);
                $catAmount += (float) ($item['total_amount'] ?? 0);
            }
            $catAmount = round($catAmount, 2);

            $filteredCategories[] = [
                'category_code' => $cat['category_code'],
                'category_name' => $cat['category_name'],
                'items_count' => count($items),
                'total_quantity' => $catQty,
                'total_amount' => $catAmount,
                'items' => $items,
            ];

            $totalItemsCount += count($items);
            $totalQuantity += $catQty;
            $totalAmount += $catAmount;
        }

        usort($filteredCategories, function ($a, $b) {
            return strcasecmp((string) ($a['category_name'] ?? ''), (string) ($b['category_name'] ?? ''));
        });

        $expiringData['categories'] = $filteredCategories;
        $expiringData['total_items_count'] = $totalItemsCount;
        $expiringData['total_quantity'] = $totalQuantity;
        $expiringData['total_amount'] = round($totalAmount, 2);

        if ($isAllCategories) {
            $categoryName = 'ALL CATEGORIES';
        } elseif (!empty($filteredCategories)) {
            $categoryName = implode(', ', array_map(fn($c) => strtoupper($c['category_name']), $filteredCategories));
        } else {
            $dbNames = \App\Domain\Shared\Models\Category::where('type', 'supply')
                ->whereIn('code', $selectedCategories)
                ->pluck('name', 'code')
                ->toArray();
            $categoryName = implode(', ', array_map(fn($code) => strtoupper($dbNames[$code] ?? $code), $selectedCategories));
        }

        $scopeName = '';
        if ($request->filled('area_id')) {
            $area = \App\Models\Area::with('division')->find($request->input('area_id'));
            if ($area) {
                $divName = $area->division ? $area->division->div_name : '';
                $scopeName = $divName !== ''
                    ? "Division: {$divName} | Area: {$area->area_name}"
                    : "Area: {$area->area_name}";
            }
        } elseif ($request->filled('division_id')) {
            $division = \App\Models\Division::find($request->input('division_id'));
            if ($division) {
                $scopeName = "Division: {$division->div_name}";
            }
        }

        $fullName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
        $generatedBy = $fullName !== '' ? $fullName : ($user?->username ?? $user?->name ?? 'System');
        $generatedAt = \Carbon\Carbon::now('+08:00')->format('F j, Y h:i A');

        return [
            'expiringSupplies' => $expiringData,
            'categoryName' => $categoryName,
            'scopeName' => $scopeName,
            'selectedCategories' => $isAllCategories ? ['All'] : $selectedCategories,
            'statusFilter' => $statusFilter,
            'reportFilters' => $reportFilters,
            'generatedBy' => $generatedBy,
            'generatedAt' => $generatedAt,
        ];
    }
}

