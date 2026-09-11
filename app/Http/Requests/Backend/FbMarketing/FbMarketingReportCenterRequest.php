<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FbMarketingReportCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $today = CarbonImmutable::today();

        $this->merge([
            'from_date' => $this->input('from_date', $today->subDays(30)->toDateString()),
            'to_date' => $this->input('to_date', $today->toDateString()),
            'report_type' => $this->filled('report_type') ? strtolower(trim((string) $this->input('report_type'))) : 'attribution_reports',
        ]);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date', 'before_or_equal:today'],
            'report_type' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'ad_set_id' => ['nullable', 'integer', 'min:1'],
            'ad_id' => ['nullable', 'integer', 'min:1'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'evidence_state' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'attribution_method' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'stock_risk' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'catalog_status' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'profit_state' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
            'mode' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/'],
            'status' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9_]+$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            try {
                $from = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('from_date'))->startOfDay();
                $to = CarbonImmutable::createFromFormat('Y-m-d', (string) $this->input('to_date'))->startOfDay();
            } catch (\Throwable $exception) {
                return;
            }

            if ($to->gte($from) && $from->diffInDays($to) > 365) {
                $validator->errors()->add('to_date', 'The reporting center range may not exceed 366 calendar days.');
            }
        });
    }
}

