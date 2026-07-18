<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SupplyImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Controller handles authorization via Gate
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->hasFile('file') && $this->file('file')->isValid()) {
            $path = $this->file('file')->getRealPath();
            $file = fopen($path, 'r');
            $header = fgetcsv($file);

            if (!$header) {
                return; // Will fail the basic 'rows' requirement
            }

            $rows = [];
            $lineNumber = 2; // Line 1 is header
            while (($row = fgetcsv($file)) !== false) {
                // Skip the hint row
                if ($lineNumber === 2 && str_starts_with($row[0] ?? '', 'Hint:')) {
                    $lineNumber++;
                    continue;
                }

                if (count($header) === count($row)) {
                    $data = array_combine($header, $row);
                    // Clean empty strings to null
                    foreach ($data as $key => $value) {
                        if (trim($value) === '') {
                            $data[$key] = null;
                        }
                    }
                    $data['_line'] = $lineNumber; // Store line number for custom error messages
                    $rows[] = $data;
                }
                $lineNumber++;
            }
            fclose($file);

            $this->merge([
                'rows' => $rows,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt',
            'rows' => 'required|array|min:1',
            'rows.*.category' => 'required|string',
            'rows.*.description' => 'required|string',
            'rows.*.division_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $user = $this->user();
                    if ($user->hasRole('Superadmin') || $user->hasRole('Developer')) {
                        return;
                    }
                    if ($value != $user->division_id) {
                        $index = explode('.', $attribute)[1];
                        $line = $this->input("rows.{$index}._line");
                        $fail("Line {$line}: You are only allowed to upload data for your assigned division.");
                    }
                }
            ],
            'rows.*.area_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $user = $this->user();
                    if ($user->hasRole('Superadmin') || $user->hasRole('Developer') || $user->hasRole('Admin')) {
                        return; // Admins can upload to any area in their division (division checked above)
                    }
                    if ($user->hasRole('Encoder') && $value != $user->area_id) {
                        $index = explode('.', $attribute)[1];
                        $line = $this->input("rows.{$index}._line");
                        $fail("Line {$line}: You are only allowed to upload data for your assigned area.");
                    }
                }
            ],
            'rows.*.balance_per_card' => 'required|numeric|gt:0',
            'rows.*.on_hand_per_count' => 'required|numeric|gt:0',
            'rows.*.expiry_date' => 'required_if:rows.*.category,mssup,enteral,drugs',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $errors = $validator->errors()->all();
        $firstError = $errors[0];
        
        // Extract line number if it's a 'rows' error
        $firstKey = array_keys($validator->errors()->messages())[0];
        if (preg_match('/^rows\.(\d+)\.(.+)$/', $firstKey, $matches)) {
            $index = $matches[1];
            $attributeName = str_replace('_id', '', $matches[2]);
            $line = $this->input("rows.{$index}._line", $index + 2);
            $firstError = "Line {$line}: The {$attributeName} field is required or invalid.";
            
            // Handle required_if specifically for expiry date
            if (str_contains($errors[0], 'expiry date field is required when')) {
                $firstError = "Line {$line}: Expiry date is required for Medical and Surgical Supplies, Enteral Supplies, and Drugs and Medicines.";
            }

            // Check if it's a custom error message from closure
            $originalError = $validator->errors()->first($firstKey);
            if (str_contains($originalError, "Line {$line}:")) {
                 $firstError = $originalError;
            }
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'file' => "Upload Failed. {$firstError}"
        ]);
    }
}
