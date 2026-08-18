<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class EquipmentImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user && $user->isInGeneralArea() && !$user->hasRole(['Admin', 'Superadmin', 'Developer'])) {
            return false;
        }
        return true;
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

            // Strip UTF-8 BOM if present
            if (isset($header[0])) {
                $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            }

            // Normalize header names
            $header = array_map(function ($col) {
                $col = strtolower(trim((string)$col));
                $col = str_replace([' ', '-'], '_', $col);
                return match ($col) {
                    'unit_val', 'unitval', 'unit_cost', 'cost' => 'unit_value',
                    'prop_no', 'property_no', 'propno' => 'property_number',
                    'serial_no', 'serialno' => 'serial_number',
                    'qty_card', 'card_qty', 'prop_card_qty' => 'quantity_per_property_card',
                    'qty_physical', 'physical_qty', 'count' => 'quantity_per_physical_count',
                    'uom' => 'unit_of_measure',
                    'div_id' => 'division_id',
                    default => $col,
                };
            }, $header);

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
                    // Clean empty strings to null and sanitize values
                    foreach ($data as $key => $value) {
                        if ($value === null) {
                            continue;
                        }
                        $value = trim((string)$value);
                        if ($value === '') {
                            $data[$key] = null;
                            continue;
                        }

                        // Sanitize numeric fields
                        if (in_array($key, ['unit_value', 'quantity_per_property_card', 'quantity_per_physical_count', 'division_id', 'area_id'])) {
                            $cleanNumeric = preg_replace('/[^\d.-]/', '', $value);
                            $data[$key] = $cleanNumeric !== '' ? $cleanNumeric : null;
                        } else {
                            $data[$key] = $value;
                        }

                        // Normalize status
                        if ($key === 'status' && $data[$key] !== null) {
                            $lowerStatus = strtolower(trim($data[$key]));
                            if (in_array($lowerStatus, ['unserviceable', 'damaged', 'condemned', 'depleted', 'inactive'])) {
                                $data[$key] = 'Unserviceable';
                            } else {
                                $data[$key] = 'Serviceable';
                            }
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
            'rows.*.article' => 'required|string',
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
                    $area = \App\Models\Area::find($value);
                    if ($area && strtolower(trim($area->area_name)) === 'general area') {
                        $index = explode('.', $attribute)[1];
                        $line = $this->input("rows.{$index}._line");
                        $fail("Line {$line}: Items cannot be imported to the General Area. Please specify a designated area.");
                        return;
                    }

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
            'rows.*.serial_number' => 'nullable|string',
            'rows.*.unit_value' => 'required|numeric|gt:0',
            'rows.*.quantity_per_property_card' => 'required|integer|min:0',
            'rows.*.quantity_per_physical_count' => 'required|integer|min:0',
            'rows.*.status' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if ($user && $user->isInGeneralArea() && !$user->hasRole(['Admin', 'Superadmin', 'Developer'])) {
                $validator->errors()->add('file', 'You are assigned to the General Area and cannot upload items. Please contact your administrator to change your designated area.');
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $messageBag = $validator->getMessageBag();
        $errors = is_object($messageBag) ? $messageBag->all() : (array)$validator->errors();
        $firstError = $errors[0] ?? 'Invalid data provided.';
        
        $messages = is_object($messageBag) ? $messageBag->messages() : [];
        $firstKey = !empty($messages) ? array_keys($messages)[0] : '';
        
        if ($firstKey && preg_match('/^rows\.(\d+)\.(.+)$/', $firstKey, $matches)) {
            $index = $matches[1];
            $line = $this->input("rows.{$index}._line", $index + 2);
            
            $originalError = is_object($messageBag) ? $messageBag->first($firstKey) : ($errors[0] ?? '');
            
            if (str_contains($originalError, "Line {$line}:")) {
                 $firstError = $originalError;
            } else {
                 $cleanError = preg_replace('/rows\.\d+\./', '', $originalError);
                 $cleanError = str_replace('_', ' ', $cleanError);
                 $firstError = "Line {$line}: {$cleanError}";
            }
        }

        throw ValidationException::withMessages([
            'file' => "Upload Failed. {$firstError}"
        ]);
    }
}
