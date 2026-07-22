<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_areas');

        $user = auth()->user();
        
        $query = Area::with('division');

        if ($request->search) {
            $query->where('area_name', 'like', "%{$request->search}%");
        }

        // Apply division filter
        $myDivisionOnly = $request->boolean('my_division_only', true);
        if ($myDivisionOnly && $user->division_id) {
            $query->where('division_id', $user->division_id);
        }
        
        // Encoders and Secretaries can only see their specific area
        if ($user->hasRole(['Encoder', 'Secretary'])) {
            $query->where('id', $user->area_id);
        }

        $perPage = $request->input('per_page', 10);
        $areas = $query->paginate($perPage)->withQueryString();

        $divisions = \App\Models\Division::select('id', 'div_name as name')->get();

        return Inertia::render('Areas/Index', [
            'areas' => $areas,
            'filters' => $request->only(['search', 'per_page', 'my_division_only']),
            'divisions' => $divisions,
            'userDivisionId' => $user->division_id,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create_areas');

        $validated = $request->validate([
            'area_name' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id',
        ]);

        $user = auth()->user();
        
        // Division Admin can only add areas in their division (unless they are superadmin/dev)
        if (!$user->hasRole(['Superadmin', 'Developer'])) {
            if ($validated['division_id'] != $user->division_id) {
                abort(403, 'You can only add areas for your own division.');
            }
        }

        Area::create($validated);

        return redirect()->back()->with('success', 'Area created successfully.');
    }

    public function update(Request $request, Area $area)
    {
        Gate::authorize('edit_areas');

        $validated = $request->validate([
            'area_name' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id',
        ]);

        $user = auth()->user();
        
        if (!$user->hasRole(['Superadmin', 'Developer'])) {
            if ($area->division_id != $user->division_id || $validated['division_id'] != $user->division_id) {
                abort(403, 'You can only edit areas within your own division.');
            }
        }

        $area->update($validated);

        return redirect()->back()->with('success', 'Area updated successfully.');
    }

    public function destroy(Area $area)
    {
        Gate::authorize('delete_areas');

        $user = auth()->user();
        if (!$user->hasRole(['Superadmin', 'Developer'])) {
            if ($area->division_id != $user->division_id) {
                abort(403, 'You can only delete areas within your own division.');
            }
        }

        $area->delete();

        return redirect()->back()->with('success', 'Area deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        Gate::authorize('delete_areas');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:areas,id',
        ]);

        $areas = Area::whereIn('id', $request->ids)->get();
        $authUser = auth()->user();

        DB::transaction(function () use ($areas, $authUser) {
            foreach ($areas as $area) {
                if (!$authUser->hasRole(['Superadmin', 'Developer'])) {
                    if ($area->division_id != $authUser->division_id) {
                        continue;
                    }
                }
                $area->delete();
            }
        });

        return redirect()->back()->with('success', 'Selected areas deleted successfully.');
    }
}
