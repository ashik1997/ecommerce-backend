<?php

namespace App\Http\Requests\FixedAsset;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetAssignmentReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'returned_at' => ['required', 'date'],
            'return_condition' => ['required', 'in:new,excellent,good,fair,poor,damaged,beyond_repair'],
            'return_note' => ['nullable', 'string'],
        ];
    }
}
