@extends('backend.master')

@section('header_css')
    @php
        $commissionSalesUsers = \Illuminate\Support\Facades\DB::table('users')
            ->select('id', 'name', 'phone')
            ->where('status', 1)
            ->whereIn('user_type', [1, 2])
            ->orderBy('name')
            ->get();

        $commissionAffiliates = \Illuminate\Support\Facades\Schema::hasTable('affiliates')
            ? \Illuminate\Support\Facades\DB::table('affiliates')
                ->select('id', 'name', 'code', 'phone')
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
            : collect();
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <link href="{{ versioned_url('assets/plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <script>
        window.ECOM_ORDER_CONFIG = {
            routes: {
                orderInfo: "{{ route('ecommerce.order-info', ['id' => '__ID__']) }}",
                orderUpdate: "{{ route('ecommerce.order-update', ['id' => '__ID__']) }}",
                productSearch: "{{ route('ecommerce.products.search') }}",
                districts: "{{ route('ecommerce.districts') }}",
                upazilas: "{{ route('ecommerce.upazilas') }}",
                paymentMethods: "{{ route('ecommerce.payment-methods') }}",
                warehouses: "{{ route('ecommerce.warehouses') }}",
                outlets: "{{ route('ecommerce.outlets') }}",
                applyCoupon: "{{ route('pos.desktop.apply-coupon') }}",
            },
            file_url: "{{ get_file_url() }}",
            sales_users: @json($commissionSalesUsers),
            affiliates: @json($commissionAffiliates),
            csrf: "{{ csrf_token() }}",
        };
        // Global axios CSRF setup
        axios.defaults.headers.common['X-CSRF-TOKEN'] = "{{ csrf_token() }}";
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f0f2f7;
        }

        ::-webkit-scrollbar-thumb {
            background: #bcc4dd;
            border-radius: 3px;
        }

        /* ════════════════════════════════════════════════════
                                       ALL COMPONENT STYLES PREFIXED WITH #order_details_component
                                       ════════════════════════════════════════════════════ */

        #order_details_component {
            --bg: #f0f2f7;
            --surface: #ffffff;
            --surface2: #f7f8fc;
            --surface3: #eef0f7;
            --border: #dde1ef;
            --border2: #bcc4dd;
            --accent: #3b6ef0;
            --accent2: #6c44f4;
            --green: #16a05c;
            --green-bg: #edfaf4;
            --green-border: #b6ebd5;
            --red: #d93535;
            --red-bg: #fef0f0;
            --red-border: #f5c0c0;
            --yellow: #b07d00;
            --yellow-bg: #fef9e7;
            --orange: #c4601a;
            --text: #1a2040;
            --text2: #5a6480;
            --text3: #9aa0b8;
            --radius: 10px;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'IBM Plex Sans', sans-serif;
            --shadow: 0 1px 3px rgba(30, 40, 90, 0.08), 0 1px 2px rgba(30, 40, 90, 0.05);
            --shadow-md: 0 4px 16px rgba(30, 40, 90, 0.10);
            color: var(--text);
            font-family: var(--sans);
            font-size: 14px;
        }

        /* TOPBAR */
        #order_details_component .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 11px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        #order_details_component .topbar-brand {
            font-family: var(--mono);
            font-size: 13px;
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 1px;
        }

        #order_details_component .topbar-order {
            font-family: var(--mono);
            font-size: 12px;
            color: var(--text2);
            background: var(--surface3);
            padding: 4px 10px;
            border-radius: 5px;
            border: 1px solid var(--border);
        }

        #order_details_component .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* BADGES */
        #order_details_component .badge {
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #order_details_component .badge-pending {
            background: var(--yellow-bg);
            color: var(--yellow);
            border: 1px solid #e8d080;
        }

        #order_details_component .badge-accepted {
            background: var(--green-bg);
            color: var(--green);
            border: 1px solid var(--green-border);
        }

        #order_details_component .badge-processing {
            background: #eef3fe;
            color: var(--accent);
            border: 1px solid #c0d0fa;
        }

        #order_details_component .badge-delivered {
            background: #f2eefe;
            color: var(--accent2);
            border: 1px solid #cfc2fa;
        }

        #order_details_component .badge-returned {
            background: var(--red-bg);
            color: var(--red);
            border: 1px solid var(--red-border);
        }

        /* LAYOUT */
        #order_details_component .page {
            margin: 0 auto;
            padding: 20px 0px;
        }

        #order_details_component .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        #order_details_component .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 14px;
        }

        /* SECTION */
        #order_details_component .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        #order_details_component .section-header {
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            padding: 11px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #order_details_component .section-num {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--accent);
            background: #eef3fe;
            border: 1px solid #c0d0fa;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        #order_details_component .section-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        #order_details_component .section-body {
            padding: 18px;
        }

        /* FORM */
        #order_details_component label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: var(--text2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-family: var(--mono);
        }

        #order_details_component input,
        #order_details_component select,
        #order_details_component textarea {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--text);
            font-family: var(--sans);
            font-size: 14px;
            padding: 8px 11px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        #order_details_component input:focus,
        #order_details_component select:focus,
        #order_details_component textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 110, 240, 0.1);
        }

        #order_details_component select {
            cursor: pointer;
        }

        #order_details_component .select2-container {
            width: 100% !important;
            font-family: var(--sans);
            font-size: 14px;
        }

        #order_details_component .select2-container--default .select2-selection--single {
            height: 38px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 7px;
            outline: none;
        }

        #order_details_component .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text);
            line-height: 36px;
            padding-left: 11px;
            padding-right: 30px;
        }

        #order_details_component .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: var(--text3);
        }

        #order_details_component .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        #order_details_component .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 110, 240, 0.1);
        }

        #order_details_component textarea {
            resize: vertical;
            min-height: 68px;
        }

        #order_details_component input[readonly] {
            background: var(--surface3);
            color: var(--text2);
            cursor: default;
        }

        #order_details_component .form-group {
            margin-bottom: 13px;
        }

        #order_details_component .form-group:last-child {
            margin-bottom: 0;
        }

        #order_details_component .input-prefix {
            display: flex;
            align-items: stretch;
        }

        #order_details_component .input-prefix .prefix {
            background: var(--surface3);
            border: 1px solid var(--border);
            border-right: none;
            border-radius: 7px 0 0 7px;
            padding: 8px 11px;
            color: var(--text2);
            font-size: 13px;
            font-family: var(--mono);
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        #order_details_component .input-prefix input {
            border-radius: 0 7px 7px 0;
        }

        /* PRODUCT TABLE */
        #order_details_component .product-search-wrap {
            position: relative;
            margin-bottom: 14px;
        }

        #order_details_component .product-search-wrap input {
            padding-left: 36px;
        }

        #order_details_component .search-icon {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text3);
            font-size: 15px;
            pointer-events: none;
        }

        #order_details_component .table-wrap {
            overflow-x: auto;
        }

        #order_details_component table {
            width: 100%;
            border-collapse: collapse;
            min-width: 820px;
        }

        #order_details_component th {
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            padding: 9px 12px;
            text-align: left;
            font-family: var(--mono);
            font-size: 10px;
            font-weight: 600;
            color: var(--text2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        #order_details_component td {
            padding: 9px 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        #order_details_component tr:last-child td {
            border-bottom: none;
        }

        #order_details_component tr:hover td {
            background: #fafbff;
        }

        #order_details_component .product-cell {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-width: 270px;
        }

        #order_details_component .product-img {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            background: var(--surface3);
            flex-shrink: 0;
            border: 1px solid var(--border);
        }

        #order_details_component .product-info {
            flex: 1;
        }

        #order_details_component .product-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            line-height: 1.35;
            margin-bottom: 3px;
        }

        #order_details_component .product-name:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        #order_details_component .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
            margin-bottom: 4px;
        }

        #order_details_component .meta-tag {
            font-size: 10px;
            color: var(--text2);
            font-family: var(--mono);
            background: var(--surface3);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 6px;
        }

        #order_details_component .meta-tag.green {
            background: var(--green-bg);
            color: var(--green);
            border-color: var(--green-border);
        }

        #order_details_component .td-input {
            background: var(--surface3);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-family: var(--mono);
            font-size: 13px;
            padding: 6px 8px;
            outline: none;
            width: 90px;
            transition: border-color 0.15s;
        }

        #order_details_component .td-input:focus {
            border-color: var(--accent);
            background: #fff;
        }

        #order_details_component .td-input.sm {
            width: 90px;
        }

        #order_details_component .td-input.qty {
            width: 58px;
            text-align: center;
        }

        #order_details_component .del-btn {
            background: var(--red-bg);
            border: 1px solid var(--red-border);
            color: var(--red);
            border-radius: 6px;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all 0.15s;
        }

        #order_details_component .del-btn:hover {
            background: #fcd9d9;
        }

        #order_details_component .add-product-row {
            padding: 10px 14px;
            border-top: 1px dashed var(--border2);
        }

        #order_details_component .btn-add-product {
            background: none;
            border: 1px dashed var(--border2);
            color: var(--accent);
            border-radius: 7px;
            padding: 7px 16px;
            cursor: pointer;
            font-size: 13px;
            font-family: var(--sans);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }

        #order_details_component .btn-add-product:hover {
            background: #eef3fe;
            border-color: var(--accent);
        }

        /* TOTALS */
        #order_details_component .totals-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        #order_details_component .totals-right {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 16px;
        }

        #order_details_component .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 7px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            gap: 10px;
        }

        #order_details_component .total-row:last-child {
            border-bottom: none;
        }

        #order_details_component .total-row.grand {
            padding-top: 12px;
            margin-top: 4px;
            border-top: 2px solid var(--border2);
            border-bottom: none;
        }

        #order_details_component .total-row.grand .tl {
            font-size: 15px;
            font-weight: 700;
        }

        #order_details_component .total-row.grand .tv {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
            font-family: var(--mono);
        }

        #order_details_component .tl {
            color: var(--text2);
        }

        #order_details_component .tv {
            font-family: var(--mono);
            color: var(--text);
        }

        #order_details_component .tv.red {
            color: var(--red);
        }

        #order_details_component .tv.green {
            color: var(--green);
        }

        /* PAYMENT */
        #order_details_component .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        #order_details_component .pay-method {
            background: var(--surface3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.15s;
        }

        #order_details_component .pay-method.active {
            border-color: var(--accent);
            background: #eef3fe;
        }

        #order_details_component .pay-method-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 6px;
        }

        #order_details_component .pay-method input {
            font-family: var(--mono);
            font-size: 13px;
            padding: 6px 8px;
            background: var(--surface2);
            border-color: var(--border);
            pointer-events: none;
            opacity: 0.4;
        }

        #order_details_component .pay-method.active input {
            background: #fff;
            pointer-events: auto;
            opacity: 1;
        }

        #order_details_component .exchange-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        /* STATUS */
        #order_details_component .order-status-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        #order_details_component .status-pill {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--border);
            transition: all 0.15s;
            background: var(--surface3);
            color: var(--text2);
        }

        #order_details_component .status-pill.s-pending {
            color: var(--yellow);
            border-color: #e8d080;
            background: var(--yellow-bg);
        }

        #order_details_component .status-pill.s-accepted {
            color: var(--green);
            border-color: var(--green-border);
            background: var(--green-bg);
        }

        #order_details_component .status-pill.s-invoiced {
            color: var(--green);
            border-color: var(--green-border);
            background: var(--green-bg);
        }

        #order_details_component .status-pill.s-processing {
            color: var(--accent);
            border-color: #c0d0fa;
            background: #eef3fe;
        }

        #order_details_component .status-pill.s-delivered {
            color: var(--accent2);
            border-color: #cfc2fa;
            background: #f2eefe;
        }

        #order_details_component .status-pill.s-returned {
            color: var(--red);
            border-color: var(--red-border);
            background: var(--red-bg);
        }

        #order_details_component .delivery-methods {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        #order_details_component .delivery-option {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface3);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
        }

        #order_details_component .delivery-option.active {
            border-color: var(--accent);
            background: #eef3fe;
        }

        #order_details_component .delivery-option input[type=radio] {
            accent-color: var(--accent);
            width: auto;
            cursor: pointer;
        }

        #order_details_component .courier-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        #order_details_component .courier-opt {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--surface3);
            border: 1.5px solid var(--border);
            border-radius: 7px;
            padding: 7px 12px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.15s;
            user-select: none;
        }

        #order_details_component .courier-opt input[type=radio] {
            accent-color: var(--accent);
            width: auto;
            cursor: pointer;
        }

        #order_details_component .courier-opt.active {
            border-color: var(--accent);
            background: #eef3fe;
        }

        /* BUTTONS */
        #order_details_component .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: var(--sans);
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        #order_details_component .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        #order_details_component .btn-primary:hover {
            background: #2d5fe0;
        }

        #order_details_component .btn-success {
            background: var(--green);
            color: #fff;
        }

        #order_details_component .btn-success:hover {
            background: #138a4e;
        }

        #order_details_component .btn-outline {
            background: transparent;
            border: 1px solid var(--border2);
            color: var(--text2);
        }

        #order_details_component .btn-outline:hover {
            border-color: var(--text2);
            color: var(--text);
        }

        /* SUBMIT BAR */
        #order_details_component .submit-bar {
            /* position: fixed; */
            position: sticky;
            bottom: 0;
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            gap: 12px;
            box-shadow: 0 -2px 12px rgba(30, 40, 90, 0.08);
        }

        #order_details_component .submit-bar-totals {
            display: flex;
            gap: 24px;
        }

        #order_details_component .sbt-item {
            text-align: center;
        }

        #order_details_component .sbt-label {
            font-size: 10px;
            color: var(--text3);
            font-family: var(--mono);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        #order_details_component .sbt-val {
            font-family: var(--mono);
            font-size: 15px;
            font-weight: 700;
        }

        #order_details_component .sbt-val.grand {
            color: var(--accent);
            font-size: 18px;
        }

        #order_details_component .sbt-val.due {
            color: var(--red);
        }

        #order_details_component .sbt-val.paid {
            color: var(--green);
        }

        /* MODAL */
        #order_details_component .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(20, 30, 70, 0.45);
            backdrop-filter: blur(4px);
            z-index: 1001;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        #order_details_component .modal-overlay.open {
            display: flex;
        }

        #order_details_component .cmodal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
            animation: od-slideUp 0.2s ease;
        }

        @keyframes od-slideUp {
            from {
                transform: translateY(18px);
                opacity: 0
            }

            to {
                transform: translateY(0);
                opacity: 1
            }
        }

        #order_details_component .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface2);
        }

        #order_details_component .modal-title {
            font-size: 14px;
            font-weight: 700;
        }

        #order_details_component .modal-close {
            background: var(--surface3);
            border: 1px solid var(--border);
            color: var(--text2);
            border-radius: 6px;
            width: 28px;
            height: 28px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        #order_details_component .modal-close:hover {
            color: var(--text);
        }

        #order_details_component .modal-body {
            padding: 20px;
        }

        #order_details_component .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            background: var(--surface2);
        }

        /* WAREHOUSE */
        #order_details_component .wh-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        #order_details_component .wh-card {
            background: var(--surface3);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.15s;
            text-align: center;
        }

        #order_details_component .wh-card:hover {
            border-color: var(--accent);
            background: #eef3fe;
        }

        #order_details_component .wh-card.selected {
            border-color: var(--accent);
            background: #eef3fe;
        }

        #order_details_component .wh-card-name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        #order_details_component .wh-card-stock {
            font-size: 11px;
            color: var(--green);
            font-family: var(--mono);
        }

        #order_details_component .wh-card-stock.out {
            color: var(--red);
        }

        #order_details_component .wh-path {
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: var(--mono);
            font-size: 12px;
            color: var(--text2);
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 8px 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        #order_details_component .wh-path-sep {
            color: var(--text3);
        }

        #order_details_component .wh-path-node {
            color: var(--accent);
            font-weight: 600;
        }

        /* INVOICE */
        #order_details_component .invoice-modal {
            max-width: 700px;
        }

        #order_details_component .invoice-header-box {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            padding: 22px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        #order_details_component .invoice-co {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }

        #order_details_component .invoice-ord {
            font-family: var(--mono);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 3px;
        }

        #order_details_component .invoice-date {
            text-align: right;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.85);
            font-family: var(--mono);
        }

        #order_details_component .inv-section-title {
            font-family: var(--mono);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text2);
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        #order_details_component .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            min-width: 0;
        }

        #order_details_component .invoice-table th {
            background: var(--surface2);
            font-size: 10px;
            font-family: var(--mono);
            text-transform: uppercase;
            color: var(--text2);
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        #order_details_component .invoice-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        #order_details_component .invoice-total-box {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 14px;
        }

        #order_details_component .inv-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
        }

        #order_details_component .inv-row:last-child {
            border-bottom: none;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 700;
        }

        #order_details_component .inv-row:last-child .inv-v {
            color: var(--accent);
            font-family: var(--mono);
        }

        /* TOAST */
        #order_details_component .toast {
            position: fixed;
            bottom: 90px;
            right: 20px;
            background: var(--green);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            z-index: 9999;
            box-shadow: var(--shadow-md);
            animation: od-slideUp 0.2s ease;
        }

        /* UTILS */
        #order_details_component .mt8 {
            margin-top: 8px;
        }

        #order_details_component .mt14 {
            margin-top: 14px;
        }

        #order_details_component .flex {
            display: flex;
        }

        #order_details_component .gap8 {
            gap: 8px;
        }

        #order_details_component .font-mono {
            font-family: var(--mono);
        }

        #order_details_component .text-muted {
            color: var(--text2);
        }

        #order_details_component .text-xs {
            font-size: 11px;
        }

        #order_details_component .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 14px 0;
        }

        /* RESPONSIVE */
        @media (max-width:768px) {

            #order_details_component .grid-2,
            #order_details_component .grid-3,
            #order_details_component .totals-grid {
                grid-template-columns: 1fr;
            }

            #order_details_component .wh-grid {
                grid-template-columns: 1fr 1fr;
            }

            #order_details_component .submit-bar {
                flex-wrap: wrap;
                padding: 10px 14px;
            }

            #order_details_component .submit-bar-totals {
                flex-wrap: wrap;
                gap: 12px;
            }

            #order_details_component table {
                min-width: 650px;
            }
        }

        @media (max-width:480px) {
            #order_details_component .payment-methods {
                grid-template-columns: 1fr 1fr;
            }

            #order_details_component .exchange-grid {
                grid-template-columns: 1fr;
            }

            #order_details_component .status-pill {
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
@endsection

@section('page_title')
    Product Order
@endsection
@section('page_heading')
    Product Order
@endsection

@section('content')
    <div class="container" style="max-width: 1500px;overflow-y: scroll;max-height: calc(100vh - 160px);">
        <div id="order_details_component">

            <!-- TOPBAR -->
            <div class="topbar">
                <div class="topbar-brand">⬡ ORDER MGR</div>
                <div class="topbar-order">@{{ order.order_code }}</div>
                <div class="topbar-right">
                    <span class="badge" :class="'badge-' + orderStatus">@{{ orderStatus }}</span>
                    <span class="text-muted text-xs font-mono">@{{ order.sale_date }}</span>
                </div>
            </div>

            <div class="page">

                <!-- §1 CUSTOMER -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-num">1</div>
                        <div class="section-title">Customer Information</div>
                    </div>
                    <div class="section-body">
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Order Source</label>
                                <select v-model="customer.order_source">
                                    <option value="ecommerce">🌐 Website / Ecommerce</option>
                                    <option value="facebook">📘 Facebook</option>
                                    <option value="whatsapp">💬 WhatsApp</option>
                                    <option value="email">✉️ Email</option>
                                    <option value="phone">📞 Phone Call</option>
                                    <option value="instagram">📸 Instagram</option>
                                    <option value="walk-in">🏪 Walk-in</option>
                                    <option value="other">❓ Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Customer Name</label>
                                <input type="text" v-model="customer.name">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <div class="input-prefix">
                                    <span class="prefix">+880</span>
                                    <input type="text" v-model="customer.phone">
                                </div>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" v-model="customer.email" placeholder="customer@email.com">
                            </div>
                            <div class="form-group">
                                <label>District</label>
                                <select id="customerDistrictSelect" class="customer-location-select" v-model="customer.district_id" @change="onDistrictChange(); syncCourierAddressFromCustomer()">
                                    <option value="">— Select District —</option>
                                    <option v-for="d in districts" :key="d.id" :value="d.id">
                                        @{{ d.name }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Upazila</label>
                                <select id="customerUpazilaSelect" class="customer-location-select" v-model="customer.upazila_id" @change="syncCourierAddressFromCustomer" :disabled="upazilas.length === 0">
                                    <option value="">— Select Upazila —</option>
                                    <option v-for="u in upazilas" :key="u.id" :value="u.id">
                                        @{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Thana</label>
                                <input type="text" v-model="customer.thana" @input="syncCourierAddressFromCustomer" placeholder="Thana / Police Station">
                            </div>
                            <div class="form-group">
                                <label>Post Office</label>
                                <input type="text" v-model="customer.post_office" @input="syncCourierAddressFromCustomer" placeholder="Post office">
                            </div>
                            <div class="form-group">
                                <label>Full Address</label>
                                <input type="text" v-model="customer.address" @input="syncCourierAddressFromCustomer">
                            </div>
                        </div>
                        <hr class="divider">
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Paid Amount (৳)</label>
                                <input type="number" :value="totalPaid" readonly
                                    style="color:var(--green);font-family:var(--mono);font-weight:600">
                            </div>
                            <div class="form-group">
                                <label>Due Amount (৳)</label>
                                <input type="number" :value="totalDue" readonly
                                    style="color:var(--red);font-family:var(--mono);font-weight:600">
                            </div>
                            <div class="form-group">
                                <label>Order Note</label>
                                <textarea rows="2" v-model="order.note"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- §2 PRODUCTS -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-num">2</div>
                        <div class="section-title">Products</div>
                        <span class="text-muted text-xs font-mono" style="margin-left:auto">@{{ products.length }}
                            item@{{ products.length !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="section-body" style="padding-bottom:0;position:relative">
                        <div class="product-search-wrap">
                            <span class="search-icon">⌕</span>
                            <input type="text" v-model="productSearch" @input="onProductSearchInput"
                                placeholder="Search product by name or SKU to add..." autocomplete="off">
                        </div>
                        <!-- search results dropdown -->
                        <div v-if="showProductSearch && productSearchResults.length"
                            style="position:absolute;top:100%;left:18px;right:18px;background:#fff;border:1px solid var(--border);border-radius:8px;z-index:200;box-shadow:var(--shadow-md);max-height:260px;overflow-y:auto">
                            <div v-for="r in productSearchResults" :key="r.id"
                                style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid var(--border)"
                                @click="addFromSearch(r)" @mousedown.prevent>
                                <img :src="r.image || 'https://placehold.co/36x36/dde1ef/3b6ef0?text=?'"
                                    style="width:36px;height:36px;border-radius:5px;object-fit:cover;border:1px solid var(--border)">
                                <div style="flex:1;min-width:0">
                                    <div
                                        style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        @{{ r.name }}</div>
                                    <div style="font-size:11px;color:var(--text2);font-family:var(--mono)">
                                        ৳ @{{ r.price }} &nbsp;·&nbsp;
                                        Stock: @{{ r.stock }}
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div v-if="searchLoading" style="padding:10px 18px;font-size:12px;color:var(--text3)">Searching…
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:32px">#</th>
                                    <th>Product</th>
                                    <th>Unit Price</th>
                                    <th>Dis%</th>
                                    <th>Dis Amt</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(p, i) in products" :key="p.id">
                                    <td class="font-mono" style="color:var(--text3);font-size:12px">
                                        @{{ i + 1 }}
                                    </td>
                                    <td>
                                        <div class="product-cell">
                                            <img :src="p.img" class="product-img">
                                            <div class="product-info">
                                                <div class="product-name" @click="openWhModal(i)">
                                                    @{{ p.name }}
                                                </div>
                                                <div class="product-meta">
                                                    <span v-if="p.variant" class="meta-tag">
                                                        Var: @{{ p.variant }}
                                                    </span>
                                                    <span class="meta-tag">WH: @{{ p.warehouse || '—' }}</span>
                                                    <span class="meta-tag green">Stock: @{{ (p.variantInfo?.stock ? p.variantInfo.stock : p.stock) ?? 0 }}</span>
                                                </div>
                                                <input type="text" class="td-input sm" v-model="p.barcode"
                                                    placeholder="Barcode" style="width:130px;margin-top:4px">
                                                {{-- <div>
                                                    <pre>
                                                    @{{ JSON.stringify(p.variantInfo, null, 2) }}
                                                    </pre>
                                                </div> --}}
                                            </div>
                                        </div>
                                    </td>
                                    <td><input type="number" class="td-input" v-model.number="p.unit"
                                            @input="recalcProduct(i)"></td>
                                    <td><input type="number" class="td-input sm" :value="discPct(p)"
                                            @change="setDiscPct(i, $event.target.value)" step="0.1"></td>
                                    <td><input type="number" class="td-input sm" v-model.number="p.discount_amount"
                                            @input="recalcProduct(i)"></td>
                                    <td><input type="number" class="td-input qty" v-model.number="p.qty" min="1"
                                            @input="recalcProduct(i)"></td>
                                    <td class="font-mono" style="color:var(--green);font-weight:700">৳
                                        @{{ fmt(productTotal(p)) }}</td>
                                    <td><button class="del-btn" @click="removeProduct(i)">🗑</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="add-product-row">
                        <button class="btn-add-product" @click="addProduct">＋ Add Product</button>
                    </div>
                </div>

                <!-- §3 ORDER SUMMARY -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-num">3</div>
                        <div class="section-title">Order Summary</div>
                    </div>
                    <div class="section-body">
                        <div class="totals-grid">
                            <div>
                                <div class="grid-2">
                                    <div class="form-group">
                                        <label>Discount Type</label>
                                        <select v-model="summary.discountType">
                                            <option value="">None</option>
                                            <option value="percent">Percent (%)</option>
                                            <option value="fixed">Fixed (৳)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Discount Value</label>
                                        <input type="number" v-model.number="summary.discountValue" min="0">
                                    </div>
                                </div>
                                <div class="grid-2">
                                    <div class="form-group">
                                        <label>Coupon Code</label>
                                        <div class="flex gap8">
                                            <input type="text" v-model="summary.couponCode"
                                                placeholder="Enter coupon..." style="flex:1"
                                                :disabled="loading.coupon"
                                                @keydown.enter.prevent="applyCoupon">
                                            <button type="button" class="btn btn-outline"
                                                style="padding:8px 12px;font-size:12px;white-space:nowrap"
                                                :disabled="loading.coupon"
                                                @click="applyCoupon">Apply</button>
                                            <button type="button" v-if="summary.couponDiscount > 0" class="btn btn-outline" style="padding:8px 12px;font-size:12px;white-space:nowrap;color:var(--red);"
                                                @click="removeCoupon">Remove</button>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Extra Charge (৳)</label>
                                        <input type="number" v-model.number="summary.extraCharge" min="0">
                                    </div>
                                </div>
                                <div class="grid-2">
                                    <div class="form-group">
                                        <label>Delivery Charge (৳)</label>
                                        <input type="number" v-model.number="summary.deliveryCharge" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Round Off (৳)</label>
                                        <input type="number" v-model.number="summary.roundOff">
                                    </div>
                                </div>
                            </div>
                            <div class="totals-right">
                                <div class="total-row"><span class="tl">Subtotal</span><span class="tv">৳
                                        @{{ fmt(subtotal) }}</span></div>
                                <div class="total-row"><span class="tl">Discount</span><span class="tv red">— ৳
                                        @{{ fmt(discountAmount) }}</span></div>
                                <div class="total-row"><span class="tl">Coupon</span><span class="tv red">— ৳
                                        @{{ fmt(summary.couponDiscount) }}</span></div>
                                <div class="total-row"><span class="tl">Extra Charge</span><span class="tv">+ ৳
                                        @{{ fmt(summary.extraCharge) }}</span></div>
                                <div class="total-row"><span class="tl">Delivery</span><span class="tv">+ ৳
                                        @{{ fmt(summary.deliveryCharge) }}</span></div>
                                <div class="total-row"><span class="tl">Round Off</span><span class="tv">- ৳
                                        @{{ fmt(summary.roundOff) }}</span></div>
                                <div class="total-row grand"><span class="tl">Grand Total</span><span
                                        class="tv">৳ @{{ fmt(grandTotal) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- §4 PAYMENT -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-num">4</div>
                        <div class="section-title">Payment Collection</div>
                    </div>
                    <div class="section-body">
                        <div class="payment-methods">
                            <div v-for="m in payMethods" :key="m.key" class="pay-method"
                                :class="{ active: m.active }" @click="togglePayMethod(m)">
                                <div class="pay-method-name">@{{ m.label }}</div>
                                <input type="number" v-model.number="m.amount" min="0"
                                    :style="{ pointerEvents: m.active ? 'auto' : 'none', opacity: m.active ? 1 : 0.4 }"
                                    @click.stop>
                            </div>
                        </div>
                        <hr class="divider">
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Total Paid (৳)</label>
                                <input type="number" :value="totalPaid" readonly
                                    style="color:var(--green);font-family:var(--mono);font-size:16px;font-weight:700">
                            </div>
                            <div class="form-group">
                                <label>Total Due (৳)</label>
                                <input type="number" :value="totalDue" readonly
                                    style="color:var(--red);font-family:var(--mono);font-size:16px;font-weight:700">
                            </div>
                        </div>
                        <div class="exchange-grid">
                            <div class="form-group">
                                <label>💵 Customer Gave (৳)</label>
                                <input type="number" v-model.number="payment.customerGave" min="0"
                                    style="font-family:var(--mono)">
                            </div>
                            <div class="form-group">
                                <label>🔄 Change / Exchange (৳)</label>
                                <input type="number" :value="exchangeAmount" readonly
                                    style="font-family:var(--mono);color:var(--orange);font-weight:600">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- §5 STATUS & DELIVERY -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-num">5</div>
                        <div class="section-title">Status &amp; Delivery</div>
                    </div>
                    <div class="section-body">
                        <label>Order Status</label>
                        <div class="order-status-pills mt8">
                            <div v-for="s in statusOptions" :key="s.key" class="status-pill"
                                :class="{
                                    ['s-' + s.key]: orderStatus === s.key
                                }"
                                @click="orderStatus = s.key">
                                @{{ s.icon }} @{{ s.label }}</div>
                        </div>


                        <hr class="divider">
                        <label>Sales Reference</label>
                        <div class="grid-2 mt8">
                            <div class="form-group">
                                <label>Salesman / SR</label>
                                <select v-model="salesReference.salesman_id">
                                    <option value="">Current User / Default</option>
                                    <option v-for="user in salesUsers" :key="user.id" :value="String(user.id)">
                                        @{{ user.name }}@{{ user.phone ? ' — ' + user.phone : '' }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Affiliate Code / Reference</label>
                                <input type="text" v-model.trim="salesReference.affiliate_code" list="ecom-affiliate-codes" placeholder="Example: RAHIM10">
                                <datalist id="ecom-affiliate-codes">
                                    <option v-for="affiliate in affiliates" :key="affiliate.id" :value="affiliate.code">@{{ affiliate.name }}</option>
                                </datalist>
                            </div>
                        </div>

                        <hr class="divider">
                        <label>Delivery Method</label>
                        <div class="delivery-methods mt8">
                            <label class="delivery-option" :class="{ active: delivery.method === 'home' }">
                                <input type="radio" name="delivery" value="home" v-model="delivery.method"> 🏠 Home
                                Delivery
                            </label>
                            <label class="delivery-option" :class="{ active: delivery.method === 'store' }">
                                <input type="radio" name="delivery" value="store" v-model="delivery.method"> 🏪 Store
                                Pickup
                            </label>
                        </div>

                        <div v-if="delivery.method === 'store'" class="form-group">
                            <label>Select Outlet</label>
                            <select v-model="delivery.outlet">
                                <option value="">Select Outlet</option>
                                <option v-for="outlet in outlets" :key="outlet.id" :value="String(outlet.id)">
                                    @{{ outlet.title }}@{{ outlet.address ? ' — ' + outlet.address : '' }}
                                </option>
                            </select>
                        </div>
                        
                        <div v-if="delivery.method === 'home'">
                            <div class="grid-3">
                                <div class="form-group">
                                    <label>Expected Delivery Date</label>
                                    <input type="date" v-model="delivery.expectedDate">
                                </div>
                                <div class="form-group" style="grid-column:span 2">
                                    <label>Courier Method</label>
                                    <div class="courier-options">
                                        <label  class="courier-opt">
                                            <input type="radio" name="courier" value="none"
                                                v-model="delivery.courier"> None
                                        </label>
                                        @foreach (App\Models\ProductOrderCourierMethod::get() as $courier)
                                        <label  class="courier-opt">
                                            <input type="radio" name="courier" value="{{$courier->title}}"
                                                v-model="delivery.courier"> {{$courier->title}}
                                        </label>
                                        @endforeach
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Courier Delivery Address</label>
                                <textarea rows="2" v-model="delivery.courierAddress" @input="onCourierAddressInput"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Note for Courier</label>
                                <textarea rows="2" v-model="delivery.courierNote" placeholder="Fragile, call before delivery..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- /page -->

            <!-- SUBMIT BAR -->
            <div class="submit-bar">
                <div class="submit-bar-totals">
                    <div class="sbt-item">
                        <div class="sbt-label">Grand Total</div>
                        <div class="sbt-val grand">৳ @{{ fmt(grandTotal) }}</div>
                    </div>
                    <div class="sbt-item">
                        <div class="sbt-label">Paid</div>
                        <div class="sbt-val paid">৳ @{{ fmt(totalPaid) }}</div>
                    </div>
                    <div class="sbt-item">
                        <div class="sbt-label">Due</div>
                        <div class="sbt-val due">৳ @{{ fmt(totalDue) }}</div>
                    </div>
                </div>
                <div class="flex gap8">
                    <button class="btn btn-outline" @click="resetForm">🔄 Reset</button>
                    <a class="btn btn-outline" :href="printUrl" target="_blank">🖨️ Print</a>
                    <button class="btn btn-success" @click="whModal.invoiceOpen = true">📋 Preview &amp; Submit</button>
                </div>
            </div>

            <!-- WAREHOUSE MODAL -->
            <div class="modal-overlay" :class="{ open: whModal.open }">
                <div class="cmodal" style="max-width:680px">
                    <div class="modal-header">
                        <div class="modal-title">📦 Assign Warehouse &mdash; <span
                                style="color:var(--accent)">@{{ whModal.productName }}</span></div>
                        <button class="modal-close" @click="closeWhModal">✕</button>
                    </div>

                    <div class="modal-body" style="padding:0">

                        {{-- Product header card --}}
                        <div
                            style="display:flex;align-items:center;gap:14px;padding:16px 20px;background:var(--surface2);border-bottom:1px solid var(--border)">
                            <img :src="whModal.productImg || 'https://placehold.co/64x64/dde1ef/3b6ef0?text=?'"
                                style="width:64px;height:64px;border-radius:8px;object-fit:cover;border:1px solid var(--border);flex-shrink:0">
                            <div style="flex:1;min-width:0">
                                <div
                                    style="font-size:14px;font-weight:700;color:var(--text);line-height:1.4;margin-bottom:4px">
                                    @{{ whModal.productFullName }}</div>
                                <div style="font-size:11px;color:var(--text2);font-family:var(--mono)">
                                    Total stock: <strong style="color:var(--green)">@{{ whModal.totalStock }}</strong> units
                                </div>
                            </div>
                        </div>

                        <div style="padding:20px">

                            {{-- ── STEP 1: VARIANT SELECTION ── --}}
                            <div v-if="whModal.variantGroups && whModal.variantGroups.length" style="margin-bottom:4px">
                                <div
                                    style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;font-family:var(--mono);margin-bottom:10px">
                                    Step 1 — Select Variant
                                </div>

                                <div
                                    style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin-bottom:12px">
                                    <div v-for="vg in whModal.variantGroups" :key="vg.key"
                                        style="margin-bottom:0">
                                        <label
                                            style="display:block;font-size:11px;font-weight:600;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;font-family:var(--mono)">
                                            @{{ vg.label }}
                                        </label>
                                        <select v-model="whModal.selectedVariants[vg.key]" @change="onVariantChange"
                                            style="width:100%;font-family:var(--mono);font-size:13px;background:var(--surface);border:1px solid var(--border);border-radius:7px;color:var(--text);padding:8px 11px;outline:none">
                                            <option value="">— Select —</option>
                                            <option v-for="opt in vg.options" :key="opt"
                                                :value="opt">@{{ opt }}</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Combination result badge --}}
                                <div v-if="whModal.resolvedCombination"
                                    style="padding:10px 14px;border-radius:8px;display:flex;align-items:center;gap:10px;margin-bottom:6px"
                                    :style="whModal.resolvedCombination.stock > 0 ?
                                        'background:var(--green-bg);border:1px solid var(--green-border)' :
                                        'background:var(--red-bg);border:1px solid var(--red-border)'">
                                    <div style="flex:1">
                                        <div style="font-size:12px;font-weight:700;font-family:var(--mono)"
                                            :style="whModal.resolvedCombination.stock > 0 ? 'color:var(--green)' :
                                                'color:var(--red)'">
                                            @{{ whModal.resolvedCombination.display }}
                                        </div>
                                        <div style="font-size:11px;margin-top:2px"
                                            :style="whModal.resolvedCombination.stock > 0 ? 'color:var(--green)' :
                                                'color:var(--red)'">
                                            @{{ whModal.resolvedCombination.stock > 0 ?
                                                whModal.resolvedCombination.stock + ' units available' :
                                                'Out of stock — choose another variant' }}
                                        </div>
                                    </div>
                                    <div style="font-size:26px;font-weight:800;font-family:var(--mono)"
                                        :style="whModal.resolvedCombination.stock > 0 ? 'color:var(--green)' :
                                            'color:var(--red)'">
                                        @{{ whModal.resolvedCombination.stock }}
                                    </div>
                                </div>

                                <div v-if="whModal.variantError"
                                    style="font-size:12px;color:var(--red);font-weight:600;margin-bottom:4px">
                                    ⚠️ @{{ whModal.variantError }}
                                </div>
                            </div>

                            {{-- Separator between variant and warehouse sections --}}
                            <hr style="border:none;border-top:1px solid var(--border);margin:16px 0"
                                v-if="whModal.variantGroups && whModal.variantGroups.length">

                            {{-- ── WAREHOUSE SELECTION — only visible when variant is ok or no variants ── --}}
                            <div
                                v-if="!whModal.variantGroups.length || (whModal.resolvedCombination && whModal.resolvedCombination.stock > 0)">

                                <div
                                    style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;font-family:var(--mono);margin-bottom:10px">
                                    Step @{{ whModal.variantGroups.length ? '2' : '1' }} — Select Warehouse
                                </div>

                                <div class="wh-grid">
                                    <div v-for="w in whModal.availableWarehouses" :key="w.id" class="wh-card"
                                        :class="{ selected: whModal.warehouseId === w.id }" @click="selectWarehouse(w)">
                                        <div class="wh-card-name">🏭 @{{ w.name }}</div>
                                        <div class="wh-card-stock" :class="{ out: w.stock === 0 }">@{{ w.stock }}
                                            units</div>
                                    </div>
                                </div>

                                {{-- ── ROOMS ── --}}
                                <div v-if="whModal.warehouseId" class="mt8">
                                    <div
                                        style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;font-family:var(--mono);margin:14px 0 8px">
                                        Step @{{ whModal.variantGroups.length ? '3' : '2' }} — Select Room
                                    </div>
                                    <div class="grid-2">
                                        <div v-for="r in whModal.availableRooms" :key="r.id" class="wh-card"
                                            :class="{ selected: whModal.roomId === r.id }" @click="selectRoom(r)">
                                            <div class="wh-card-name">🚪 @{{ r.name }}</div>
                                            <div class="wh-card-stock" :class="{ out: r.stock === 0 }">
                                                @{{ r.stock }} units</div>
                                        </div>
                                    </div>
                                    <div v-if="whModal.availableRooms.length === 0"
                                        style="font-size:12px;color:var(--text3);padding:8px 0">
                                        No rooms found for this warehouse.
                                    </div>
                                </div>

                                {{-- ── CARTONS ── --}}
                                <div v-if="whModal.roomId" class="mt8">
                                    <div
                                        style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;font-family:var(--mono);margin:14px 0 8px">
                                        Step @{{ whModal.variantGroups.length ? '4' : '3' }} — Select Carton
                                    </div>
                                    <div class="grid-2">
                                        <div v-for="c in whModal.availableCartons" :key="c.id" class="wh-card"
                                            :class="{ selected: whModal.cartonId === c.id }" @click="selectCarton(c)">
                                            <div class="wh-card-name">📦 @{{ c.name }}</div>
                                            <div class="wh-card-stock" :class="{ out: c.stock === 0 }">
                                                @{{ c.stock }} units</div>
                                        </div>
                                    </div>
                                    <div v-if="whModal.availableCartons.length === 0"
                                        style="font-size:12px;color:var(--text3);padding:8px 0">
                                        No cartons found for this room.
                                    </div>
                                </div>

                                {{-- ── SEPARATOR + STOCK DRILL-DOWN SUMMARY ── --}}
                                <hr style="border:none;border-top:1px solid var(--border);margin:18px 0">

                                <div
                                    style="font-size:11px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;font-family:var(--mono);margin-bottom:10px">
                                    Stock Summary
                                </div>

                                <div style="display:flex;flex-direction:column;gap:6px">

                                    {{-- Total --}}
                                    <div
                                        style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:7px">
                                        <span style="font-size:13px;color:var(--text2)">📦 All Stock</span>
                                        <strong
                                            style="font-family:var(--mono);font-size:14px;color:var(--text)">@{{ whModal.totalStock }}
                                            units</strong>
                                    </div>

                                    {{-- After Warehouse --}}
                                    <div v-if="whModal.warehouseId"
                                        style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:7px">
                                        <span style="font-size:13px;color:var(--text2)">
                                            🏭 In <strong style="color:var(--text)">@{{ whModal.warehouseName }}</strong>
                                        </span>
                                        <strong
                                            style="font-family:var(--mono);font-size:14px;color:var(--accent)">@{{ whModal.stockAtWarehouse }}
                                            units</strong>
                                    </div>

                                    {{-- After Room --}}
                                    <div v-if="whModal.roomId"
                                        style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:7px">
                                        <span style="font-size:13px;color:var(--text2)">
                                            🚪 In <strong style="color:var(--text)">@{{ whModal.roomName }}</strong>
                                        </span>
                                        <strong
                                            style="font-family:var(--mono);font-size:14px;color:var(--accent)">@{{ whModal.stockAtRoom }}
                                            units</strong>
                                    </div>

                                    {{-- After Carton --}}
                                    <div v-if="whModal.cartonId"
                                        style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:var(--green-bg);border:1px solid var(--green-border);border-radius:7px">
                                        <span style="font-size:13px;color:var(--text2)">
                                            📦 In <strong style="color:var(--text)">@{{ whModal.cartonName }}</strong>
                                        </span>
                                        <strong
                                            style="font-family:var(--mono);font-size:14px;color:var(--green)">@{{ whModal.stockAtCarton }}
                                            units</strong>
                                    </div>

                                </div>

                                {{-- Location path breadcrumb --}}
                                <div v-if="whModal.warehouseId" class="wh-path" style="margin-top:12px">
                                    <span>Path:</span>
                                    <span class="wh-path-sep">›</span>
                                    <span class="wh-path-node">@{{ whModal.warehouseName }}</span>
                                    <template v-if="whModal.roomId">
                                        <span class="wh-path-sep">›</span>
                                        <span class="wh-path-node">@{{ whModal.roomName }}</span>
                                    </template>
                                    <template v-if="whModal.cartonId">
                                        <span class="wh-path-sep">›</span>
                                        <span class="wh-path-node">@{{ whModal.cartonName }}</span>
                                    </template>
                                </div>

                            </div>{{-- /warehouse section --}}

                        </div>{{-- /inner padding --}}
                    </div>{{-- /modal-body --}}

                    <div class="modal-footer">
                        <button class="btn btn-outline" @click="closeWhModal">Cancel</button>
                        <button class="btn btn-primary" @click="confirmWarehouseAssign">✓ Confirm Assignment</button>
                    </div>
                </div>
            </div>
            {{-- END WAREHOUSE MODAL --}}

            <!-- INVOICE MODAL -->
            <div class="modal-overlay" :class="{ open: whModal.invoiceOpen }">
                <div class="cmodal invoice-modal">
                    <div class="modal-header">
                        <div class="modal-title">📋 Invoice Preview</div>
                        <button class="modal-close" @click="whModal.invoiceOpen = false">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="invoice-header-box">
                            <div>
                                <div class="invoice-co">⬡ ShopManager</div>
                                <div class="invoice-ord">@{{ order.order_code }}</div>
                            </div>
                            <div class="invoice-date">
                                <div>@{{ today }}</div>
                                <div style="margin-top:6px"><span class="badge"
                                        :class="'badge-' + orderStatus">@{{ orderStatus }}</span></div>
                            </div>
                        </div>
                        <div class="grid-2" style="margin-bottom:16px;gap:12px">
                            <div>
                                <div class="inv-section-title">Bill To</div>
                                <div style="font-weight:600">@{{ customer.name }}</div>
                                <div class="text-muted">@{{ customer.phone }}</div>
                                <div class="text-muted">@{{ customer.address }}, @{{ selectedUpazila }},
                                    @{{ selectedDistrict }}</div>
                            </div>
                            <div>
                                <div class="inv-section-title">Delivery</div>
                                <div class="text-muted">Expected: @{{ delivery.expectedDate }}</div>
                                <div class="text-muted">Method:
                                    @{{ delivery.method === 'home' ? 'Home Delivery' : 'Store Pickup' }}</div>
                                <div class="text-muted">Courier:
                                    @{{ delivery.courier === 'none' ? 'None' : delivery.courier }}</div>
                            </div>
                        </div>
                        <div class="inv-section-title">Items</div>
                        <div style="overflow-x:auto;margin-bottom:16px">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Unit</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, i) in products" :key="p.id">
                                        <td>@{{ i + 1 }}</td>
                                        <td style="font-size:12px">@{{ p.name }}</td>
                                        <td class="font-mono">৳ @{{ fmt(p.unit) }}</td>
                                        <td class="font-mono">
                                            <span v-if="p.discount_amount > 0">
                                                ৳@{{ fmt(p.unit - p.discount_amount) }}
                                            </span>
                                            <span v-else>
                                                ৳@{{ fmt(p.unit) }}
                                            </span>
                                        </td>
                                        <td style="text-align:center">@{{ p.qty }}</td>
                                        <td class="font-mono" style="color:var(--green);font-weight:600">৳
                                            @{{ fmt(productTotal(p)) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="invoice-total-box">
                            <div class="inv-row">
                                <span>Subtotal</span>
                                <span class="inv-v font-mono">৳ @{{ fmt(subtotal) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Delivery</span>
                                <span class="inv-v font-mono">+৳ @{{ fmt(summary.deliveryCharge) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Discount</span>
                                <span class="inv-v font-mono">-৳ @{{ fmt(summary.discountValue) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Coupon</span>
                                <span class="inv-v font-mono">-৳ @{{ fmt(summary.couponDiscount) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Extra Charge</span>
                                <span class="inv-v font-mono">+৳ @{{ fmt(summary.extraCharge) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Round Off</span>
                                <span class="inv-v font-mono">-৳ @{{ fmt(summary.roundOff) }}</span>
                            </div>
                            <div class="inv-row">
                                <span>Grand Total</span><span class="inv-v">৳ @{{ fmt(grandTotal) }}</span>
                            </div>
                        </div>
                        <div class="grid-2" style="gap:12px">
                            <div
                                style="background:var(--green-bg);border:1px solid var(--green-border);border-radius:8px;padding:14px;text-align:center">
                                <div class="text-xs text-muted font-mono">PAID</div>
                                <div style="font-size:22px;font-weight:700;color:var(--green);font-family:var(--mono)">৳
                                    @{{ fmt(totalPaid) }}</div>
                            </div>
                            <div
                                style="background:var(--red-bg);border:1px solid var(--red-border);border-radius:8px;padding:14px;text-align:center">
                                <div class="text-xs text-muted font-mono">DUE</div>
                                <div style="font-size:22px;font-weight:700;color:var(--red);font-family:var(--mono)">৳
                                    @{{ fmt(totalDue) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" @click="whModal.invoiceOpen = false">← Back &amp; Edit</button>
                        <a class="btn btn-primary" :href="printUrl" target="_blank">🖨️ Print</a>
                        <button class="btn btn-success" @click="confirmOrder" :disabled="saving">
                            <span v-if="!saving">✅ Confirm &amp; Save</span>
                            <span v-else>⏳ Saving…</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TOAST -->
            <div v-if="showToast" class="toast" :style="toastError ? 'background:var(--red)' : ''">
                @{{ toastMessage }}</div>

        </div><!-- /#order_details_component -->
    </div>
@endsection

@push('js')
    <script src="{{ versioned_url('assets/plugins/select2/select2.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        /* ─── Bootstrap ────────────────────────────────────────────────────── */
        var order_id = @json($id);
        var _cfg = window.ECOM_ORDER_CONFIG || {};
        var _routes = _cfg.routes || {};
        var file_url = _cfg.file_url || '';

        /* Simple debounce helper */
        function _debounce(fn, ms) {
            var t;
            return function() {
                var args = arguments,
                    ctx = this;
                clearTimeout(t);
                t = setTimeout(function() {
                    fn.apply(ctx, args);
                }, ms);
            };
        }

        new Vue({
            el: '#order_details_component',

            /* ═══════════════════════════════════════════════════════════════
               DATA
            ═══════════════════════════════════════════════════════════════ */
            data: function() {
                return {
                    // ── ORDER META ──
                    order: {
                        order_code: '',
                        sale_date: '',
                        note: ''
                    },

                    // ── CUSTOMER ──
                    customer: {
                        order_source: 'ecommerce',
                        name: '',
                        phone: '',
                        email: '',
                        district_id: '',
                        upazila_id: '',
                        thana: '',
                        post_office: '',
                        address: '',
                        note: ''
                    },

                    // ── GEO ──
                    districts: [],
                    upazilas: [],

                    // ── PRODUCTS ──
                    productSearch: '',
                    productSearchResults: [],
                    showProductSearch: false,
                    searchLoading: false,
                    products: [],

                    // ── SUMMARY ──
                    summary: {
                        discountType: '',
                        discountValue: 0,
                        couponCode: '',
                        couponDiscount: 0,
                        extraCharge: 0,
                        deliveryCharge: 0,
                        roundOff: 0
                    },

                    // ── PAYMENT ──
                    payment: {
                        customerGave: 0
                    },
                    payMethods: [{
                            key: 'cash',
                            label: '💵 Cash',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'bkash',
                            label: '🩷 bKash',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'rocket',
                            label: '🟣 Rocket',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'nogod',
                            label: '🟠 Nagad',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'bank',
                            label: '🏦 Bank Transfer',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'cheque',
                            label: '📄 Cheque',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'credit',
                            label: '💳 Credit',
                            active: false,
                            amount: 0
                        },
                        {
                            key: 'gateway',
                            label: '🌐 Gateway',
                            active: false,
                            amount: 0
                        }
                    ],

                    // ── STATUS ──
                    orderStatus: 'pending',
                    salesUsers: _cfg.sales_users || [],
                    affiliates: _cfg.affiliates || [],
                    salesReference: {
                        salesman_id: '',
                        affiliate_code: ''
                    },
                    statusOptions: [{
                            key: 'pending',
                            icon: '⏳',
                            label: 'Pending'
                        },
                        {
                            key: 'invoiced',
                            icon: '✅',
                            label: 'Accepted'
                        },
                        // {
                        //     key: 'processing',
                        //     icon: '⚙️',
                        //     label: 'Processing'
                        // },
                        {
                            key: 'delivered',
                            icon: '🚀',
                            label: 'Delivered'
                        },
                        {
                            key: 'returned',
                            icon: '↩️',
                            label: 'Returned'
                        }
                    ],

                    // ── DELIVERY ──
                    delivery: {
                        method: 'home',
                        outlet: '',
                        expectedDate: '',
                        courier: 'none',
                        courierAddress: '',
                        courierNote: ''
                    },
                    courierAddressManuallyEdited: false,
                    courierOptions: [{
                            key: 'none',
                            label: '🚫 None'
                        },
                        {
                            key: 'pathao',
                            label: '🛵 Pathao'
                        },
                        {
                            key: 'steadfast',
                            label: '📦 Steadfast'
                        },
                    ],

                    // ── WAREHOUSE MODAL ──
                    whModal: {
                        open: false,
                        invoiceOpen: false,

                        // product context
                        productIdx: null,
                        productName: '',
                        productFullName: '',
                        productImg: '',
                        variantType: 'simple', // 'simple' | 'combination'

                        // variant state
                        variantGroups: [], // [{ key, label, options[] }]
                        selectedVariants: {}, // { color: '', size: '', ... }
                        allVariantsSelected: false,
                        resolvedCombination: null, // exact match in combinations[]
                        selectedVariantDisplay: '', // human-readable label built from selected variants
                        availableCombosWithStock: [], // combos where stock > 0 (for info table)

                        // warehouse/room/carton UI state
                        showWarehouseBlock: false,
                        warehouseId: null,
                        warehouseName: '',
                        roomId: null,
                        roomName: '',
                        cartonId: null,
                        cartonName: '',

                        // stock drill-down display
                        totalStock: 0,
                        stockAtWarehouse: 0,
                        stockAtRoom: 0,
                        stockAtCarton: 0,

                        // filtered lists shown in UI
                        availableWarehouses: [],
                        availableRooms: [],
                        availableCartons: [],

                        // raw combination data from product
                        combinations: [],
                        simpleWarehouseStocks: [],
                        simpleRoomStocks: [],
                        simpleCartonStocks: [],
                    },

                    // global warehouses (used for simple products)
                    warehouses: [],
                    outlets: [],

                    // ── UI STATE ──
                    saving: false,
                    showToast: false,
                    toastMessage: '✅ Order saved successfully!',
                    toastError: false,
                    loading: { coupon: false },
                    coupon: { type: '', value: 0, percent: 0 },
                    printUrl: '',
                };
            },

            /* ═══════════════════════════════════════════════════════════════
               COMPUTED
            ═══════════════════════════════════════════════════════════════ */
            computed: {
                productTotal: function() {
                    return function(p) {
                        return Math.max(0, (p.unit - (p.discount_amount || 0))) * (p.qty || 1);
                    };
                },
                discPct: function() {
                    return function(p) {
                        if (!p.unit || !p.discount_amount) return 0;
                        return parseFloat(((p.discount_amount / p.unit) * 100).toFixed(1));
                    };
                },
                subtotal: function() {
                    return this.products.reduce(function(s, p) {
                        return s + Math.max(0, (p.unit - (p.discount_amount || 0))) * (p.qty || 1);
                    }, 0);
                },
                discountAmount: function() {
                    var v = this.summary.discountValue || 0;
                    if (this.summary.discountType === 'percent') return this.subtotal * (v / 100);
                    if (this.summary.discountType === 'fixed') return v;
                    return 0;
                },
                grandTotal: function() {
                    return this.subtotal -
                        this.discountAmount -
                        (this.summary.couponDiscount || 0) +
                        (this.summary.extraCharge || 0) +
                        (this.summary.deliveryCharge || 0) -
                        (this.summary.roundOff || 0);
                },
                totalPaid: function() {
                    return this.payMethods.reduce(function(s, m) {
                        return s + (m.active ? (m.amount || 0) : 0);
                    }, 0);
                },
                totalDue: function() {
                    return Math.max(0, this.grandTotal - this.totalPaid);
                },
                exchangeAmount: function() {
                    return Math.max(0, (this.payment.customerGave || 0) - this.totalPaid);
                },
                today: function() {
                    return new Date().toLocaleDateString('en-BD', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                },
                selectedDistrict: function() {
                    if (!this.customer.district_id) return '';
                    var d = this.districts.find(function(x) {
                        return x.id == this.customer.district_id;
                    }.bind(this));
                    return d ? d.name : '';
                },
                selectedUpazila: function() {
                    if (!this.customer.upazila_id) return '';
                    var u = this.upazilas.find(function(x) {
                        return x.id == this.customer.upazila_id;
                    }.bind(this));
                    return u ? u.name : '';
                }
            },

            /* ═══════════════════════════════════════════════════════════════
               MOUNTED
            ═══════════════════════════════════════════════════════════════ */
            mounted: function() {
                this.fetchOrder(order_id);
                this.loadDistricts();
                this.loadPaymentMethods();
                this.loadWarehouses();
                this.loadOutlets();
                this.refreshLocationSelect2();
            },

            watch: {
                districts: function() {
                    this.refreshLocationSelect2();
                },
                upazilas: function() {
                    this.refreshLocationSelect2();
                },
                'customer.district_id': function() {
                    this.refreshLocationSelect2();
                },
                'customer.upazila_id': function() {
                    this.refreshLocationSelect2();
                },
            },

            /* ═══════════════════════════════════════════════════════════════
               METHODS
            ═══════════════════════════════════════════════════════════════ */
            methods: {

                /* ── SELECT2 LOCATION FIELDS ──────────────────────────────── */
                refreshLocationSelect2: function() {
                    if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;

                    var self = this;
                    this.$nextTick(function() {
                        var $root = jQuery('#order_details_component');

                        function setupSelect2(selector, placeholder, onChange) {
                            var $select = $root.find(selector);
                            if (!$select.length) return;

                            if (!$select.data('select2')) {
                                $select.select2({
                                    width: '100%',
                                    placeholder: placeholder,
                                    allowClear: true
                                });
                            }

                            $select.off('change.locationSelect2').on('change.locationSelect2', onChange);
                        }

                        setupSelect2('#customerDistrictSelect', 'Select District', function() {
                            self.customer.district_id = jQuery(this).val() || '';
                            self.onDistrictChange();
                            self.syncCourierAddressFromCustomer();
                        });

                        setupSelect2('#customerUpazilaSelect', 'Select Upazila', function() {
                            self.customer.upazila_id = jQuery(this).val() || '';
                            self.syncCourierAddressFromCustomer();
                        });

                        $root.find('#customerDistrictSelect')
                            .val(self.customer.district_id || '')
                            .trigger('change.select2');
                        $root.find('#customerUpazilaSelect')
                            .prop('disabled', self.upazilas.length === 0)
                            .val(self.customer.upazila_id || '')
                            .trigger('change.select2');
                    });
                },

                /* ── FORMAT ──────────────────────────────────────────────── */
                fmt: function(n) {
                    return Math.round(n || 0).toLocaleString('en-IN');
                },

                /* ── LOAD ORDER ──────────────────────────────────────────── */
                fetchOrder: function(id) {
                    var self = this;
                    var url = (_routes.orderInfo || '/ecommerce/order-info/__ID__').replace('__ID__', id);

                    axios.get(url).then(function(res) {
                        var d = res.data;

                        self.order.order_code = d.order_code || '';
                        self.order.sale_date = d.sale_date || '';
                        self.order.note = d.order_note || '';
                        self.orderStatus = d.order_status || 'pending';
                        self.salesReference.salesman_id = d.salesman_id ? String(d.salesman_id) : '';
                        self.salesReference.affiliate_code = d.affiliate_code || '';

                        var addr = d.address || {};
                        self.customer.order_source = d.order_source || 'ecommerce';
                        self.customer.name = addr.name || d.customer_name || '';
                        self.customer.phone = addr.phone || d.customer_phone || '';
                        self.customer.email = addr.email || '';
                        self.customer.district_id = addr.district_id || '';
                        self.customer.upazila_id = addr.upozilla_id || '';
                        self.customer.thana = addr.thana || '';
                        self.customer.post_office = addr.post_office || '';
                        self.customer.address = addr.address || '';

                        if (self.customer.district_id) {
                            self.loadUpazilas(self.customer.district_id);
                        }

                        self.products = (d.products || []).map(function(p) {
                            return {
                                id: p.id,
                                product_id: p.product_id,
                                name: p.product_name || '',
                                img: p.product_image ? (file_url + '/' + p.product_image) : '',
                                product_image: p.product_image || null,
                                unit: parseFloat(p.product_price) || 0,
                                discount_amount: parseFloat(p.discount_amount) || 0,
                                qty: parseInt(p.qty) || 1,
                                variant: p.variant || null,
                                combination_id: p.combination_id || null,
                                warehouse: p.warehouse_name || '',
                                barcode: '',
                                variantInfo: p.variant_info || {},
                                selected_variant_info: p.selected_variant_info || null,
                                stock: p.stock || 0,
                                warehouse_id: p.warehouse_id || null,
                                warehouse_room_id: p.warehouse_room_id || null,
                                warehouse_carton_id: p.warehouse_carton_id || null,
                                warehouse_name: p.warehouse_name || null,
                                room_name: p.room_name || null,
                                carton_name: p.carton_name || null,
                            };
                        });
                        self.applyDefaultWarehouseMappings();

                        self.summary.discountType = d.discount_type || '';
                        self.summary.discountValue = parseFloat(d.discount_amount) || 0;
                        self.summary.extraCharge = parseFloat(d.other_charge_amount) || 0;
                        self.summary.deliveryCharge = parseFloat(d.delivery_fee) || 0;
                        self.summary.roundOff = parseFloat(d.round_off_from_total) || 0;
                        self.summary.couponCode = d.coupon || '';
                        self.summary.couponDiscount = parseFloat(d.coupon_discount_amount) || 0;

                        var di = d.delivery_info || {};
                        self.delivery.expectedDate = d.due_date || di.expected_delivery_date || '';
                        self.delivery.method = di.delivery_method || 'home';
                        self.delivery.courier = di.courier_method || 'none';
                        self.delivery.courierAddress = di.courier_address || addr.address || '';
                        self.delivery.courierNote = di.order_note || '';
                        self.courierAddressManuallyEdited = !!self.delivery.courierAddress;
                        self.delivery.outlet = di.outlet ? String(di.outlet) : '';
                        self.normalizeSelectedOutlet();

                        self.printUrl = '/order-invoice/' + d.slug;

                        var pmts = d.payments || {};
                        self.payMethods.forEach(function(m) {
                            var val = parseFloat(pmts[m.key]) || 0;
                            m.amount = val;
                            m.active = val > 0;
                        });

                    }).catch(function(err) {
                        console.error('fetchOrder error', err);
                    });
                },

                applyDefaultWarehouseMappings: function() {
                    var self = this;
                    self.products = (self.products || []).map(function(p) {
                        return self._withDefaultWarehouseMapping(p);
                    });
                },

                _withDefaultWarehouseMapping: function(product) {
                    var p = Object.assign({}, product);
                    var info = p.variantInfo || {};
                    var combo = this._resolveProductCombinationForMapping(p, info);
                    var warehouseStocks = combo ? (combo.warehouse_stocks || []) : (info.warehouse_stocks || []);
                    var roomStocks = combo ? (combo.room_stocks || []) : (info.room_stocks || []);
                    var cartonStocks = combo ? (combo.cartoon_stocks || []) : (info.cartoon_stocks || []);

                    var wh = p.warehouse_id ? this._findStockRowById(warehouseStocks, p.warehouse_id) : null;
                    wh = wh || this._firstPositiveStock(warehouseStocks);
                    if (!wh) {
                        return p;
                    }

                    p.warehouse_id = wh.id || null;
                    p.warehouse_name = wh.name || null;
                    p.warehouse = wh.name || '';

                    var roomsForWarehouse = (roomStocks || []).filter(function(r) {
                        return String(r.warehouse_id) === String(wh.id);
                    });
                    var room = p.warehouse_room_id ? this._findStockRowById(roomsForWarehouse, p.warehouse_room_id) : null;
                    room = room || this._firstPositiveStock(roomsForWarehouse);

                    if (room) {
                        p.warehouse_room_id = room.id || null;
                        p.room_name = room.name || null;

                        var cartonsForRoom = (cartonStocks || []).filter(function(c) {
                            return String(c.warehouse_id) === String(wh.id) &&
                                String(c.room_id) === String(room.id);
                        });
                        var carton = p.warehouse_carton_id ? this._findStockRowById(cartonsForRoom, p.warehouse_carton_id) : null;
                        carton = carton || this._firstPositiveStock(cartonsForRoom);

                        if (carton) {
                            p.warehouse_carton_id = carton.id || null;
                            p.carton_name = carton.name || ('Carton #' + carton.id);
                        } else {
                            p.warehouse_carton_id = null;
                            p.carton_name = null;
                        }
                    } else {
                        p.warehouse_room_id = null;
                        p.warehouse_carton_id = null;
                        p.room_name = null;
                        p.carton_name = null;
                    }

                    if (combo && !p.combination_id) {
                        p.combination_id = combo.id || null;
                    }

                    return p;
                },

                _resolveProductCombinationForMapping: function(p, info) {
                    var combos = info.combinations || [];
                    if (!combos.length) {
                        return null;
                    }

                    var combo = null;
                    if (p.combination_id) {
                        combo = combos.find(function(c) {
                            return String(c.id) === String(p.combination_id);
                        });
                    }

                    if (!combo && p.variant) {
                        combo = combos.find(function(c) {
                            return String(c.display || '') === String(p.variant || '');
                        });
                    }

                    if (!combo) {
                        combo = combos.find(function(c) {
                            return (parseFloat(c.stock) || 0) > 0;
                        });
                    }

                    return combo || null;
                },

                _firstPositiveStock: function(rows) {
                    rows = rows || [];
                    return rows.find(function(row) {
                        return row && row.id && (parseFloat(row.stock) || 0) > 0;
                    }) || null;
                },

                _findStockRowById: function(rows, id) {
                    rows = rows || [];
                    return rows.find(function(row) {
                        return row && String(row.id) === String(id) && (parseFloat(row.stock) || 0) > 0;
                    }) || null;
                },

                /* ── GEO ─────────────────────────────────────────────────── */
                loadDistricts: function() {
                    if (!_routes.districts) return;
                    var self = this;
                    axios.get(_routes.districts).then(function(res) {
                        self.districts = res.data.data || [];
                    }).catch(function() {});
                },

                onDistrictChange: function() {
                    this.customer.upazila_id = '';
                    this.upazilas = [];
                    if (this.customer.district_id) {
                        this.loadUpazilas(this.customer.district_id);
                    }
                },

                loadUpazilas: function(districtId) {
                    if (!_routes.upazilas || !districtId) return;
                    var self = this;
                    axios.get(_routes.upazilas, {
                            params: {
                                district_id: districtId
                            }
                        })
                        .then(function(res) {
                            self.upazilas = res.data.data || [];
                        })
                        .catch(function() {});
                },

                /* ── PAYMENT METHODS ─────────────────────────────────────── */
                loadPaymentMethods: function() {
                    if (!_routes.paymentMethods) return;
                    var self = this;
                    axios.get(_routes.paymentMethods).then(function(res) {
                        var data = res.data && res.data.data ? res.data.data : null;
                        if (!data || !data.length) return;
                        var existing = {};
                        self.payMethods.forEach(function(m) {
                            existing[m.key] = m.amount;
                        });
                        self.payMethods = data.map(function(m) {
                            var k = String(m.id || m.title).toLowerCase().replace(/\s+/g, '_');
                            return {
                                key: k,
                                label: m.title,
                                active: (existing[k] || 0) > 0,
                                amount: existing[k] || 0
                            };
                        });
                    }).catch(function() {});
                },

                /* Build a default courier delivery address from customer location fields */
                syncCourierAddressFromCustomer: function() {
                    if (this.courierAddressManuallyEdited) return;

                    var parts = [];
                    if (this.customer.address) parts.push(this.customer.address);
                    if (this.customer.post_office) parts.push(this.customer.post_office);
                    if (this.customer.thana) parts.push(this.customer.thana);

                    var upa = this.upazilas.find(function(u) {
                        return u.id == this.customer.upazila_id;
                    }.bind(this));
                    if (upa && upa.name) parts.push(upa.name);

                    var dist = this.districts.find(function(d) {
                        return d.id == this.customer.district_id;
                    }.bind(this));
                    if (dist && dist.name) parts.push(dist.name);

                    this.delivery.courierAddress = parts.join(', ');
                },

                onCourierAddressInput: function() {
                    this.courierAddressManuallyEdited = true;
                },

                /* ── WAREHOUSES (global list, used for simple products) ───── */
                loadWarehouses: function() {
                    if (!_routes.warehouses) return;
                    var self = this;
                    axios.get(_routes.warehouses).then(function(res) {
                        var wh = res.data.data || [];
                        self.warehouses = wh.map(function(w) {
                            return {
                                id: w.id,
                                name: w.title,
                                stock: 0
                            };
                        });
                    }).catch(function() {});
                },

                loadOutlets: function() {
                    if (!_routes.outlets) return;
                    var self = this;
                    axios.get(_routes.outlets).then(function(res) {
                        self.outlets = (res.data.data || []).map(function(o) {
                            return {
                                id: String(o.id),
                                title: o.title || '',
                                address: o.address || '',
                                contact: o.contact_number_1 || ''
                            };
                        });
                        self.normalizeSelectedOutlet();
                    }).catch(function() {});
                },

                normalizeSelectedOutlet: function() {
                    if (!this.delivery.outlet || !this.outlets.length) return;

                    var selected = String(this.delivery.outlet);
                    var exact = this.outlets.find(function(o) {
                        return String(o.id) === selected;
                    });
                    if (exact) {
                        this.delivery.outlet = String(exact.id);
                        return;
                    }

                    var byTitle = this.outlets.find(function(o) {
                        return String(o.title) === selected || String(o.title + ' — ' + o.address) === selected;
                    });
                    if (byTitle) {
                        this.delivery.outlet = String(byTitle.id);
                    }
                },

                /* ── PRODUCT SEARCH ──────────────────────────────────────── */
                onProductSearchInput: _debounce(function() {
                    var q = (this.productSearch || '').trim();
                    if (!q) {
                        this.productSearchResults = [];
                        this.showProductSearch = false;
                        return;
                    }
                    if (!_routes.productSearch) return;
                    var self = this;
                    self.searchLoading = true;
                    axios.get(_routes.productSearch, {
                            params: {
                                q: q
                            }
                        })
                        .then(function(res) {
                            self.productSearchResults = res.data.data || [];
                            self.showProductSearch = self.productSearchResults.length > 0;
                        })
                        .catch(function() {})
                        .finally(function() {
                            self.searchLoading = false;
                        });
                }, 400),

                addFromSearch: function(p) {
                    this.products.push(this._withDefaultWarehouseMapping({
                        id: 'new-' + Date.now(),
                        product_id: p.id,
                        name: p.name,
                        img: p.image || '',
                        unit: p.price,
                        discount_amount: 0,
                        qty: 1,
                        variant: null,
                        combination_id: null,
                        warehouse: '',
                        barcode: '',
                        variantInfo: p.variant_info || {},
                        warehouse_id: null,
                        warehouse_room_id: null,
                        warehouse_carton_id: null,
                        warehouse_name: null,
                        room_name: null,
                        carton_name: null,
                    }));
                    this.productSearch = '';
                    this.productSearchResults = [];
                    this.showProductSearch = false;
                },

                /* ── PRODUCT TABLE ───────────────────────────────────────── */
                recalcProduct: function(i) {
                    this.$set(this.products, i, Object.assign({}, this.products[i]));
                },

                setDiscPct: function(i, pct) {
                    var p = this.products[i];
                    var newDisc = ((parseFloat(pct) || 0) / 100) * p.unit;
                    this.$set(this.products, i, Object.assign({}, p, {
                        discount_amount: newDisc
                    }));
                },

                removeProduct: function(i) {
                    this.products.splice(i, 1);
                },

                addProduct: function() {
                    var el = this.$el.querySelector('.product-search-wrap input');
                    if (el) el.focus();
                },

                /* ── PAYMENT ─────────────────────────────────────────────── */
                togglePayMethod: function(m) {
                    m.active = !m.active;
                    if (!m.active) m.amount = 0;
                },
                s_alert(title, icon = 'info', text = null) {
                    const opts = { title, icon, allowOutsideClick: false, allowEscapeKey: false };
                    if (text) opts.text = text;
                    if (typeof Swal !== 'undefined') {
                        Swal.fire(opts);
                    } else {
                        alert(title);
                    }
                },

                /* ── COUPON ──────────────────────────────────────────────── */
                applyCoupon() {
                    var code = (this.summary.couponCode || '').trim();
                    if (!code) {
                        this.s_alert('Enter a coupon code', 'error');
                        return;
                    }
                    this.loading.coupon = true;
                    axios.post(_routes.applyCoupon, {
                        code: code,
                        subtotal: this.subtotal,
                    })
                        .then((r) => {
                            if (r.data && r.data.success) {
                                var data = r.data.data || {};
                                this.coupon.type = data.type === 'percent' ? 'percent' : 'fixed';
                                this.coupon.value = Number(data.value) || 0;
                                this.coupon.percent = data.type === 'percent' ? this.coupon.value : 0;
                                this.summary.couponDiscount = Number(data.amount) || 0;
                                this.summary.couponCode = code;
                                this.s_alert((r.data.message) || 'Coupon applied', 'success');
                            } else {
                                this.summary.couponDiscount = 0;
                                this.s_alert((r.data && r.data.message) || 'Invalid coupon', 'error');
                            }
                        })
                        .catch((err) => {
                            this.summary.couponDiscount = 0;
                            var msg = (err.response && err.response.data && err.response.data.message) ? err.response.data.message : 'Coupon apply failed';
                            this.s_alert(msg, 'error');
                        })
                        .finally(() => {
                            this.loading.coupon = false;
                        });
                },
                removeCoupon() {
                    this.summary.couponCode = '';
                    this.summary.couponDiscount = 0;
                    this.coupon.type = '';
                    this.coupon.value = 0;
                    this.coupon.percent = 0;
                },

                /* ══════════════════════════════════════════════════════════
                   WAREHOUSE MODAL — CORE
                ══════════════════════════════════════════════════════════ */

                /* ══════════════════════════════════════════════════════════════════════
                openWhModal(idx)
                ─────────────────
                Opens the warehouse modal. If the product already has a saved
                variant/warehouse assignment, restores that state instead of resetting.
                ══════════════════════════════════════════════════════════════════════ */
                openWhModal: function(idx) {
                    var self = this;
                    var p = this.products[idx];
                    var info = p.variantInfo || {};

                    // ── base reset ──
                    self.whModal.open = true;
                    self.whModal.productIdx = idx;
                    self.whModal.productName = (p.name || '').substring(0, 40) + (p.name && p.name.length > 40 ?
                        '…' : '');
                    self.whModal.productFullName = p.name || '';
                    self.whModal.productImg = p.img || '';
                    self.whModal.variantType = info.variant_type || 'simple';
                    self.whModal.resolvedCombination = null;
                    self.whModal.allVariantsSelected = false;
                    self.whModal.showWarehouseBlock = false;
                    self.whModal.selectedVariantDisplay = '';
                    self.whModal.availableCombosWithStock = [];
                    self.whModal.availableWarehouses = [];
                    self.whModal.availableRooms = [];
                    self.whModal.availableCartons = [];
                    self.whModal.simpleWarehouseStocks = [];
                    self.whModal.simpleRoomStocks = [];
                    self.whModal.simpleCartonStocks = [];
                    self.whModal.stockAtWarehouse = 0;
                    self.whModal.stockAtRoom = 0;
                    self.whModal.stockAtCarton = 0;

                    // ── store raw combinations ──
                    var combinations = info.combinations || [];
                    self.whModal.combinations = combinations;
                    self.whModal.simpleWarehouseStocks = info.warehouse_stocks || [];
                    self.whModal.simpleRoomStocks = info.room_stocks || [];
                    self.whModal.simpleCartonStocks = info.cartoon_stocks || [];

                    // ── total stock ──
                    self.whModal.totalStock = (info.product && info.product.total_stock != null) ?
                        info.product.total_stock :
                        (info.stock != null ? info.stock : (p.stock != null ? p.stock : combinations.reduce(function(s, c) {
                            return s + (c.stock || 0);
                        }, 0)));

                    // ── all combinations that have actual stock (for the info table) ──
                    self.whModal.availableCombosWithStock = combinations.filter(function(c) {
                        return c.stock > 0;
                    });

                    // ══════════════════════════════════════════════════════════════════
                    // TYPE 1 — Simple product (no variants)
                    // ══════════════════════════════════════════════════════════════════
                    var hasVariantDefs = (info.colors && info.colors.length) ||
                        (info.sizes && info.sizes.length) ||
                        ((info.other_variants || []).length);

                    if (self.whModal.variantType === 'simple' || (!combinations.length && !hasVariantDefs)) {
                        self.whModal.variantGroups = [];
                        self.whModal.selectedVariants = {};
                        self.whModal.allVariantsSelected = true;
                        self.whModal.showWarehouseBlock = true;

                        // Populate warehouse list from product_stocks location data.
                        self.whModal.availableWarehouses = (self.whModal.simpleWarehouseStocks || []).map(function(w) {
                            return {
                                id: w.id,
                                name: w.name,
                                stock: w.stock || 0
                            };
                        });

                        if (!self.whModal.availableWarehouses.length) {
                            self.whModal.availableWarehouses = self.warehouses.map(function(w) {
                                return {
                                    id: w.id,
                                    name: w.name,
                                    stock: self.whModal.totalStock
                                };
                            });
                        }

                        // ── RESTORE saved warehouse for simple product ──
                        if (p.warehouse_id) {
                            var savedWh = self.whModal.availableWarehouses.find(function(w) {
                                return w.id === p.warehouse_id;
                            });
                            if (savedWh) {
                                self.selectWarehouse(savedWh);

                                if (p.warehouse_room_id) {
                                    var savedRoom = self.whModal.availableRooms.find(function(r) {
                                        return r.id === p.warehouse_room_id;
                                    });
                                    if (savedRoom) {
                                        self.selectRoom(savedRoom);

                                        if (p.warehouse_carton_id) {
                                            var savedCarton = self.whModal.availableCartons.find(function(c) {
                                                return c.id === p.warehouse_carton_id;
                                            });
                                            if (savedCarton) {
                                                self.selectCarton(savedCarton);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            self.whModal.warehouseId = null;
                            self.whModal.warehouseName = '';
                            self.whModal.roomId = null;
                            self.whModal.roomName = '';
                            self.whModal.cartonId = null;
                            self.whModal.cartonName = '';
                        }
                        return;
                    }

                    // ══════════════════════════════════════════════════════════════════
                    // TYPE 2 & 3 — Combination product
                    // ══════════════════════════════════════════════════════════════════

                    // ── build variant groups: color → size → other_variants ──
                    var groups = [];
                    var selected = {};

                    if (info.colors && info.colors.length) {
                        groups.push({
                            key: 'color',
                            label: 'Color',
                            options: info.colors
                        });
                        selected['color'] = '';
                    }
                    if (info.sizes && info.sizes.length) {
                        groups.push({
                            key: 'size',
                            label: 'Size',
                            options: info.sizes
                        });
                        selected['size'] = '';
                    }
                    (info.other_variants || []).forEach(function(ov) {
                        Object.keys(ov).forEach(function(k) {
                            groups.push({
                                key: k,
                                label: k.charAt(0).toUpperCase() + k.slice(1),
                                options: ov[k]
                            });
                            selected[k] = '';
                        });
                    });

                    // Seed selected values from existing variant label, if any (e.g. "Green • M • 8GB • 512GB")
                    var existingLabel = p.variant || '';
                    /**
                     * Restore previously selected variants in two ways:
                     *
                     * 1) From the joined label string `p.variant`
                     *    Example:
                     *      - existingLabel = "Green • M • 8GB • 512GB"
                     *      - groups: [{key: 'color'}, {key: 'size'}, {key: 'ram'}, {key: 'storage'}]
                     *      → selected = { color: "Green", size: "M", ram: "8GB", storage: "512GB" }
                     *
                     * 2) If there is no " • " in the label (older data), fall back to
                     *    `p.selected_variant_info` and match keys case‑insensitively:
                     *      - selected_variant_info may have keys like "Color", "SIZE", "ram"
                     *      - we normalize keys to lowercase and map them back to group keys.
                     */
                    if (existingLabel) {
                        var parts = [];
                        if (existingLabel.includes(' • ')) {
                            parts = existingLabel.split('•').map(function(s) {
                                return String(s || '').trim();
                            });
                            groups.forEach(function(g, idx) {
                                if (parts[idx]) {
                                    selected[g.key] = parts[idx];
                                }
                            });
                        } else {
                            var svi = p['selected_variant_info'] || {};
                            var sviLower = {};
                            Object.keys(svi).forEach(function(k) {
                                sviLower[String(k).toLowerCase()] = svi[k];
                            });

                            groups.forEach(function(g) {
                                var keyLower = String(g.key).toLowerCase();
                                if (sviLower.hasOwnProperty(keyLower)) {
                                    selected[g.key] = sviLower[keyLower];
                                }
                            });
                        }
                    }

                    self.whModal.variantGroups = groups;

                    // ── RESTORE previously saved variant selection ──
                    // Try to find the saved combination by combination_id first, then by variant display label
                    var savedCombo = null;

                    if (p.combination_id) {
                        savedCombo = combinations.find(function(c) {
                            return c.id === p.combination_id;
                        });
                    }

                    // Fallback: match by display label (e.g. "yellow • M")
                    if (!savedCombo && p.variant) {
                        savedCombo = combinations.find(function(c) {
                            return c.display === p.variant;
                        });
                    }

                    if (savedCombo) {
                        // Restore each variant group's selected value:
                        // - Start from any values parsed from the existing label
                        // - Override keys that are present in the saved combination's attributes
                        Object.keys(savedCombo.attributes || {}).forEach(function(attrKey) {
                            if (selected.hasOwnProperty(attrKey)) {
                                selected[attrKey] = savedCombo.attributes[attrKey];
                            }
                        });

                        self.whModal.selectedVariants = selected;
                        self.whModal.allVariantsSelected = groups.every(function(g) {
                            return !!selected[g.key];
                        });
                        self.whModal.selectedVariantDisplay = self._computeSelectedVariantLabel(selected, groups);
                        self.whModal.resolvedCombination = savedCombo;

                        if (savedCombo.stock > 0) {
                            self.whModal.showWarehouseBlock = true;
                            self._buildWarehousesFromCombinations([savedCombo]);

                            // ── RESTORE saved warehouse selection ──
                            if (p.warehouse_id) {
                                var restoredWh = self.whModal.availableWarehouses.find(function(w) {
                                    return w.id === p.warehouse_id;
                                });

                                if (restoredWh) {
                                    self.whModal.warehouseId = restoredWh.id;
                                    self.whModal.warehouseName = restoredWh.name;
                                    self.whModal.stockAtWarehouse = restoredWh.stock;

                                    // ── RESTORE saved room ──
                                    var roomMap = {};;
                                    [savedCombo].forEach(function(c) {
                                        (c.room_stocks || []).forEach(function(rs) {
                                            if (rs.warehouse_id !== restoredWh.id) return;
                                            if (!roomMap[rs.id]) roomMap[rs.id] = {
                                                id: rs.id,
                                                name: rs.name,
                                                stock: 0,
                                                warehouse_id: rs.warehouse_id
                                            };
                                            roomMap[rs.id].stock += rs.stock;
                                        });
                                    });
                                    self.whModal.availableRooms = Object.values(roomMap);

                                    if (p.warehouse_room_id) {
                                        var restoredRoom = self.whModal.availableRooms.find(function(r) {
                                            return r.id === p.warehouse_room_id;
                                        });

                                        if (restoredRoom) {
                                            self.whModal.roomId = restoredRoom.id;
                                            self.whModal.roomName = restoredRoom.name;
                                            self.whModal.stockAtRoom = restoredRoom.stock;

                                            // ── RESTORE saved carton ──
                                            var ctnMap = {};;
                                            [savedCombo].forEach(function(c) {
                                                (c.cartoon_stocks || []).forEach(function(cs) {
                                                    if (cs.room_id !== restoredRoom.id || cs
                                                        .warehouse_id !== restoredWh.id) return;
                                                    if (!ctnMap[cs.id]) ctnMap[cs.id] = {
                                                        id: cs.id,
                                                        name: 'Carton #' + cs.id,
                                                        stock: 0
                                                    };
                                                    ctnMap[cs.id].stock += cs.stock;
                                                });
                                            });
                                            self.whModal.availableCartons = Object.values(ctnMap);

                                            if (p.warehouse_carton_id) {
                                                var restoredCarton = self.whModal.availableCartons.find(
                                                    function(c) {
                                                        return c.id === p.warehouse_carton_id;
                                                    });
                                                if (restoredCarton) {
                                                    self.whModal.cartonId = restoredCarton.id;
                                                    self.whModal.cartonName = restoredCarton.name;
                                                    self.whModal.stockAtCarton = restoredCarton.stock;
                                                } else {
                                                    self.whModal.cartonId = null;
                                                    self.whModal.cartonName = '';
                                                }
                                            } else {
                                                self.whModal.cartonId = null;
                                                self.whModal.cartonName = '';
                                            }
                                        } else {
                                            self.whModal.roomId = null;
                                            self.whModal.roomName = '';
                                            self.whModal.cartonId = null;
                                            self.whModal.cartonName = '';
                                            self.whModal.availableCartons = [];
                                        }
                                    } else {
                                        self.whModal.roomId = null;
                                        self.whModal.roomName = '';
                                        self.whModal.cartonId = null;
                                        self.whModal.cartonName = '';
                                        self.whModal.availableCartons = [];
                                    }
                                } else {
                                    // warehouse id saved but not found in current list — clear it
                                    self.whModal.warehouseId = null;
                                    self.whModal.warehouseName = '';
                                    self.whModal.roomId = null;
                                    self.whModal.roomName = '';
                                    self.whModal.cartonId = null;
                                    self.whModal.cartonName = '';
                                    self.whModal.availableRooms = [];
                                    self.whModal.availableCartons = [];
                                }
                            } else {
                                // no saved warehouse yet
                                self.whModal.warehouseId = null;
                                self.whModal.warehouseName = '';
                                self.whModal.roomId = null;
                                self.whModal.roomName = '';
                                self.whModal.cartonId = null;
                                self.whModal.cartonName = '';
                                self.whModal.availableRooms = [];
                                self.whModal.availableCartons = [];
                            }
                        } else {
                            // saved combo but out of stock — just show it without warehouse block
                            self.whModal.showWarehouseBlock = false;
                            self.whModal.warehouseId = null;
                            self.whModal.warehouseName = '';
                            self.whModal.roomId = null;
                            self.whModal.roomName = '';
                            self.whModal.cartonId = null;
                            self.whModal.cartonName = '';
                        }

                    } else {
                        // No saved combo — fresh start, all selects empty
                        self.whModal.selectedVariants = selected;
                        self.whModal.allVariantsSelected = false;
                         self.whModal.selectedVariantDisplay = self._computeSelectedVariantLabel(selected, groups);
                        self.whModal.showWarehouseBlock = false;
                        self.whModal.warehouseId = null;
                        self.whModal.warehouseName = '';
                        self.whModal.roomId = null;
                        self.whModal.roomName = '';
                        self.whModal.cartonId = null;
                        self.whModal.cartonName = '';
                        self._buildWarehousesFromCombinations(
                            combinations.filter(function(c) {
                                return c.stock > 0;
                            })
                        );
                    }
                },

                /*
                 * onVariantChange()
                 * ─────────────────
                 * Fired on every variant <select> change.
                 *
                 * Matching strategy:
                 *   - Try to find an EXACT attribute match across all selected keys.
                 *   - If found and stock > 0  → show warehouse block for that combo.
                 *   - If found and stock == 0 → show out-of-stock info + available table.
                 *   - If NOT found (partial assignment case, e.g. ram+storage not in combos)
                 *     → try a PARTIAL match: find combinations whose attribute keys are a
                 *       SUBSET of the selected keys and whose values match.
                 *       If partial match found with stock → show warehouse block for it.
                 *     → Otherwise show "not assigned" info + available table.
                 */
                onVariantChange: function() {
                    var self = this;
                    var selected = self.whModal.selectedVariants;
                    var groups = self.whModal.variantGroups;
                    var combos = self.whModal.combinations;

                    // Check all variant groups have a value
                    var allSelected = groups.every(function(g) {
                        return !!selected[g.key];
                    });
                    self.whModal.allVariantsSelected = allSelected;

                    // Always keep a human-readable label in sync with current selections
                    self.whModal.selectedVariantDisplay = self._computeSelectedVariantLabel(selected, groups);

                    if (!allSelected) {
                        self.whModal.resolvedCombination = null;
                        self.whModal.showWarehouseBlock = false;
                        self._resetWarehouseChain();
                        return;
                    }

                    // ── 1. Try exact match ──
                    var exactMatch = combos.find(function(c) {
                        var cKeys = Object.keys(c.attributes);
                        var gKeys = groups.map(function(g) {
                            return g.key;
                        });
                        // same number of attribute keys AND all values match
                        if (cKeys.length !== gKeys.length) return false;
                        return gKeys.every(function(k) {
                            return c.attributes[k] === selected[k];
                        });
                    });

                    if (exactMatch) {
                        self.whModal.resolvedCombination = exactMatch;
                        if (exactMatch.stock > 0) {
                            self.whModal.showWarehouseBlock = true;
                            self._buildWarehousesFromCombinations([exactMatch]);
                        } else {
                            // exact match but no stock
                            self.whModal.showWarehouseBlock = false;
                            self._resetWarehouseChain();
                        }
                        return;
                    }

                    // ── 2. No exact match — try PARTIAL match ──
                    // Find combos whose attributes are a SUBSET of the user's selections
                    // (covers the case where color+size combos exist but ram+storage combos don't)
                    var partialMatches = combos.filter(function(c) {
                        var cKeys = Object.keys(c.attributes);
                        // every attribute in this combo must match the user's selection
                        return cKeys.length > 0 && cKeys.every(function(k) {
                            return selected[k] !== undefined && c.attributes[k] === selected[k];
                        });
                    });

                    var partialWithStock = partialMatches.filter(function(c) {
                        return c.stock > 0;
                    });

                    if (partialWithStock.length > 0) {
                        // Use first partial match as resolved combo (best available)
                        self.whModal.resolvedCombination = partialWithStock[0];
                        self.whModal.showWarehouseBlock = true;
                        self._buildWarehousesFromCombinations(partialWithStock);
                    } else {
                        // No usable match at all
                        self.whModal.resolvedCombination = null;
                        self.whModal.showWarehouseBlock = false;
                        self._resetWarehouseChain();
                    }
                },

                /*
                 * _buildWarehousesFromCombinations(combos)
                 * ─────────────────────────────────────────
                 * Aggregate warehouse_stocks from the given combos array into the modal list.
                 */
                _buildWarehousesFromCombinations: function(combos) {
                    var whMap = {};
                    combos.forEach(function(c) {
                        (c.warehouse_stocks || []).forEach(function(ws) {
                            if (!whMap[ws.id]) whMap[ws.id] = {
                                id: ws.id,
                                name: ws.name,
                                stock: 0
                            };
                            whMap[ws.id].stock += ws.stock;
                        });
                    });
                    this.whModal.availableWarehouses = Object.values(whMap);
                    this._resetWarehouseChain();
                },

                // Build a display label like "Green • M • 8GB • 512GB" from the current selections
                _computeSelectedVariantLabel: function(selected, groups) {
                    if (!groups || !groups.length) return '';
                    return groups.map(function(g) {
                            return selected[g.key];
                        })
                        .filter(function(v) {
                            return !!v;
                        })
                        .join(' • ');
                },

                /* Reset warehouse/room/carton selection */
                _resetWarehouseChain: function() {
                    var m = this.whModal;
                    m.warehouseId = null;
                    m.warehouseName = '';
                    m.roomId = null;
                    m.roomName = '';
                    m.cartonId = null;
                    m.cartonName = '';
                    m.availableRooms = [];
                    m.availableCartons = [];
                    m.stockAtWarehouse = 0;
                    m.stockAtRoom = 0;
                    m.stockAtCarton = 0;
                },

                _getRoomStocksSource: function() {
                    var resolved = this.whModal.resolvedCombination;
                    if (resolved) return resolved.room_stocks || [];
                    if (this.whModal.combinations && this.whModal.combinations.length) {
                        var rows = [];
                        this.whModal.combinations.forEach(function(c) {
                            rows = rows.concat(c.room_stocks || []);
                        });
                        return rows;
                    }
                    return this.whModal.simpleRoomStocks || [];
                },

                _getCartonStocksSource: function() {
                    var resolved = this.whModal.resolvedCombination;
                    if (resolved) return resolved.cartoon_stocks || [];
                    if (this.whModal.combinations && this.whModal.combinations.length) {
                        var rows = [];
                        this.whModal.combinations.forEach(function(c) {
                            rows = rows.concat(c.cartoon_stocks || []);
                        });
                        return rows;
                    }
                    return this.whModal.simpleCartonStocks || [];
                },

                /* Warehouse card clicked */
                selectWarehouse: function(w) {
                    var self = this;
                    self.whModal.warehouseId = w.id;
                    self.whModal.warehouseName = w.name;
                    self.whModal.stockAtWarehouse = w.stock;
                    self.whModal.roomId = null;
                    self.whModal.roomName = '';
                    self.whModal.cartonId = null;
                    self.whModal.cartonName = '';
                    self.whModal.availableCartons = [];
                    self.whModal.stockAtRoom = 0;
                    self.whModal.stockAtCarton = 0;

                    var roomStocks = self._getRoomStocksSource();
                    if (!roomStocks.length) {
                        self.whModal.availableRooms = [];
                        return;
                    }

                    var roomMap = {};
                    roomStocks.forEach(function(rs) {
                        if (rs.warehouse_id != w.id) return;
                        if (!roomMap[rs.id]) roomMap[rs.id] = {
                            id: rs.id,
                            name: rs.name,
                            stock: 0,
                            warehouse_id: rs.warehouse_id
                        };
                        roomMap[rs.id].stock += rs.stock;
                    });
                    self.whModal.availableRooms = Object.values(roomMap);
                },

                /* Room card clicked */
                selectRoom: function(r) {
                    var self = this;
                    self.whModal.roomId = r.id;
                    self.whModal.roomName = r.name;
                    self.whModal.stockAtRoom = r.stock;
                    self.whModal.cartonId = null;
                    self.whModal.cartonName = '';
                    self.whModal.stockAtCarton = 0;

                    var cartonStocks = self._getCartonStocksSource();
                    if (!cartonStocks.length) {
                        self.whModal.availableCartons = [];
                        return;
                    }

                    var ctnMap = {};
                    cartonStocks.forEach(function(cs) {
                        if (cs.room_id != r.id || cs.warehouse_id != self.whModal.warehouseId) return;
                        if (!ctnMap[cs.id]) ctnMap[cs.id] = {
                            id: cs.id,
                            name: cs.name || ('Carton #' + cs.id),
                            stock: 0
                        };
                        ctnMap[cs.id].stock += cs.stock;
                    });
                    self.whModal.availableCartons = Object.values(ctnMap);
                },

                /* Carton card clicked */
                selectCarton: function(c) {
                    this.whModal.cartonId = c.id;
                    this.whModal.cartonName = c.name;
                    this.whModal.stockAtCarton = c.stock;
                },

                closeWhModal: function() {
                    this.whModal.open = false;
                },

                /* ══════════════════════════════════════════════════════════════════════
                confirmWarehouseAssign()
                ─────────────────────────
                Validates and commits all assignment data back to the product row,
                including variant display, combination_id, and warehouse/room/carton ids.
                ══════════════════════════════════════════════════════════════════════ */
                confirmWarehouseAssign: function() {
                    var self = this;
                    var idx = self.whModal.productIdx;

                    // ── variant validation (combination products only) ──
                    if (self.whModal.variantGroups.length) {
                        if (!self.whModal.allVariantsSelected) {
                            alert('⚠️ Please select all variant options first.');
                            return;
                        }
                        if (!self.whModal.resolvedCombination) {
                            alert('⚠️ No stock found for the selected variant combination.');
                            return;
                        }
                        if (self.whModal.resolvedCombination.stock <= 0) {
                            alert('⚠️ The selected variant combination is out of stock.');
                            return;
                        }
                    }

                    // ── warehouse is mandatory ──
                    if (!self.whModal.warehouseId) {
                        alert('⚠️ Please select a warehouse before confirming.');
                        return;
                    }

                    // ── commit to product row ──
                    if (idx !== null) {
                        self.$set(self.products, idx, Object.assign({}, self.products[idx], {
                            // display fields (shown in the product table row)
                            warehouse: self.whModal.warehouseName,
                            variant: self.whModal.selectedVariantDisplay ||
                                (self.whModal.resolvedCombination ?
                                    self.whModal.resolvedCombination.display :
                                    self.products[idx].variant),
                            // id fields (sent in payload + used to restore modal state on reopen)
                            combination_id: self.whModal.resolvedCombination ?
                                self.whModal.resolvedCombination.id : null,
                            warehouse_id: self.whModal.warehouseId || null,
                            warehouse_room_id: self.whModal.roomId || null,
                            warehouse_carton_id: self.whModal.cartonId || null,
                            // also store names for display convenience
                            warehouse_name: self.whModal.warehouseName || null,
                            room_name: self.whModal.roomName || null,
                            carton_name: self.whModal.cartonName || null,
                        }));
                    }
                    self.closeWhModal();
                },

                /* ── CONFIRM ORDER (API SAVE) ──────────────────────────────────────── */
                confirmOrder: function() {
                    var self = this;

                    // ── payments object ──
                    var paymentsObj = {};
                    this.payMethods.forEach(function(m) {
                        if (m.active && m.amount > 0) paymentsObj[m.key] = m.amount;
                    });

                    // ── products list — include all warehouse assignment fields ──
                    var productsList = this.products
                        .filter(function(p) {
                            return p.product_id;
                        })
                        .map(function(p) {
                            return {
                                product_id: p.product_id,
                                unit: p.unit,
                                discount_amount: p.discount_amount || 0,
                                qty: p.qty || 1,
                                variant: p.variant || null,
                                combination_id: p.combination_id || null,
                                // warehouse assignment (set by confirmWarehouseAssign)
                                warehouse_id: p.warehouse_id || null,
                                warehouse_room_id: p.warehouse_room_id || null,
                                warehouse_carton_id: p.warehouse_carton_id || null,
                                barcode: p.barcode || null,
                                image: p.product_image || null,
                            };
                        });

                    if (!productsList.length) {
                        alert('No valid products to save. Please use the search to add products.');
                        return;
                    }

                    var payload = {
                        customer: {
                            order_source: this.customer.order_source,
                            name: this.customer.name,
                            phone: this.customer.phone,
                            email: this.customer.email || null,
                            district_id: this.customer.district_id || null,
                            upazila_id: this.customer.upazila_id || null,
                            thana: this.customer.thana || null,
                            post_office: this.customer.post_office || null,
                            address: this.customer.address || null,
                            note: this.customer.note || null,
                        },
                        products: productsList,
                        summary: {
                            discountType: this.summary.discountType,
                            discountValue: this.summary.discountValue || 0,
                            couponCode: this.summary.couponCode || '',
                            couponDiscount: this.summary.couponDiscount || 0,
                            extraCharge: this.summary.extraCharge || 0,
                            deliveryCharge: this.summary.deliveryCharge || 0,
                            roundOff: this.summary.roundOff || 0,
                        },
                        payments: paymentsObj,
                        delivery: {
                            method: this.delivery.method,
                            outlet: this.delivery.outlet || null,
                            expectedDate: this.delivery.expectedDate || null,
                            courier: this.delivery.courier,
                            courierAddress: this.delivery.courierAddress || null,
                            courierNote: this.delivery.courierNote || null,
                        },
                        order_status: this.orderStatus,
                        salesman_id: this.salesReference.salesman_id || null,
                        affiliate_code: this.salesReference.affiliate_code || null,
                        order_note: this.order.note || null,
                    };

                    var url = (_routes.orderUpdate || '/ecommerce/order-update/__ID__').replace('__ID__',
                        order_id);
                    self.saving = true;

                    axios.post(url, payload)
                        .then(function(res) {
                            self.whModal.invoiceOpen = false;
                            self.s_alert('Order saved successfully!', 'success');
                        })
                        .catch(function(err) {
                            self.whModal.invoiceOpen = false;
                            var msg = (err.response && err.response.data && err.response.data.message) ?
                                err.response.data.message : 'Save failed. Please try again.';
                            self.s_alert(msg, 'error');
                        })
                        .finally(function() {
                            self.saving = false;
                        });
                },

                /* ── RESET ───────────────────────────────────────────────── */
                resetForm: function() {
                    if (confirm('Reset all changes?')) location.reload();
                }
            }
        });
    </script>
@endpush
