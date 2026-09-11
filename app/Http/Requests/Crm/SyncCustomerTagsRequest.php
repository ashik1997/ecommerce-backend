<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class SyncCustomerTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tag_ids' => ['present', 'array', 'max:100'],
            'tag_ids.*' => ['integer', 'distinct', 'exists:crm_customer_tags,id'],
        ];
    }
}
