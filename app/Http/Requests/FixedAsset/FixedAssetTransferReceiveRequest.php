<?php

namespace App\Http\Requests\FixedAsset;

use Illuminate\Foundation\Http\FormRequest;

class FixedAssetTransferReceiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_date' => ['required', 'date'],
            'condition_at_receive' => ['required', 'in:new,excellent,good,fair,poor,damaged,beyond_repair'],
        ];
    }
}
