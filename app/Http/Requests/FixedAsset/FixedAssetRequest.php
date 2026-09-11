<?php

namespace App\Http\Requests\FixedAsset;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'asset_name' => ['required','string','max:255'],
            'category_id' => ['required','exists:fa_categories,id'],
            'warehouse_id' => ['required','exists:product_warehouses,id'],
            'location_id' => ['nullable','exists:fa_locations,id'],
            'serial_number' => ['nullable','string','max:255'],
            'purchase_date' => ['nullable','date'],
            'available_for_use_date' => ['nullable','date'],
            'purchase_cost' => ['required','numeric','min:0'],
            'additional_cost' => ['nullable','numeric','min:0'],
            'residual_value' => ['nullable','numeric','min:0'],
            'useful_life_months' => ['nullable','integer','min:1'],
            'depreciation_method' => ['required','in:straight_line,diminishing_balance,units_of_production,none'],
            'condition_status' => ['required','in:new,excellent,good,fair,poor,damaged,beyond_repair'],
            'warranty_start_date' => ['nullable','date'],
            'warranty_end_date' => ['nullable','date'],
        ];
    }
}
