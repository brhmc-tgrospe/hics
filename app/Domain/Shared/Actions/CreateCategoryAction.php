<?php

namespace App\Domain\Shared\Actions;

use App\Domain\Shared\DTOs\CategoryDTO;
use App\Domain\Shared\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function execute(CategoryDTO $dto): Category
    {
        $code = $this->resolveUniqueCode($dto);

        return Category::create([
            'code' => $code,
            'name' => $dto->name,
            'type' => $dto->type,
        ]);
    }

    private function resolveUniqueCode(CategoryDTO $dto): string
    {
        $baseCode = $dto->code 
            ? Str::slug($dto->code, '_') 
            : Str::slug($dto->name, '_');

        // Fallback in case name produces an empty slug (e.g. special characters only)
        if (empty($baseCode)) {
            $baseCode = 'cat_' . strtolower(Str::random(6));
        }

        $code = $baseCode;
        $counter = 1;

        while (Category::withTrashed()->where('type', $dto->type)->where('code', $code)->exists()) {
            $code = "{$baseCode}_{$counter}";
            $counter++;
        }

        return $code;
    }
}
