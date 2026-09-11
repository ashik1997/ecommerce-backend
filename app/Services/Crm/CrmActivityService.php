<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CrmActivityService
{
    public function record(array $data): ?CrmActivity
    {
        try {
            return $this->createActivity($data);
        } catch (\Throwable $exception) {
            $this->logFailure($data, $exception);

            return null;
        }
    }

    public function recordOrFail(array $data): CrmActivity
    {
        try {
            return $this->createActivity($data);
        } catch (\Throwable $exception) {
            $this->logFailure($data, $exception);

            throw $exception;
        }
    }

    protected function createActivity(array $data): CrmActivity
    {
        return CrmActivity::create([
            'product_website_id' => $data['product_website_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'activity_type' => $data['activity_type'] ?? $data['type'] ?? 'general',
            'subject' => $data['subject'] ?? null,
            'description' => $data['description'] ?? null,
            'source_module' => $data['source_module'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'metadata' => Arr::get($data, 'metadata'),
            'performed_by' => $data['performed_by'] ?? auth()->id(),
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);
    }

    protected function logFailure(array $data, \Throwable $exception): void
    {
        Log::error('CRM activity record failed', [
            'customer_id' => $data['customer_id'] ?? null,
            'activity_type' => $data['activity_type'] ?? $data['type'] ?? null,
            'source_module' => $data['source_module'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
