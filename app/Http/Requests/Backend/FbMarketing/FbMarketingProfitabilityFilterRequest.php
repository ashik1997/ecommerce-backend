<?php

namespace App\Http\Requests\Backend\FbMarketing;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class FbMarketingProfitabilityFilterRequest extends FormRequest
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
            'profit_state' => $this->filled('profit_state') ? strtolower(trim((string) $this->input('profit_state'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date', 'before_or_equal:today'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'ad_set_id' => ['nullable', 'integer', 'min:1'],
            'ad_id' => ['nullable', 'integer', 'min:1'],
            'profit_state' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9_]+$/'],
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
                $validator->errors()->add('to_date', 'The profitability report range may not exceed 366 calendar days.');
            }
        });
    }
}
