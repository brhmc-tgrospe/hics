<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Domain\Divisions\Actions\GetDivisionsAction;
use App\Domain\Divisions\Actions\CreateDivisionAction;
use App\Domain\Divisions\Actions\UpdateDivisionAction;
use App\Domain\Divisions\Actions\DeleteDivisionAction;
use App\Domain\Divisions\DTOs\DivisionDTO;
use Illuminate\Support\Facades\DB;

class DivisionController extends Controller
{
    public function index(Request $request, GetDivisionsAction $action)
    {
        $divisions = $action->execute([
            'search' => $request->search,
            'per_page' => $request->per_page,
        ]);
        
        return Inertia::render('Divisions/Index', [
            'divisions' => $divisions,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request, CreateDivisionAction $action)
    {
        $validated = $request->validate([
            'div_code' => 'required|string|max:255|unique:divisions',
            'div_name' => 'required|string|max:255|unique:divisions',
        ], [
            'div_code.unique' => 'An existing division is still in the database. Contact the system developer.',
            'div_name.unique' => 'An existing division is still in the database. Contact the system developer.'
        ]);

        DB::transaction(function () use ($validated, $action) {
            $action->execute(DivisionDTO::fromArray($validated));
        });

        return redirect()->back()->with('success', 'Division created successfully.');
    }

    public function update(Request $request, Division $division, UpdateDivisionAction $action)
    {
        $validated = $request->validate([
            'div_code' => 'required|string|max:255|unique:divisions,div_code,' . $division->id,
            'div_name' => 'required|string|max:255|unique:divisions,div_name,' . $division->id,
        ], [
            'div_code.unique' => 'An existing division is still in the database. Contact the system developer.',
            'div_name.unique' => 'An existing division is still in the database. Contact the system developer.'
        ]);

        DB::transaction(function () use ($division, $validated, $action) {
            $action->execute($division, DivisionDTO::fromArray($validated));
        });

        return redirect()->back()->with('success', 'Division updated successfully.');
    }

    public function destroy(Division $division, DeleteDivisionAction $action)
    {
        DB::transaction(function () use ($division, $action) {
            $action->execute($division);
        });

        return redirect()->back()->with('success', 'Division deleted successfully.');
    }

    public function bulkDestroy(Request $request, DeleteDivisionAction $action)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:divisions,id',
        ]);

        $divisions = Division::whereIn('id', $request->ids)->get();

        DB::transaction(function () use ($divisions, $action) {
            foreach ($divisions as $division) {
                $action->execute($division);
            }
        });

        return redirect()->back()->with('success', 'Selected divisions deleted successfully.');
    }
}
