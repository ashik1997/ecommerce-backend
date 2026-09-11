<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmBoostingJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => strtolower(trim((string) $this->input('mode', 'own_store'))),
            'status' => strtolower(trim((string) $this->input('status', 'draft'))),
            'currency' => strtoupper(trim((string) $this->input('currency', 'BDT'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'mode' => ['required', 'in:own_store,client_boosting'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'client_name' => ['nullable', 'string', 'max:180'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'planned_budget' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'service_fee' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,paused,completed,cancelled'],
            'safe_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
