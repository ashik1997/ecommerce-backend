<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FbMarketingDashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $today = CarbonImmutable::today();

        $this->merge([
            'from_date' => $this->input('from_date', $today->subDays(7)->toDateString()),
            'to_date' => $this->input('to_date', $today->subDay()->toDateString()),
            'compare_period' => $this->boolean('compare_period'),
        ]);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date', 'before_or_equal:today'],
            'ad_account_id' => ['nullable', 'integer', 'min:1'],
            'compare_period' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $from = $this->input('from_date');
            $to = $this->input('to_date');

            if (!is_string($from) || !is_string($to)) {
                return;
            }

            try {
                $fromDate = CarbonImmutable::createFromFormat('Y-m-d', $from)->startOfDay();
                $toDate = CarbonImmutable::createFromFormat('Y-m-d', $to)->startOfDay();
            } catch (\Throwable $exception) {
                return;
            }

            if ($toDate->gte($fromDate) && $fromDate->diffInDays($toDate) > 365) {
                $validator->errors()->add('to_date', 'The dashboard range may not exceed 366 calendar days.');
            }
        });
    }
}
