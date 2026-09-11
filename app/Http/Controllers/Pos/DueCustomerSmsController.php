<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Customer\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DueCustomerSmsController extends Controller
{
    public function create()
    {
        try {
            $customers = Customer::query()
                ->select('id', 'name', 'phone', 'due', 'status')
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('due', '>', 0)
                        ->orWhereHas('orders', function ($q) {
                            $q->where('status', 'active')
                                ->directCustomerReceivable()
                                ->where('due_amount', '>', 0);
                        })
                        ->orWhereHas('openingBalances', function ($q) {
                            $q->where('status', 'active')
                                ->where('entry_type', 'due')
                                ->where('remaining_amount', '>', 0);
                        });
                })

                ->withCount([
                    'orders as total_order',
                    'orders as due_order' => function ($q) {
                        $q->where('status', 'active')
                            ->directCustomerReceivable()
                            ->where('due_amount', '>', 0);
                    },
                ])
                ->withSum([
                    'orders as order_due_amount' => function ($q) {
                        $q->where('status', 'active')
                            ->directCustomerReceivable()
                            ->where('due_amount', '>', 0);
                    },
                ], 'due_amount')
                ->withSum([
                    'openingBalances as opening_due_amount' => function ($q) {
                        $q->where('status', 'active')
                            ->where('entry_type', 'due')
                            ->where('remaining_amount', '>', 0);
                    },
                ], 'remaining_amount')
                ->orderBy('name')
                ->get()
                ->map(function ($customer) {
                    $totalDue = (float) (($customer->order_due_amount ?? 0) + ($customer->opening_due_amount ?? 0));
                    // (float) ($customer->due ?? 0) ??
                    // (float) ($customer->order_due_amount ?? 0);

                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'total_order' => $customer->total_order ?? 0,
                        'due_order' => $customer->due_order ?? 0,
                        'due_amount' => $totalDue,
                    ];
                });

            $defaultSms = 'Assalamualaikum, Dear $customer_name, you have $due BDT due in $company_name. Please pay your due as soon as possible. Thank you.';

            return view('backend.due_sms.create', compact('customers', 'defaultSms'));
        } catch (\Throwable $e) {
            Log::error('Due SMS create page error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Something went wrong while loading due customers.');
        }
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer', 'exists:customers,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $companyName = config('app.name');

        $customers = Customer::query()
            ->select('id', 'name', 'phone', 'due', 'status')
            ->whereIn('id', $data['customer_ids'])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('due', '>', 0)
                    ->orWhereHas('orders', function ($q) {
                        $q->where('status', 'active')
                            ->directCustomerReceivable()
                            ->where('due_amount', '>', 0);
                    })
                    ->orWhereHas('openingBalances', function ($q) {
                        $q->where('status', 'active')
                            ->where('entry_type', 'due')
                            ->where('remaining_amount', '>', 0);
                    });
            })
            ->withSum([
                'orders as order_due_amount' => function ($q) {
                    $q->where('status', 'active')
                        ->directCustomerReceivable()
                        ->where('due_amount', '>', 0);
                },
            ], 'due_amount')
            ->withSum([
                'openingBalances as opening_due_amount' => function ($q) {
                    $q->where('status', 'active')
                        ->where('entry_type', 'due')
                        ->where('remaining_amount', '>', 0);
                },
            ], 'remaining_amount')
            ->get();

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($customers as $customer) {
            if (empty($customer->phone)) {
                $skippedCount++;
                continue;
            }

            $due = (float) (($customer->order_due_amount ?? 0) + ($customer->opening_due_amount ?? 0));

            if ($due <= 0) {
                $skippedCount++;
                continue;
            }

            $message = str_replace(
                ['$customer_name', '$due', '$phone', '$company_name'],
                [
                    $customer->name,
                    number_format($due, 2),
                    $customer->phone,
                    $companyName,
                ],
                $data['message']
            );

            try {
                sms_send($customer->phone, $message);
                $successCount++;
            } catch (\Throwable $e) {
                $failedCount++;

                Log::error('Due customer SMS failed', [
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->back()
            ->with('success', "SMS sent: {$successCount}, failed: {$failedCount}, skipped: {$skippedCount}");
    }
}
