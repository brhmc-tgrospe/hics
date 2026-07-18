<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Shared\Models\Category;
use App\Domain\Shared\Actions\GetCategoriesAction;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = app(GetCategoriesAction::class)->execute($request->all());

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'per_page', 'tab']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:categories,code',
            'name' => 'required|string',
            'type' => 'required|string|in:equipment,supply',
        ], [
            'code.unique' => 'An existing category is still in the database. Contact the system developer.'
        ]);

        Category::create($validated);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('Superadmin') && !$user->hasRole('Developer')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id'
        ]);

        $count = Category::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', "{$count} categories deleted.");
    }
}
