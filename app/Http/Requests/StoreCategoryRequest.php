<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $user->hasRole('Superadmin') || $user->hasRole('Developer');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:equipment,supply'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Category type must be either equipment or supply.',
            'name.required' => 'Category name is required.',
        ];
    }
}
