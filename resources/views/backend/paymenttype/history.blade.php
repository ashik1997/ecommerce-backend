@extends('backend.master')

@section('header_css')
    <link href="{{ url('dataTable') }}/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="{{ url('dataTable') }}/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        .payment-history-hero {
            border: 1px solid #e7edf5;
            border-left: 4px solid #2f80ed;
            background: #f8fbff;
            border-radius: 6px;
            padding: 18px 20px;
        }

        .payment-history-title {
            font-size: 22px;
            font-weight: 700;
            color: #1f2d3d;
            margin-bottom: 4px;
        }

        .metric-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .metric-value {
            font-size: 18px;
            font-weight: 700;
            color: #1f2d3d;
        }

        .filter-panel {
            border: 1px solid #eef1f5;
            border-radius: 6px;
            padding: 14px;
            background: #fff;
        }

        .history-table td,
        .history-table th {
            vertical-align: middle !important;
            text-align: center;
        }

        .details-note {
            max-width: 420px;
            white-space: normal;
            text-align: left;
        }
    </style>
@endsection

@section('page_title')
    Payment Type History
@endsection

@section('page_heading')
    Payment Type History
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Payment Ledger</h4>
                            <small class="text-muted">Daily movement summary with income, expense, and running balance.</small>
                        </div>
                        <a href="{{ route('ViewAllPaymentType') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>

                    <div class="payment-history-hero mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <div class="payment-history-title">{{ $paymentType->payment_type }}</div>
                                <div class="text-muted">
                                    {{ ucfirst($paymentType->payment_category ?? 'Uncategorized') }} payment account history
                                </div>
                            </div>
                            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                <div class="metric-label">Current Balance</div>
                                <div class="metric-value">৳ {{ number_format($currentBalance, 2) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-panel mb-3">
                        <form id="historyFilterForm" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" class="form-control" value="{{ $defaultStartDate }}">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" class="form-control" value="{{ $defaultEndDate }}">
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                                <button type="button" id="resetFilter" class="btn btn-light">
                                    Reset
                                </button>
                            </div>
                            <div class="col-12">
                                <small id="dateError" class="text-danger d-none">Start date must be before or equal to end date.</small>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered history-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Income (Dr)</th>
                                    <th>Expense (Cr)</th>
                                    <th>Balance</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="historyDetailsModal" tabindex="-1" role="dialog" aria-labelledby="historyDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyDetailsModalLabel">Transaction Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody id="detailsBody">
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No details loaded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('dataTable') }}/js/jquery.validate.js"></script>
    <script src="{{ url('dataTable') }}/js/jquery.dataTables.min.js"></script>
    <script src="{{ url('dataTable') }}/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(function () {
            var defaultStartDate = @json($defaultStartDate);
            var defaultEndDate = @json($defaultEndDate);
            var historyUrl = @json(route('PaymentTypeHistory', $paymentType->slug));
            var detailsUrl = @json(route('PaymentTypeHistoryDetails', $paymentType->slug));

            function datesAreValid() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var valid = !startDate || !endDate || startDate <= endDate;
                $('#dateError').toggleClass('d-none', valid);
                return valid;
            }

            var table = $('.history-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50], [10, 25, 50]],
                searching: false,
                order: [[0, 'asc']],
                ajax: {
                    url: historyUrl,
                    data: function (d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [
                    { data: 'transaction_date', name: 'transaction_date' },
                    { data: 'income', name: 'income', orderable: false, searchable: false },
                    { data: 'expense', name: 'expense', orderable: false, searchable: false },
                    { data: 'balance', name: 'balance', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });

            $('#historyFilterForm').on('submit', function (event) {
                event.preventDefault();
                if (!datesAreValid()) {
                    return;
                }
                table.ajax.reload();
            });

            $('#start_date, #end_date').on('change', datesAreValid);

            $('#resetFilter').on('click', function () {
                $('#start_date').val(defaultStartDate);
                $('#end_date').val(defaultEndDate);
                $('#dateError').addClass('d-none');
                table.ajax.reload();
            });

            $('body').on('click', '.history-detail-btn', function () {
                var date = $(this).data('date');
                $('#historyDetailsModalLabel').text($(this).data('title') + ' - ' + date);
                $('#detailsBody').html('<tr><td colspan="6" class="text-center text-muted">Loading...</td></tr>');
                $('#historyDetailsModal').modal('show');

                $.get(detailsUrl, { date: date })
                    .done(function (response) {
                        var rows = response.data || [];
                        $('#historyDetailsModalLabel').text((response.payment_type || 'Payment') + ' - ' + (response.date || date));

                        if (!rows.length) {
                            $('#detailsBody').html('<tr><td colspan="6" class="text-center text-muted">No transactions found.</td></tr>');
                            return;
                        }

                        var html = rows.map(function (row) {
                            var typeClass = row.type === 'Income' ? 'badge-success' : 'badge-danger';
                            var reference = referenceHtml(row.reference || {});
                            return '<tr>' +
                                '<td>' + escapeHtml(row.payment_code || '') + '</td>' +
                                '<td><span class="badge ' + typeClass + '">' + escapeHtml(row.type || '') + '</span></td>' +
                                '<td>' + reference + '</td>' +
                                '<td>' + escapeHtml(row.account || '') + '</td>' +
                                '<td class="font-weight-bold">৳ ' + escapeHtml(row.amount || '0.00') + '</td>' +
                                '<td class="details-note">' + escapeHtml(row.note || '') + '</td>' +
                                '</tr>';
                        }).join('');

                        $('#detailsBody').html(html);
                    })
                    .fail(function () {
                        $('#detailsBody').html('<tr><td colspan="6" class="text-center text-danger">Unable to load transaction details.</td></tr>');
                    });
            });

            function referenceHtml(reference) {
                var label = escapeHtml(reference.label || 'Reference');
                var text = escapeHtml(reference.text || 'Open');

                if (!reference.url) {
                    return '<span class="text-muted">' + label + '<br><small>' + text + '</small></span>';
                }

                return '<a href="' + escapeHtml(reference.url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">' +
                    '<i class="fas fa-external-link-alt"></i> ' + label +
                    '<br><small>' + text + '</small>' +
                    '</a>';
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        });
    </script>
@endsection
