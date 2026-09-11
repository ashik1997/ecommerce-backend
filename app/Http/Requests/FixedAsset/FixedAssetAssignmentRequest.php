<?php

namespace App\Http\Requests\FixedAsset;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to_type' => ['required', 'in:employee,department,warehouse'],
            'warehouse_id' => ['required', 'exists:product_warehouses,id'],
            'department_id' => ['nullable', 'integer', 'required_if:assigned_to_type,department'],
            'employee_id' => ['nullable', 'integer', 'required_if:assigned_to_type,employee'],
            'assigned_at' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:assigned_at'],
            'issue_condition' => ['required', 'in:new,excellent,good,fair,poor,damaged,beyond_repair'],
            'handover_note' => ['nullable', 'string'],
        ];
    }
}
