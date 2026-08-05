<?php

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\Models\Category;

class BulkDeleteCategoriesAction
{
    public function execute(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $categories = Category::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($categories as $category) {
            $category->delete();
            $count++;
        }

        return $count;
    }
}
