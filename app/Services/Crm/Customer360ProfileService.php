<?php

namespace App\Services\Crm;

use App\Http\Controllers\Customer\Models\Customer;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmCommunication;
use App\Models\Crm\CrmLead;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Customer360ProfileService
{
    public function profile(int $customerId): array
    {
        $relations = ['customerCategory', 'customerSourceType', 'assignedUser'];

        if (Schema::hasTable('crm_customer_tags') && Schema::hasTable('crm_customer_tag_pivots')) {
            $relations[] = 'tags';
        }

        $customer = Customer::query()->with($relations)->findOrFail($customerId);

        return [
            'customer' => $this->customerPayload($customer),
            'summary' => $this->summaryPayload($customer),
            'tags' => $this->tagPayload($customer),
            'quick_links' => $this->quickLinks($customer),
            'sections' => $this->sectionAvailability(),
        ];
    }

    public function activities(int $customerId, int $perPage = 15): array
    {
        Customer::query()->findOrFail($customerId);

        if (!Schema::hasTable('crm_activities')) {
            return $this->emptyPagination('CRM activity storage is not available.');
        }

        $query = CrmActivity::query()
            ->where('customer_id', $customerId)
            ->with(['performer:id,name'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');

        return $this->paginate($query, $perPage, function (CrmActivity $activity) {
            return [
                'id' => $activity->id,
                'activity_type' => $activity->activity_type,
                'subject' => $activity->subject,
                'description' => $activity->description,
                'actor' => optional($activity->performer)->name,
                'occurred_at' => $this->dateTime($activity->occurred_at ?: $activity->created_at),
            ];
        });
    }

    public function communications(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        if (!Schema::hasTable('crm_communications')) {
            return $this->emptyPagination('CRM communication storage is not available.');
        }

        $query = CrmCommunication::query()
            ->where('customer_id', $customerId)
            ->with(['sender:id,name'])
            ->orderByDesc('sent_at')
            ->orderByDesc('id');

        return $this->paginate($query, $perPage, function (CrmCommunication $communication) {
            return [
                'id' => $communication->id,
                'channel' => $communication->channel,
                'direction' => $communication->direction,
                'status' => $communication->status,
                'subject' => $communication->subject,
                'message_summary' => Str::limit(trim((string) $communication->message), 140),
                'source_module' => $communication->source_module,
                'provider' => $communication->provider,
                'actor' => optional($communication->sender)->name,
                'sent_at' => $this->dateTime($communication->sent_at ?: $communication->created_at),
            ];
        }, 'No CRM communication records exist yet. Existing SMS and newsletter flows remain unchanged until production hooks are added in a later stage.');
    }

    public function leads(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        if (!Schema::hasTable('crm_leads') || !Schema::hasColumn('crm_leads', 'customer_id')) {
            return $this->emptyPagination('CRM lead storage is not available.');
        }

        $relations = [];
        if (Schema::hasColumn('crm_leads', 'assigned_user_id')) {
            $relations[] = 'assignedUser:id,name';
        }

        $query = CrmLead::query()
            ->where('customer_id', $customerId)
            ->with($relations);

        if (Schema::hasColumn('crm_leads', 'status')) {
            $query->orderByRaw("CASE WHEN status IN ('converted','lost') THEN 2 ELSE 1 END");
        }
        if (Schema::hasColumn('crm_leads', 'next_follow_up_at')) {
            $query->orderBy('next_follow_up_at');
        }
        $query->orderByDesc('id');

        return $this->paginate($query, $perPage, function (CrmLead $lead) {
            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'phone' => $lead->phone,
                'source' => $lead->source,
                'status' => $lead->status,
                'priority' => $lead->priority,
                'estimated_value' => $this->money($lead->estimated_value),
                'requirement_summary' => Str::limit(trim((string) $lead->requirement), 120),
                'assigned_user' => optional($lead->assignedUser)->name,
                'next_follow_up_at' => $this->dateTime($lead->next_follow_up_at),
                'created_at' => $this->dateTime($lead->created_at),
            ];
        }, 'No CRM leads are linked to this customer.');
    }

    public function orders(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        return $this->tablePagination(
            'product_orders',
            $customerId,
            ['id', 'order_code', 'slug', 'sale_date', 'total', 'paid_amount', 'due_amount', 'payment_status', 'order_status', 'status', 'created_at'],
            $perPage,
            function ($row) {
                return [
                    'id' => $row->id,
                    'code' => $row->order_code ?? ('#' . $row->id),
                    'date' => $this->date($row->sale_date ?? $row->created_at ?? null),
                    'total' => $this->money($row->total ?? null),
                    'paid' => $this->money($row->paid_amount ?? null),
                    'due' => $this->money($row->due_amount ?? null),
                    'status' => $row->order_status ?? $row->payment_status ?? $row->status ?? null,
                    'url' => $this->slugRoute('ShowProductOrder', $row->slug ?? null),
                ];
            },
            'No customer orders were found.'
        );
    }

    public function quotations(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        return $this->tablePagination(
            'product_order_quotations',
            $customerId,
            ['id', 'order_code', 'slug', 'sale_date', 'quotation_date', 'valid_until', 'total', 'quotation_status', 'order_status', 'status', 'created_at'],
            $perPage,
            function ($row) {
                return [
                    'id' => $row->id,
                    'code' => $row->order_code ?? ('#' . $row->id),
                    'date' => $this->date($row->quotation_date ?? $row->sale_date ?? $row->created_at ?? null),
                    'valid_until' => $this->date($row->valid_until ?? null),
                    'total' => $this->money($row->total ?? null),
                    'status' => $row->quotation_status ?? $row->order_status ?? $row->status ?? null,
                    'url' => $this->slugRoute('EditProductOrderQuotation', $row->slug ?? null),
                ];
            },
            'No customer quotations were found.'
        );
    }

    public function contactHistories(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        return $this->tablePagination(
            'customer_contact_histories',
            $customerId,
            ['id', 'slug', 'subject', 'date', 'note', 'contact_history_status', 'priority', 'status', 'created_at'],
            $perPage,
            function ($row) {
                return [
                    'id' => $row->id,
                    'subject' => $row->subject ?? 'Contact history',
                    'date' => $this->date($row->date ?? $row->created_at ?? null),
                    'note' => $row->note ?? null,
                    'priority' => $row->priority ?? null,
                    'status' => $row->contact_history_status ?? $row->status ?? null,
                    'url' => $this->slugRoute('EditCustomerContactHistories', $row->slug ?? null),
                ];
            },
            'No customer contact-history records were found.'
        );
    }

    public function scheduledContacts(int $customerId, int $perPage = 10): array
    {
        Customer::query()->findOrFail($customerId);

        return $this->tablePagination(
            'customer_next_contact_dates',
            $customerId,
            ['id', 'slug', 'next_date', 'contact_status', 'status', 'created_at'],
            $perPage,
            function ($row) {
                return [
                    'id' => $row->id,
                    'date' => $this->date($row->next_date ?? $row->created_at ?? null),
                    'contact_status' => $row->contact_status ?? null,
                    'status' => $row->status ?? null,
                    'url' => $this->slugRoute('EditCustomerNextContactDate', $row->slug ?? null),
                ];
            },
            'No scheduled customer contacts were found.'
        );
    }

    public function returnsAndRefunds(int $customerId): array
    {
        Customer::query()->findOrFail($customerId);

        return [
            'returns' => $this->topRows(
                'product_order_returns',
                $customerId,
                ['id', 'slug', 'return_code', 'return_date', 'total', 'refund_status', 'return_status', 'status', 'created_at'],
                function ($row) {
                    return [
                        'id' => $row->id,
                        'code' => $row->return_code ?? ('#' . $row->id),
                        'date' => $this->date($row->return_date ?? $row->created_at ?? null),
                        'total' => $this->money($row->total ?? null),
                        'status' => $row->return_status ?? $row->refund_status ?? $row->status ?? null,
                        'url' => $this->slugRoute('ShowProductOrderReturn', $row->slug ?? null),
                    ];
                },
                'No customer return records were found.'
            ),
            'refunds' => $this->topRows(
                'product_order_refunds',
                $customerId,
                ['id', 'slug', 'refund_code', 'refund_date', 'refund_amount', 'refund_status', 'status', 'created_at'],
                function ($row) {
                    return [
                        'id' => $row->id,
                        'code' => $row->refund_code ?? ('#' . $row->id),
                        'date' => $this->date($row->refund_date ?? $row->created_at ?? null),
                        'total' => $this->money($row->refund_amount ?? null),
                        'status' => $row->refund_status ?? $row->status ?? null,
                        'url' => $this->slugRoute('ShowProductOrderRefund', $row->slug ?? null),
                    ];
                },
                'No customer refund records were found.'
            ),
        ];
    }

    protected function customerPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name ?: $customer->full_name ?: ('Customer #' . $customer->id),
            'customer_code' => $customer->customer_code,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'address' => $customer->address,
            'category' => optional($customer->customerCategory)->title,
            'source_type' => optional($customer->customerSourceType)->title,
            'lifecycle_stage' => $customer->lifecycle_stage,
            'assigned_user' => optional($customer->assignedUser)->name,
            'status' => $customer->status,
            'credit_limit' => $this->money($customer->credit_limit ?? null),
            'payment_terms_days' => $customer->payment_terms_days,
        ];
    }

    protected function summaryPayload(Customer $customer): array
    {
        return [
            'total_order_value' => $this->money($customer->total_order_value ?? null),
            'total_paid' => $this->money($customer->total_paid ?? $customer->paid ?? null),
            'current_due' => $this->money($customer->current_due ?? $customer->due ?? null),
            'advance_balance' => $this->money($customer->available_advance ?? $customer->balance ?? null),
            'overdue_amount' => $this->money($customer->overdue_amount ?? null),
            'last_order_at' => $this->dateTime($customer->last_order_at ?? null),
            'last_contact_at' => $this->dateTime($customer->last_contact_at ?? null),
            'next_follow_up_at' => $this->dateTime($customer->next_follow_up_at ?? null),
            'credit_status' => $customer->credit_status,
            'total_orders_count' => $this->countCustomerRows('product_orders', $customer->id),
        ];
    }

    protected function tagPayload(Customer $customer): array
    {
        if (!$customer->relationLoaded('tags')) {
            return [];
        }

        return $customer->tags
            ->sortBy('name')
            ->values()
            ->map(fn($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'status' => $tag->status,
            ])
            ->all();
    }

    protected function quickLinks(Customer $customer): array
    {
        $links = [];

        $this->addLink($links, 'Customer list', 'feather-list', $this->safeRoute('ViewAllCustomer'));
        $this->addLink($links, 'Edit customer', 'feather-edit-2', $this->slugRoute('EditCustomers', $customer->slug));
        $this->addLink($links, 'Payment history', 'feather-credit-card', $this->safeRoute('ViewCustomerPaymentHistory', ['customer_id' => $customer->id]));
        $this->addLink($links, 'Customer ledger', 'feather-book-open', $this->safeRoute('ledger.customer_ledger', [
            'customer_id' => $customer->id,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'store_id' => '',
        ]));
        $this->addLink($links, 'Pay due', 'feather-dollar-sign', $this->safeRoute('CreateCustomerDuePayment', ['customer_id' => $customer->id]));
        $this->addLink($links, 'Add contact history', 'feather-phone-call', $this->safeRoute('AddNewCustomerContactHistories', ['customer_id' => $customer->id]));
        $this->addLink($links, 'Schedule contact', 'feather-calendar', $this->safeRoute('AddNewCustomerNextContactDate', ['customer_id' => $customer->id]));

        return $links;
    }

    protected function sectionAvailability(): array
    {
        return [
            'notes' => Schema::hasTable('crm_notes'),
            'activities' => Schema::hasTable('crm_activities'),
            'communications' => Schema::hasTable('crm_communications'),
            'leads' => Schema::hasTable('crm_leads'),
            'tasks' => Schema::hasTable('crm_tasks'),
            'orders' => Schema::hasTable('product_orders'),
            'quotations' => Schema::hasTable('product_order_quotations'),
            'returns_refunds' => Schema::hasTable('product_order_returns') || Schema::hasTable('product_order_refunds'),
            'contact_histories' => Schema::hasTable('customer_contact_histories'),
            'scheduled_contacts' => Schema::hasTable('customer_next_contact_dates'),
        ];
    }

    protected function tablePagination(
        string $table,
        int $customerId,
        array $candidateColumns,
        int $perPage,
        callable $map,
        string $emptyMessage
    ): array {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'customer_id')) {
            return $this->emptyPagination('This data source is not available.');
        }

        $columns = $this->existingColumns($table, $candidateColumns);
        if (!in_array('id', $columns, true)) {
            return $this->emptyPagination('This data source cannot be safely listed.');
        }

        $query = DB::table($table)
            ->select($columns)
            ->where('customer_id', $customerId);

        if (Schema::hasColumn($table, 'status')) {
            $query->where(function ($nested) {
                $nested->whereNull('status')->orWhere('status', '<>', 'inactive');
            });
        }

        $query->orderByDesc('id');

        return $this->paginate($query, $perPage, $map, $emptyMessage);
    }

    protected function topRows(
        string $table,
        int $customerId,
        array $candidateColumns,
        callable $map,
        string $emptyMessage
    ): array {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'customer_id')) {
            return ['data' => [], 'message' => 'This data source is not available.'];
        }

        $columns = $this->existingColumns($table, $candidateColumns);
        if (!in_array('id', $columns, true)) {
            return ['data' => [], 'message' => 'This data source cannot be safely listed.'];
        }

        $query = DB::table($table)
            ->select($columns)
            ->where('customer_id', $customerId);

        if (Schema::hasColumn($table, 'status')) {
            $query->where(function ($nested) {
                $nested->whereNull('status')->orWhere('status', '<>', 'inactive');
            });
        }

        $rows = $query->orderByDesc('id')->limit(10)->get()->map($map)->values()->all();

        return ['data' => $rows, 'message' => empty($rows) ? $emptyMessage : null];
    }

    protected function paginate($query, int $perPage, callable $map, ?string $emptyMessage = null): array
    {
        $perPage = max(1, min($perPage, 50));
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage);
        $rows = collect($paginator->items())->map($map)->values()->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => empty($rows) ? $emptyMessage : null,
        ];
    }

    protected function emptyPagination(?string $message = null): array
    {
        return [
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 0,
                'total' => 0,
            ],
            'message' => $message,
        ];
    }

    protected function existingColumns(string $table, array $candidateColumns): array
    {
        return array_values(array_filter($candidateColumns, fn(string $column) => Schema::hasColumn($table, $column)));
    }

    protected function countCustomerRows(string $table, int $customerId): ?int
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'customer_id')) {
            return null;
        }

        $query = DB::table($table)->where('customer_id', $customerId);
        if (Schema::hasColumn($table, 'status')) {
            $query->where(function ($nested) {
                $nested->whereNull('status')->orWhere('status', '<>', 'inactive');
            });
        }

        return (int) $query->count();
    }

    protected function safeRoute(string $routeName, array $parameters = []): ?string
    {
        return Route::has($routeName) ? route($routeName, $parameters) : null;
    }

    protected function slugRoute(string $routeName, ?string $slug): ?string
    {
        return $slug && Route::has($routeName) ? route($routeName, ['slug' => $slug]) : null;
    }

    protected function addLink(array &$links, string $label, string $icon, ?string $url): void
    {
        if ($url) {
            $links[] = compact('label', 'icon', 'url');
        }
    }

    protected function date($value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    protected function dateTime($value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d h:i a') : null;
    }

    protected function money($value): ?string
    {
        return $value === null || $value === '' ? null : number_format((float) $value, 2);
    }
}
