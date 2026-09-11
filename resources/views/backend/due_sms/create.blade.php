@extends('backend.master')

@section('header_css')
    <link href="{{ url('assets') }}/plugins/select2/select2.min.css" rel="stylesheet" />
    <style>
        .sms-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 15px;
        }

        .variable-tag {
            display: inline-block;
            background: #eef6ff;
            color: #0b63ce;
            padding: 4px 9px;
            border-radius: 20px;
            font-size: 12px;
            margin-right: 5px;
            margin-bottom: 5px;
            cursor: pointer;
        }
    </style>
@endsection

@section('page_title', 'Due Customer SMS')
@section('page_heading', 'Send SMS To Due Customers')

@section('content')
    <div class="container" style="max-width: 1100px;">
        <div class="sms-card">

            <form action="{{ route('DueCustomerSmsSend') }}" method="POST" id="dueSmsForm">
                @csrf

                <div class="form-group">
                    <label>Select Due Customers</label>

                    <div class="mb-2">
                        <label>
                            <input type="checkbox" id="selectAllCustomers">
                            Select All Due Customers
                        </label>
                    </div>

                    <select name="customer_ids[]" id="customer_ids" class="form-control select2" multiple required>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer['id'] }}" data-name="{{ $customer['name'] }}"
                                data-phone="{{ $customer['phone'] }}" data-total-order="{{ $customer['total_order'] }}"
                                data-due-order="{{ $customer['due_order'] }}"
                                data-due-amount="{{ $customer['due_amount'] }}">
                                {{ $customer['name'] }} - {{ $customer['phone'] }} - Due:
                                {{ number_format($customer['due_amount'], 2) }} ৳
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="summary-box mt-3">
                    <strong>Selected Customers:</strong> <span id="selectedCount">0</span>
                    |
                    <strong>Total Due:</strong> <span id="selectedTotalDue">0.00</span> ৳
                </div>

                <div class="table-responsive mt-3" id="selectedCustomerTableWrapper" style="display:none;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer Name</th>
                                <th>Phone</th>
                                <th>Total Order</th>
                                <th>Due Order</th>
                                <th>Due Amount</th>
                            </tr>
                        </thead>
                        <tbody id="selectedCustomerTable"></tbody>
                    </table>
                </div>

                <div class="form-group mt-4">
                    <label>SMS Message</label>

                    <div class="mb-2">
                        <span class="variable-tag" data-var="$customer_name">$customer_name</span>
                        <span class="variable-tag" data-var="$due">$due</span>
                        <span class="variable-tag" data-var="$company_name">$company_name</span>
                        <span class="variable-tag" data-var="$phone">$phone</span>
                    </div>

                    <textarea name="message" id="message" rows="5" class="form-control" required>{{ $defaultSms }}</textarea>

                    <small class="text-muted">
                        Variables will be replaced individually for every customer.
                    </small>
                </div>

                <div class="text-right mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-paper-plane"></i> Send SMS
                    </button>
                </div>
            </form>

        </div>
    </div>
@endsection

@section('footer_js')
    <script src="{{ url('assets') }}/plugins/select2/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#customer_ids').select2({
                placeholder: 'Select due customers',
                width: '100%'
            });

            function updateSelectedCustomerTable() {
                let selected = $('#customer_ids option:selected');
                let html = '';
                let totalDue = 0;

                selected.each(function(index) {
                    let option = $(this);

                    let name = option.data('name') || '';
                    let phone = option.data('phone') || '';
                    let totalOrder = parseInt(option.data('total-order')) || 0;
                    let dueOrder = parseInt(option.data('due-order')) || 0;
                    let dueAmount = parseFloat(option.data('due-amount')) || 0;

                    totalDue += dueAmount;

                    html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${name}</td>
                    <td>${phone}</td>
                    <td>${totalOrder}</td>
                    <td>${dueOrder}</td>
                    <td>${dueAmount.toFixed(2)} ৳</td>
                </tr>
            `;
                });

                $('#selectedCustomerTable').html(html);
                $('#selectedCount').text(selected.length);
                $('#selectedTotalDue').text(totalDue.toFixed(2));

                if (selected.length > 0) {
                    $('#selectedCustomerTableWrapper').show();
                } else {
                    $('#selectedCustomerTableWrapper').hide();
                }
            }

            $('#customer_ids').on('change', function() {
                updateSelectedCustomerTable();

                let totalOptions = $('#customer_ids option').length;
                let selectedOptions = $('#customer_ids option:selected').length;

                $('#selectAllCustomers').prop('checked', totalOptions === selectedOptions);
            });

            $('#selectAllCustomers').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#customer_ids option').prop('selected', true);
                } else {
                    $('#customer_ids option').prop('selected', false);
                }

                $('#customer_ids').trigger('change');
            });

            $('.variable-tag').on('click', function() {
                let variable = $(this).data('var');
                let textarea = $('#message');
                textarea.val(textarea.val() + ' ' + variable);
                textarea.focus();
            });
        });
    </script>
@endsection
