<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Illuminate\Foundation\Http\FormRequest;

class StoreFbmBoostingJobPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_type' => strtolower(trim((string) $this->input('payment_type', 'client_payment'))),
            'status' => strtolower(trim((string) $this->input('status', 'confirmed'))),
            'currency' => strtoupper(trim((string) $this->input('currency', 'BDT'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_type' => ['required', 'in:advance,client_payment,refund,adjustment'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'method' => ['nullable', 'string', 'max:80'],
            'safe_reference' => ['nullable', 'string', 'max:180'],
            'status' => ['required', 'in:confirmed,pending,void'],
            'safe_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
