@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
    <style>
        .journal-print { font-size: 12px; color: #000; background: #fff; }
        .journal-print .j-headings {
            display: flex;
            padding: 8px 10px;
            font-weight: 700;
            border-bottom: 2px solid #333;
            background: #f5f5f5;
        }
        .journal-print .j-headings .j-col-date { width: 50px; }
        .journal-print .j-headings .j-col-pc { width: 100px; }
        .journal-print .j-headings .j-col-type { width: 130px; }
        .journal-print .j-headings .j-col-part { flex: 1; }
        .journal-print .j-headings .j-col-debit { width: 120px; text-align: right; }
        .journal-print .j-headings .j-col-credit { width: 120px; text-align: right; }
        .journal-print .j-date-block { border-bottom: 1px solid #ddd; }
        .journal-print .j-date-row { font-weight: 700; padding: 6px 10px; background: #f8f8f8; }
        .journal-print .j-pc-block { padding-left: 70px; }
        .journal-print .j-pc-row { font-weight: 600; padding: 4px 10px; background: #f0f0f0; }
        .journal-print .j-type-block { padding-left: 70px; margin-top: 1px;}
        .journal-print .j-type-row { font-weight: 600; padding: 4px 10px; background: #eee; }
        .journal-print .j-row-block { padding-left: 1.5rem; }
        .journal-print .j-transaction-row {
            display: flex;
            align-items: flex-start;
            padding: 4px 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .journal-print .j-transaction-row.j-has-credit { padding-left: 2.5rem; }
        .journal-print .j-transaction-row .j-col-part { flex: 1; }
        .journal-print .j-transaction-row .j-col-debit { width: 120px; text-align: right; flex-shrink: 0; }
        .journal-print .j-transaction-row .j-col-credit { width: 120px; text-align: right; flex-shrink: 0; }
        .journal-print .j-note { color: #555; font-size: 11px; }
        .journal-print .j-reference {
            display: inline-block;
            margin-top: 3px;
            color: #2f80ed;
            font-size: 11px;
            font-weight: 600;
        }
        .journal-print .j-footer {
            display: flex;
            padding: 8px 10px;
            font-weight: 700;
            border-top: 2px solid #333;
            background: #f0f0f0;
        }
        .journal-print .j-footer .j-col-part { flex: 1; }
        .journal-print .j-footer .j-col-debit { width: 120px; text-align: right; }
        .journal-print .j-footer .j-col-credit { width: 120px; text-align: right; }
        @media print {
            form, .no-print, .pagination, .j-reference { display: none !important; }
            .journal-print .j-date-row, .journal-print .j-pc-row, .journal-print .j-type-row {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
        }
    </style>
@endsection

@section('page_title')
    Journal
@endsection

@section('page_heading')
    Journal
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Journal</h4>

                        <form method="GET" action="{{ route('journal.index') }}">
                            <div class="row">
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label for="from">From</label>
                                        <input type="date" id="from" name="from" class="form-control"
                                            value="{{ request('from', $from ?? now()->subDays(30)->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <div class="form-group">
                                        <label for="to">To</label>
                                        <input type="date" id="to" name="to" class="form-control"
                                            value="{{ request('to', $to ?? now()->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="page">Page</label>
                                        <input type="number" id="page" name="page" class="form-control" min="1"
                                            value="{{ request('page', $page ?? 1) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="account_id">Account</label>
                                        <select id="account_id" name="account_id" class="form-control">
                                            <option value="">All Accounts</option>
                                            @foreach (($accounts ?? []) as $account)
                                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_code ? $account->account_code . ' - ' : '' }}{{ $account->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                {{-- <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="store_id">Store ID</label>
                                        <input type="number" id="store_id" name="store_id" class="form-control"
                                            value="{{ request('store_id') }}">
                                    </div>
                                </div> --}}
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="customer_id">Customer</label>
                                        <select id="customer_id" name="customer_id" class="form-control">
                                            @if ($selectedCustomer ?? null)
                                                <option value="{{ $selectedCustomer->id }}" selected>
                                                    {{ $selectedCustomer->name }}{{ $selectedCustomer->phone ? ' - ' . $selectedCustomer->phone : '' }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="supplier_id">Supplier</label>
                                        <select id="supplier_id" name="supplier_id" class="form-control">
                                            @if ($selectedSupplier ?? null)
                                                <option value="{{ $selectedSupplier->id }}" selected>
                                                    {{ $selectedSupplier->name }}{{ $selectedSupplier->contact_number ? ' - ' . $selectedSupplier->contact_number : '' }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="transaction_type">Transaction Type</label>
                                        <select id="transaction_type" name="transaction_type" class="form-control">
                                            <option value="">All Transaction Types</option>
                                            @foreach (($transactionTypes ?? []) as $transactionType)
                                                <option value="{{ $transactionType }}" {{ request('transaction_type') === $transactionType ? 'selected' : '' }}>
                                                    {{ \Illuminate\Support\Str::of($transactionType)->replace(['_', '-'], ' ')->title() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2">
                                    <div class="form-group">
                                        <label for="event_type">Event Type</label>
                                        <select id="event_type" name="event_type" class="form-control">
                                            <option value="">All Event Types</option>
                                            @foreach (($eventTypes ?? []) as $eventType)
                                                <option value="{{ $eventType }}" {{ request('event_type') === $eventType ? 'selected' : '' }}>
                                                    {{ \Illuminate\Support\Str::of($eventType)->replace(['_', '-'], ' ')->title() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-2 d-flex align-items-end">
                                    <button class="btn btn-primary" type="submit">Filter</button>
                                    <button class="btn btn-secondary ml-2" type="button" onclick="window.print()">Print</button>
                                </div>
                            </div>
                        </form>

                        <div class="journal-print mt-4">
                            {{-- Headings --}}
                            <div class="j-headings">
                                <span class="j-col-date">Date</span>
                                <span class="j-col-pc">Payment Code</span>
                                <span class="j-col-type">Transaction Type</span>
                                <span class="j-col-part">Particulars</span>
                                <span class="j-col-debit">Debit</span>
                                <span class="j-col-credit">Credit</span>
                            </div>

                            @php $journalData = $journalData ?? []; @endphp
                            @forelse($journalData as $block)
                                @if (isset($block['dr']) && isset($block['cr']))
                                    {{-- Opening balance --}}
                                    <div class="j-date-block">
                                        <div class="j-date-row" style="display: flex; align-items: center;">
                                            <span class="j-col-date" style="width: 11%; min-width: 90px;">{{ $block['date'] }}</span>
                                            <span class="j-col-pc">—</span>
                                            <span class="j-col-type" style="width: 16%; min-width: 120px;">Opening balance</span>
                                            <span class="j-col-part" style="flex: 1;">—</span>
                                            <span class="j-col-debit" style="width: 120px; text-align: right;">{{ number_format($block['dr'], 2) }}</span>
                                            <span class="j-col-credit" style="width: 120px; text-align: right;">{{ number_format($block['cr'], 2) }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{-- Date -> Payment Code -> Transaction Type -> Rows --}}
                                    @if (!empty($block['payment_codes']))
                                        <div class="j-date-block">
                                            <div class="j-date-row">{{ $block['date'] ?? '' }}</div>
                                            @foreach ($block['payment_codes'] as $pcBlock)
                                                <div class="j-pc-block">
                                                    <div class="j-pc-row">{{ $pcBlock['payment_code'] ?? '' }}</div>
                                                    @foreach ($pcBlock['transactions'] ?? [] as $typeBlock)
                                                        @if (count($typeBlock['transactions'] ?? []) > 0)
                                                            <div class="j-type-block">
                                                                <div class="j-type-row">{{ $typeBlock['type'] ?? '' }}</div>
                                                                <div class="j-row-block">
                                                                    @foreach ($typeBlock['transactions'] as $row)
                                                                        <div class="j-transaction-row {{ (($row['credit_amount'] ?? 0) > 0) ? 'j-has-credit' : '' }}">
                                                                            <span class="j-col-part">
                                                                                {{ $row['account_head'] ?? '' }}
                                                                                @if (!empty($row['note']))
                                                                                    <span class="j-note"> — {{ $row['note'] }}</span>
                                                                                @endif
                                                                                @if (!empty($row['event_type']))
                                                                                    <span class="j-note"> ({{ $row['event_type'] }})</span>
                                                                                @endif
                                                                                @if (!empty($row['reference']['url']))
                                                                                    <br>
                                                                                    <a href="{{ $row['reference']['url'] }}" target="_blank" rel="noopener" class="j-reference">
                                                                                        <i class="fas fa-external-link-alt"></i>
                                                                                        {{ $row['reference']['label'] ?? 'Reference' }}
                                                                                        @if (!empty($row['reference']['text']))
                                                                                            - {{ $row['reference']['text'] }}
                                                                                        @endif
                                                                                    </a>
                                                                                @endif
                                                                            </span>
                                                                            @php $d = (float)($row['debit_amount'] ?? 0); $c = (float)($row['credit_amount'] ?? 0); @endphp
                                                                            <span class="j-col-debit">{{ ($d ?? 0) > 0 ? (($d ?? 0) == (int)($d ?? 0) ? number_format($d ?? 0, 0) : number_format($d ?? 0, 2)) : '' }}</span>
                                                                            <span class="j-col-credit">{{ ($c ?? 0) > 0 ? (($c ?? 0) == (int)($c ?? 0) ? number_format($c ?? 0, 0) : number_format($c ?? 0, 2)) : '' }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            @empty
                                <div class="py-4 text-center">No journal data for the selected period.</div>
                            @endforelse

                            {{-- Footer total --}}
                            <div class="j-footer">
                                <span class="j-col-part">Total (this page)</span>
                                <span class="j-col-debit">{{ number_format($totalDebit ?? 0, 2) }}</span>
                                <span class="j-col-credit">{{ number_format($totalCredit ?? 0, 2) }}</span>
                            </div>

                            <div class="mt-3">
                                {!! $transactions->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>
    <script>
        $(function () {
            $('#account_id').select2({
                placeholder: 'All Accounts',
                allowClear: true,
                width: '100%'
            });

            $('#customer_id').select2({
                placeholder: 'Search customer',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: @json(url('/customers')),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        const rows = Array.isArray(data) ? data : [];

                        return {
                            results: rows.map(function (customer) {
                                const label = [
                                    customer.name || ('Customer #' + customer.id),
                                    customer.phone || ''
                                ].filter(Boolean).join(' - ');

                                return {
                                    id: customer.id,
                                    text: label
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            $('#supplier_id').select2({
                placeholder: 'Search supplier',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: @json(route('ledger.suppliers.search')),
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        const rows = Array.isArray(data) ? data : [];

                        return {
                            results: rows.map(function (supplier) {
                                const label = [
                                    supplier.name || ('Supplier #' + supplier.id),
                                    supplier.contact_number || ''
                                ].filter(Boolean).join(' - ');

                                return {
                                    id: supplier.id,
                                    text: label
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            $('#transaction_type').select2({
                placeholder: 'All Transaction Types',
                allowClear: true,
                width: '100%'
            });

            $('#event_type').select2({
                placeholder: 'All Event Types',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endsection
