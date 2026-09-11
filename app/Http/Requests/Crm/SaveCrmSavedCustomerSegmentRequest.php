<?php

namespace App\Http\Requests\Crm;

use App\Services\Crm\CrmSavedCustomerSegmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SaveCrmSavedCustomerSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $filters = $this->input('filters', []);
        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $filters = $decoded;
            }
        }

        if (is_array($filters)) {
            $tagIds = $filters['tag_ids'] ?? [];
            if (!is_array($tagIds)) {
                $tagIds = $tagIds === null || $tagIds === '' ? [] : [$tagIds];
            }

            $filters['tag_ids'] = array_values(array_filter($tagIds, fn ($value) => $value !== null && $value !== ''));
            foreach (CrmSavedCustomerSegmentService::STRING_FILTER_KEYS as $key) {
                if (array_key_exists($key, $filters) && is_string($filters[$key])) {
                    $filters[$key] = trim($filters[$key]);
                }
            }
        }

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
            'visibility' => is_string($this->input('visibility')) ? trim($this->input('visibility')) : $this->input('visibility'),
            'filters' => $filters,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', Rule::in(CrmSavedCustomerSegmentService::VISIBILITIES)],
            'filters' => ['required', 'array'],
            'filters.customer_id' => ['nullable', 'integer', 'min:1'],
            'filters.assigned_user_id' => ['nullable', 'integer', 'min:1'],
            'filters.tag_ids' => ['nullable', 'array', 'max:50'],
            'filters.tag_ids.*' => ['integer', 'min:1', 'distinct'],
            'filters.lifecycle_stage' => ['nullable', 'string', 'max:40'],
            'filters.credit_status' => ['nullable', 'string', 'max:40'],
            'filters.risk_bucket' => ['nullable', Rule::in(['overdue', 'due', 'follow_up_due', 'duplicate'])],
            'filters.duplicate_candidate' => ['nullable', Rule::in(['yes', 'no'])],
            'filters.last_contact_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.last_contact_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.last_contact_from'],
            'filters.follow_up_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.follow_up_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.follow_up_from'],
            'filters.last_order_from' => ['nullable', 'date_format:Y-m-d'],
            'filters.last_order_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.last_order_from'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $filters = $this->input('filters');
            if (!is_array($filters)) {
                return;
            }

            $unknownKeys = array_values(array_diff(array_keys($filters), CrmSavedCustomerSegmentService::FILTER_KEYS));
            if (!empty($unknownKeys)) {
                $validator->errors()->add('filters', 'Unsupported saved segment filter key: ' . implode(', ', $unknownKeys) . '.');
            }

            if (!$this->containsActiveFilter($filters)) {
                $validator->errors()->add('filters', 'Select at least one customer portfolio filter before saving a segment.');
            }
        });
    }

    protected function containsActiveFilter(array $filters): bool
    {
        foreach (CrmSavedCustomerSegmentService::FILTER_KEYS as $key) {
            if (!array_key_exists($key, $filters)) {
                continue;
            }

            $value = $filters[$key];
            if (is_array($value) && !empty($value)) {
                return true;
            }
            if (!is_array($value) && $value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }
}
