<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Domain\Shared\Actions\GetCategoriesAction;
use App\Domain\Shared\Actions\CreateCategoryAction;
use App\Domain\Shared\Actions\BulkDeleteCategoriesAction;
use App\Domain\Shared\DTOs\CategoryDTO;
use App\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    public function index(Request $request, GetCategoriesAction $action)
    {
        $categories = $action->execute($request->all());

        return Inertia::render('Category/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'per_page', 'tab']),
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategoryAction $action)
    {
        $dto = CategoryDTO::fromArray($request->validated());
        $category = $action->execute($dto);

        return redirect()->back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function bulkDestroy(Request $request, BulkDeleteCategoriesAction $action)
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole('Superadmin') && !$user->hasRole('Developer'))) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:categories,id'
        ]);

        $count = $action->execute($validated['ids']);

        return redirect()->back()->with('success', "{$count} categories deleted.");
    }
}
