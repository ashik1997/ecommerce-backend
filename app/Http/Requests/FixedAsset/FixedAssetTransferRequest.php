<?php

namespace App\Http\Requests\FixedAsset;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_warehouse_id' => ['required', 'exists:product_warehouses,id'],
            'to_location_id' => ['nullable', 'integer'],
            'transfer_date' => ['required', 'date'],
            'condition_at_dispatch' => ['required', 'in:new,excellent,good,fair,poor,damaged,beyond_repair'],
            'note' => ['nullable', 'string'],
        ];
    }
}
