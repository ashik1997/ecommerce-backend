<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class CrmCustomerSegmentWorklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $tagIds = $this->input('tag_ids', []);
        if (!is_array($tagIds)) {
            $tagIds = $tagIds === null || $tagIds === '' ? [] : [$tagIds];
        }

        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'assigned_user_id' => $this->input('assigned_user_id') ?: null,
            'tag_ids' => array_values(array_filter($tagIds, fn ($value) => $value !== null && $value !== '')),
            'lifecycle_stage' => is_string($this->lifecycle_stage) && trim($this->lifecycle_stage) !== '' ? trim($this->lifecycle_stage) : null,
            'credit_status' => is_string($this->credit_status) && trim($this->credit_status) !== '' ? trim($this->credit_status) : null,
            'risk_bucket' => is_string($this->risk_bucket) && trim($this->risk_bucket) !== '' ? trim($this->risk_bucket) : null,
            'duplicate_candidate' => is_string($this->duplicate_candidate) && trim($this->duplicate_candidate) !== '' ? trim($this->duplicate_candidate) : null,
            'last_contact_from' => $this->input('last_contact_from') ?: null,
            'last_contact_to' => $this->input('last_contact_to') ?: null,
            'follow_up_from' => $this->input('follow_up_from') ?: null,
            'follow_up_to' => $this->input('follow_up_to') ?: null,
            'last_order_from' => $this->input('last_order_from') ?: null,
            'last_order_to' => $this->input('last_order_to') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'assigned_user_id' => ['nullable', 'integer', 'min:1'],
            'tag_ids' => ['nullable', 'array', 'max:50'],
            'tag_ids.*' => ['integer', 'min:1', 'distinct'],
            'lifecycle_stage' => ['nullable', 'string', 'max:40'],
            'credit_status' => ['nullable', 'string', 'max:40'],
            'risk_bucket' => ['nullable', 'in:overdue,due,follow_up_due,duplicate'],
            'duplicate_candidate' => ['nullable', 'in:yes,no'],
            'last_contact_from' => ['nullable', 'date_format:Y-m-d'],
            'last_contact_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:last_contact_from'],
            'follow_up_from' => ['nullable', 'date_format:Y-m-d'],
            'follow_up_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:follow_up_from'],
            'last_order_from' => ['nullable', 'date_format:Y-m-d'],
            'last_order_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:last_order_from'],
        ];
    }
}
