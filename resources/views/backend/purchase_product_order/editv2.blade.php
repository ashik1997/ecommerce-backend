@extends('backend.master')

@section('header_css')
    <link href="{{ versioned_url('assets/plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.7.15/vue.min.js"></script>
    <script src="{{ versioned_asset('assets/js/purchase_edit_vue_v2.js') }}" defer></script>

    {{-- Pass order data to Vue before script runs --}}
    <script>
        window.PURCHASE_EDIT_DATA = {
            slug:       '{{ $data->slug }}',
            order_id:   {{ $data->id }},
            warehouse_id: '{{ $data->product_warehouse_id }}',
            supplier_id:  '{{ $data->product_supplier_id }}',
            date:         '{{ $data->date ? \Carbon\Carbon::parse($data->date)->format('Y-m-d') : now()->format('Y-m-d') }}',
            reference:    @json($data->reference ?? ''),
            note:         @json($data->note ?? ''),
            order_status: '{{ $data->order_status }}',
            is_admin:     {{ $isAdmin ? 'true' : 'false' }},
        };
    </script>

    <style>
        /* ═══════════════════════════════════════════════
       PURCHASE ORDER COMPONENT — TEAL BRAND SYSTEM
       Prefix: .po_component
       ═══════════════════════════════════════════════ */

        .po_component *,
        .po_component *::before,
        .po_component *::after {
            box-sizing: border-box;
        }

        .po_component {
            --t50: #f0fdfa;
            --t100: #ccfbf1;
            --t200: #99f6e4;
            --t400: #2dd4bf;
            --t500: #14b8a6;
            --t600: #0d9488;
            --t700: #0f766e;
            --t800: #115e59;
            --t900: #134e4a;

            --g50: #f8fafc;
            --g100: #f1f5f9;
            --g200: #e2e8f0;
            --g300: #cbd5e1;
            --g400: #94a3b8;
            --g500: #64748b;
            --g600: #475569;
            --g700: #334155;
            --g800: #1e293b;

            --ok: #10b981;
            --err: #ef4444;
            --warn: #f59e0b;
            --inf: #3b82f6;

            --r: 8px;
            --rl: 12px;
            --rxl: 18px;
            --sh: 0 1px 3px rgba(0, 0, 0, .07), 0 1px 2px rgba(0, 0, 0, .04);
            --shm: 0 4px 12px rgba(0, 0, 0, .08), 0 2px 4px rgba(0, 0, 0, .04);
            --shl: 0 8px 24px rgba(0, 0, 0, .1), 0 4px 8px rgba(0, 0, 0, .05);

            font-family: 'Nunito', 'Segoe UI', sans-serif;
            font-size: 13px;
            color: var(--g800);
            line-height: 1.5;
        }

        /* ── PAGE HEADER ─────────────────────── */
        .po_component .po-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .po_component .po-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .po_component .po-header-icon {
            width: 40px;
            height: 40px;
            background: var(--t600);
            border-radius: var(--rl);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
            flex-shrink: 0;
        }

        .po_component .po-header-left h4 {
            font-size: 17px;
            font-weight: 700;
            margin: 0;
            color: var(--g800);
            letter-spacing: -.3px;
        }

        .po_component .po-header-left span {
            font-size: 12px;
            color: var(--g400);
            display: block;
        }

        .po_component .po-btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 600;
            border: 1.5px solid var(--g200);
            background: #fff;
            color: var(--g600);
            text-decoration: none;
            transition: all .15s;
            box-shadow: var(--sh);
        }

        .po_component .po-btn-back:hover {
            border-color: var(--t400);
            color: var(--t600);
            background: var(--t50);
            text-decoration: none;
        }

        /* ── CARD ────────────────────────────── */
        .po_component .po-card {
            background: #fff;
            border-radius: var(--rl);
            border: 1.5px solid var(--g200);
            box-shadow: var(--sh);
            overflow: visible;
        }

        .po_component .po-card+.po-card {
            margin-top: 14px;
        }

        .po_component .po-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-bottom: 1.5px solid var(--g100);
            background: var(--g50);
            border-radius: var(--rl) var(--rl) 0 0;
        }

        .po_component .po-card-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: var(--g700);
        }

        .po_component .po-card-header-left i {
            color: var(--t500);
        }

        .po_component .po-card-body {
            padding: 16px 18px;
        }

        /* ── FORM GRID ───────────────────────── */
        .po_component .po-grid-5 {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }

        .po_component .po-grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .po_component .po-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .po_component .po-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        @media(max-width:1024px) {
            .po_component .po-grid-5 {
                grid-template-columns: repeat(3, 1fr);
            }

            .po_component .po-grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:640px) {

            .po_component .po-grid-5,
            .po_component .po-grid-4,
            .po_component .po-grid-3,
            .po_component .po-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .po_component .po-fg {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .po_component .po-fg.span2 {
            grid-column: span 2;
        }

        .po_component .po-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--g500);
            text-transform: uppercase;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .po_component .po-label i {
            color: var(--t400);
            font-size: 10px;
        }

        .po_component .po-label .req {
            color: var(--err);
            font-size: 12px;
            line-height: 1;
        }

        .po_component .po-input,
        .po_component .po-select,
        .po_component .po-textarea {
            width: 100%;
            padding: 7px 10px;
            border: 1.5px solid var(--g200);
            border-radius: var(--r);
            font-size: 13px;
            color: var(--g800);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
            font-family: inherit;
        }

        .po_component .po-input:focus,
        .po_component .po-select:focus,
        .po_component .po-textarea:focus {
            border-color: var(--t400);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, .12);
        }

        .po_component .po-input[readonly],
        .po_component .po-input:disabled {
            background: var(--g50);
            color: var(--g500);
            cursor: default;
        }

        .po_component .po-textarea {
            resize: none;
        }

        /* ── PRODUCT SEARCH AREA ─────────────── */
        .po_component .po-search-wrap {
            position: relative;
        }

        .po_component .po-search-dropdown {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 200;
            background: #fff;
            border: 1.5px solid var(--g200);
            border-radius: var(--rl);
            box-shadow: var(--shl);
            max-height: 280px;
            overflow-y: auto;
        }

        .po_component .po-search-item {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid var(--g100);
            transition: background .1s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .po_component .po-search-item:last-child {
            border-bottom: none;
        }

        .po_component .po-search-item:hover {
            background: var(--t50);
        }

        .po_component .po-search-item-img {
            width: 36px;
            height: 36px;
            border-radius: var(--r);
            object-fit: cover;
            background: var(--g100);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--g400);
        }

        .po_component .po-search-item-img img {
            width: 100%;
            height: 100%;
            border-radius: var(--r);
            object-fit: cover;
        }

        .po_component .po-search-item-name {
            font-weight: 700;
            font-size: 13px;
            color: var(--g800);
        }

        .po_component .po-search-item-meta {
            font-size: 11px;
            color: var(--g400);
            margin-top: 1px;
        }

        .po_component .po-search-loading {
            padding: 14px;
            text-align: center;
            color: var(--g400);
            font-size: 13px;
        }

        /* ── VARIANT SELECTION PANEL ─────────── */
        .po_component .po-variant-panel {
            background: var(--t50);
            border: 1.5px solid var(--t100);
            border-radius: var(--rl);
            padding: 14px 16px;
            margin-top: 12px;
            animation: po-slide-in .2s ease;
        }

        @keyframes po-slide-in {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .po_component .po-variant-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--t700);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .po_component .po-variant-title i {
            color: var(--t500);
        }

        .po_component .po-attr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }

        .po_component .po-attr-fg {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .po_component .po-attr-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--t700);
            text-transform: capitalize;
        }

        .po_component .po-attr-select {
            padding: 6px 8px;
            border: 1.5px solid var(--t200);
            border-radius: var(--r);
            font-size: 12px;
            color: var(--g800);
            background: #fff;
            outline: none;
            font-family: inherit;
            transition: border-color .15s;
        }

        .po_component .po-attr-select:focus {
            border-color: var(--t500);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, .15);
        }

        .po_component .po-comb-strip {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
            padding: 8px 12px;
            background: #fff;
            border-radius: var(--r);
            border: 1.5px solid var(--t200);
            margin-bottom: 10px;
        }

        .po_component .po-comb-pill {
            background: var(--t100);
            color: var(--t800);
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .po_component .po-comb-label {
            font-size: 11px;
            color: var(--t600);
            font-weight: 600;
            margin-right: 2px;
        }

        .po_component .po-no-match {
            padding: 8px 12px;
            background: #fff1f1;
            border: 1px solid #fca5a5;
            border-radius: var(--r);
            color: var(--err);
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ── ADD TO TABLE BUTTON ─────────────── */
        .po_component .po-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 700;
            border: none;
            background: var(--t600);
            color: #fff;
            cursor: pointer;
            transition: all .15s;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
            font-family: inherit;
        }

        .po_component .po-add-btn:hover {
            background: var(--t700);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(13, 148, 136, .35);
        }

        .po_component .po-add-btn:active {
            transform: translateY(0);
        }

        .po_component .po-add-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }

        /* ── PURCHASE ITEMS TABLE ────────────── */
        .po_component .po-table-wrap {
            overflow-x: auto;
            border-radius: var(--rl);
            border: 1.5px solid var(--g200);
            margin-top: 0;
        }

        .po_component .po-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
            font-size: 12.5px;
        }

        .po_component .po-table thead th {
            padding: 9px 12px;
            background: var(--t700);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .po_component .po-table thead th:first-child {
            border-radius: var(--rl) 0 0 0;
        }

        .po_component .po-table thead th:last-child {
            border-radius: 0 var(--rl) 0 0;
        }

        .po_component .po-table tbody tr {
            border-bottom: 1px solid var(--g100);
            transition: background .1s;
        }

        .po_component .po-table tbody tr:last-child {
            border-bottom: none;
        }

        .po_component .po-table tbody tr:hover {
            background: var(--t50);
        }

        .po_component .po-table td {
            padding: 8px 10px;
            color: var(--g700);
            vertical-align: middle;
        }

        .po_component .po-table .po-input {
            padding: 5px 8px;
            font-size: 12px;
            min-width: 70px;
        }

        .po_component .po-product-cell {
            min-width: 160px;
        }

        .po_component .po-product-name {
            font-weight: 700;
            color: var(--g800);
            font-size: 12px;
        }

        .po_component .po-product-variant {
            font-size: 11px;
            color: var(--t600);
            margin-top: 2px;
        }

        .po_component .po-prev-stock {
            font-weight: 700;
            color: var(--g600);
        }

        .po_component .po-barcode-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: var(--r);
            font-size: 11px;
            font-weight: 700;
            border: 1.5px solid var(--t200);
            background: var(--t50);
            color: var(--t700);
            cursor: pointer;
            transition: all .12s;
            white-space: nowrap;
            font-family: inherit;
        }

        .po_component .po-barcode-btn:hover {
            background: var(--t100);
            border-color: var(--t400);
        }

        .po_component .po-barcode-btn .po-bc-count {
            background: var(--t600);
            color: #fff;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .po_component .po-row-total {
            font-weight: 700;
            color: var(--t700);
        }

        .po_component .po-del-btn {
            width: 28px;
            height: 28px;
            border-radius: var(--r);
            border: 1.5px solid #fca5a5;
            background: #fff1f1;
            color: var(--err);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all .12s;
        }

        .po_component .po-del-btn:hover {
            background: var(--err);
            color: #fff;
            border-color: var(--err);
        }

        .po_component .po-empty-row td {
            padding: 32px;
            text-align: center;
            color: var(--g400);
            font-size: 13px;
        }

        .po_component .po-empty-row-icon {
            font-size: 28px;
            color: var(--g300);
            margin-bottom: 8px;
        }

        /* ── SUMMARY & NOTE ──────────────────── */
        .po_component .po-bottom-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 14px;
        }

        @media(max-width:768px) {
            .po_component .po-bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        .po_component .po-stat-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .po_component .po-stat-card {
            background: var(--g50);
            border: 1.5px solid var(--g200);
            border-radius: var(--r);
            padding: 10px 14px;
            min-width: 100px;
        }

        .po_component .po-stat-label {
            font-size: 11px;
            color: var(--g400);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .po_component .po-stat-val {
            font-size: 22px;
            font-weight: 700;
            color: var(--t600);
            margin-top: 2px;
            line-height: 1;
        }

        .po_component .po-totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .po_component .po-totals-table tr {
            border-bottom: 1px solid var(--g100);
        }

        .po_component .po-totals-table tr:last-child {
            border-bottom: none;
        }

        .po_component .po-totals-table td {
            padding: 8px 0;
            font-size: 13px;
        }

        .po_component .po-totals-table td:last-child {
            text-align: right;
            font-weight: 700;
            color: var(--g800);
        }

        .po_component .po-totals-table .grand td {
            font-size: 15px;
            color: var(--t700);
            padding-top: 10px;
        }

        .po_component .po-totals-table .grand td:first-child {
            font-weight: 700;
        }

        /* ── FOOTER ──────────────────────────── */
        .po_component .po-footer {
            padding: 14px 18px;
            border-top: 1.5px solid var(--g100);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: var(--g50);
            border-radius: 0 0 var(--rl) var(--rl);
        }

        /* ── BUTTONS ─────────────────────────── */
        .po_component .po-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: var(--r);
            font-size: 13px;
            font-weight: 700;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all .15s;
            font-family: inherit;
            line-height: 1;
            white-space: nowrap;
        }

        .po_component .po-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .po_component .po-btn-primary {
            background: var(--t600);
            border-color: var(--t600);
            color: #fff;
            box-shadow: 0 2px 6px rgba(13, 148, 136, .3);
        }

        .po_component .po-btn-primary:hover:not(:disabled) {
            background: var(--t700);
            border-color: var(--t700);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(13, 148, 136, .35);
        }

        .po_component .po-btn-ghost {
            background: #fff;
            border-color: var(--g200);
            color: var(--g600);
        }

        .po_component .po-btn-ghost:hover {
            background: var(--g50);
            border-color: var(--g300);
        }

        .po_component .po-btn-outline-danger {
            background: #fff;
            border-color: #dc3545;
            color: #dc3545;
        }

        .po_component .po-btn-outline-danger:hover {
            background: #dc3545;
            color: #fff;
        }

        .po_component .po-btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .po_component .po-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: po-spin .6s linear infinite;
            display: inline-block;
        }

        @keyframes po-spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── RECEIVED BANNER ─────────────────── */
        .po_component .po-received-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: #fff7ed;
            border: 1.5px solid #fed7aa;
            border-radius: var(--r);
            color: #92400e;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .po_component .po-received-banner i {
            color: #f59e0b;
            font-size: 14px;
        }

        /* ══ BARCODE MODAL ═══════════════════════════════════ */
        .po_component .po-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .55);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            animation: po-fade .15s ease;
        }

        @keyframes po-fade {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .po_component .po-modal {
            background: #fff;
            border-radius: var(--rxl);
            width: 100%;
            max-width: 1000px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
            animation: po-modal-in .2s ease;
        }

        @keyframes po-modal-in {
            from {
                opacity: 0;
                transform: scale(.96) translateY(8px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .po_component .po-modal-header {
            padding: 16px 20px;
            border-bottom: 1.5px solid var(--g100);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .po_component .po-modal-title-block {}

        .po_component .po-modal-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--g800);
            margin: 0 0 3px;
        }

        .po_component .po-modal-subtitle {
            font-size: 12px;
            color: var(--g400);
            margin: 0;
        }

        .po_component .po-modal-close {
            width: 30px;
            height: 30px;
            border-radius: var(--r);
            border: 1.5px solid var(--g200);
            background: #fff;
            color: var(--g500);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all .12s;
            flex-shrink: 0;
            margin-left: 10px;
        }

        .po_component .po-modal-close:hover {
            background: var(--err);
            border-color: var(--err);
            color: #fff;
        }

        .po_component .po-modal-body {
            padding: 16px 20px;
            overflow-y: auto;
            flex: 1;
        }

        .po_component .po-modal-footer {
            padding: 12px 20px;
            border-top: 1.5px solid var(--g100);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Scan row in modal */
        .po_component .po-modal-scan-row {
            display: grid;
            grid-template-columns: 1fr 120px;
            gap: 8px;
            padding: 10px;
            background: var(--g50);
            border-radius: var(--r);
            border: 1.5px dashed var(--g200);
            margin-bottom: 10px;
            align-items: flex-end
        }

        @media(max-width:420px) {
            .po_component .po-modal-scan-row {
                grid-template-columns: 1fr;
            }
        }

        /* Barcode table in modal */
        .po_component .po-bc-table-wrap {
            border: 1.5px solid var(--g200);
            border-radius: var(--r);
            overflow: auto;
        }

        .po_component .po-bc-table {
            min-width: 100%;
            width: max-content;
            border-collapse: collapse;
            font-size: 12px;
            white-space: nowrap;
        }

        .po_component .po-bc-table thead th {
            padding: 7px 10px;
            background: var(--t600);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            position: sticky;
            top: 0;
        }

        .po_component .po-bc-table thead th:first-child {
            width: 36px;
        }

        .po_component .po-bc-table tbody tr {
            border-bottom: 1px solid var(--g100);
        }

        .po_component .po-bc-table tbody tr:last-child {
            border-bottom: none;
        }

        .po_component .po-bc-table td {
            padding: 4px 8px;
            vertical-align: middle;
        }

        .po_component .po-bc-table td:first-child {
            font-size: 10px;
            color: var(--g400);
            text-align: center;
        }

        .po_component .po-bc-table .po-input {
            border-color: transparent;
            background: transparent;
            padding: 4px 6px;
            font-size: 12px;
        }

        .po_component .po-bc-table .po-input:focus {
            background: #fff;
            border-color: var(--t400);
        }

        .po_component .po-bc-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--t500);
            color: #fff;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 700;
            margin-left: 5px;
        }

        .po_component .po-hint {
            font-size: 11px;
            color: var(--g400);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
        }

        /* ── SELECT2 TWEAKS ──────────────────── */
        .po_component .select2-container .select2-selection--single {
            height: 36px !important;
            border: 1.5px solid var(--g200) !important;
            border-radius: var(--r) !important;
            display: flex;
            align-items: center;
        }

        .po_component .select2-container--default.select2-container--open .select2-selection--single,
        .po_component .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--t400) !important;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, .12) !important;
        }

        .po_component .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding: 0 10px !important;
            color: var(--g800) !important;
            font-size: 13px;
        }

        .po_component .select2-container .select2-selection--single .select2-selection__placeholder {
            color: var(--g400) !important;
        }

        .po_component .select2-container .select2-selection--single .select2-selection__arrow {
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 8px !important;
        }
    </style>
@endsection

@section('page_title')
    Edit Purchase Order
@endsection
@section('page_heading')
    Edit Purchase Order — {{ $data->code }}
@endsection

@section('content')
    <div class="po_component" id="formApp">

        @if($data->order_status === 'received')
            <div class="po-received-banner">
                <i class="fas fa-exclamation-triangle"></i>
                This order has already been <strong>confirmed / received</strong>.
                @if($isAdmin)
                    As admin, you can still edit it — stocks and accounting will be rewound and re-applied on save.
                @endif
            </div>
        @endif

        <!-- Page Header -->
        <div class="po-header">
            <div class="po-header-left">
                <div class="po-header-icon"><i class="fas fa-edit"></i></div>
                <div>
                    <h4>Edit Purchase Order</h4>
                    <span>{{ $data->code }} &middot; {{ ucfirst($data->order_status) }}</span>
                </div>
            </div>
            <a href="{{ route('ViewAllPurchaseProductOrder') }}" class="po-btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <form action="{{ url('update/purchase-product/order/v2') }}" method="POST" id="purchaseForm"
            @submit.prevent="onFormSubmit">
            @csrf
            <input type="hidden" name="purchase_product_order_id" value="{{ $data->id }}">

            <!-- ── SECTION 1: Order Details ── -->
            <div class="po-card">
                <div class="po-card-header">
                    <div class="po-card-header-left">
                        <i class="fas fa-file-alt"></i> Order Details
                    </div>
                    <span style="font-size:11px;color:var(--g400);">Order #{{ $data->code }}</span>
                </div>
                <div class="po-card-body">
                    <div class="po-grid-5">
                        <div class="po-fg">
                            <label class="po-label"><i class="fas fa-tag"></i> Status</label>
                            <input type="text" class="po-input" value="{{ ucfirst($data->order_status) }}" readonly>
                        </div>
                        <div class="po-fg">
                            <label class="po-label"><i class="fas fa-warehouse"></i> Warehouse <span class="req">*</span></label>
                            <select name="purchase_product_warehouse_id" class="po-select" required
                                v-model="selectedWarehouse" @change="getRooms">
                                <option value="">Select Warehouse</option>
                                @foreach ($productWarehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ $data->product_warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="po-fg">
                            <label class="po-label"><i class="fas fa-truck"></i> Supplier <span class="req">*</span></label>
                            <select name="supplier_id" class="po-select" data-toggle="select2" required>
                                <option value="">Select Supplier</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ $data->product_supplier_id == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="po-fg">
                            <label class="po-label"><i class="fas fa-calendar"></i> Purchase Date <span class="req">*</span></label>
                            <input type="date" name="purchase_date" class="po-input"
                                value="{{ $data->date ? \Carbon\Carbon::parse($data->date)->format('Y-m-d') : now()->format('Y-m-d') }}"
                                required>
                        </div>
                        <div class="po-fg">
                            <label class="po-label"><i class="fas fa-hashtag"></i> Reference</label>
                            <input type="text" name="reference" class="po-input"
                                value="{{ $data->reference }}" placeholder="Optional reference">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECTION 2: Product Search + Variant Panel ── -->
            <div class="po-card">
                <div class="po-card-header">
                    <div class="po-card-header-left">
                        <i class="fas fa-search"></i> Add / Change Products
                    </div>
                </div>
                <div class="po-card-body">

                    <!-- Search input -->
                    <div class="po-fg" style="max-width:520px; margin-bottom:0;">
                        <label class="po-label"><i class="fas fa-box"></i> Search Product</label>
                        <div class="po-search-wrap">
                            <input type="text" class="po-input" v-model="searchQuery" @keyup="getData"
                                @focus="dropdownVisible = searchResults.length > 0"
                                placeholder="Type product name, code, SKU or barcode…">
                            <div v-if="dropdownVisible && searchResults.length > 0" class="po-search-dropdown">
                                <div v-if="loadingMore" class="po-search-loading">
                                    <i class="fas fa-circle-notch fa-spin"></i> Searching…
                                </div>
                                <div v-for="product in searchResults" :key="product.id" class="po-search-item"
                                    @click="selectProduct(product)">
                                    <div class="po-search-item-img">
                                        <img v-if="product.image_url" :src="product.image_url" :alt="product.name">
                                        <i v-else class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="po-search-item-name">@{{ product.name }}</div>
                                        <div class="po-search-item-meta">
                                            @{{ product.sku || product.code || 'No SKU' }} &middot; Stock: @{{ product.stock }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Variant selection panel -->
                    <div v-if="pendingProduct && pendingProduct.has_variants" class="po-variant-panel">
                        <div class="po-variant-title">
                            <i class="fas fa-th-list"></i>
                            Select Variant — <span style="font-weight:400;text-transform:none;letter-spacing:0;">@{{ pendingProduct.name }}</span>
                        </div>

                        <div class="po-attr-grid">
                            <div v-for="attr in getPendingVariantAttributes()" :key="attr" class="po-attr-fg">
                                <label class="po-attr-label">@{{ attr }}</label>
                                <select class="po-attr-select" v-model="pendingVariantSelections[attr]"
                                    @change="onPendingVariantChange">
                                    <option value="">— @{{ attr }}</option>
                                    <option v-for="val in pendingProduct.product_variants[attr]" :key="val" :value="val">
                                        @{{ val }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="getPendingCombinationLabel()">
                            <div v-if="pendingMatchedVariant" class="po-comb-strip">
                                <span class="po-comb-label"><i class="fas fa-check-circle" style="color:var(--ok);"></i> Match:</span>
                                <span v-for="(val, key) in pendingMatchedVariant" :key="key">
                                    <span v-if="key !== 'id'" class="po-comb-pill">@{{ key }}: @{{ val }}</span>
                                </span>
                            </div>
                            <div v-else class="po-no-match">
                                <i class="fas fa-exclamation-circle"></i> No matching combination for selected attributes.
                            </div>
                        </div>

                        <button type="button" class="po-add-btn" :disabled="!pendingMatchedVariant" @click="addProductToTable">
                            <i class="fas fa-plus"></i> Add to Order
                        </button>
                    </div>

                    <!-- Non-variant: just add button -->
                    <div v-if="pendingProduct && !pendingProduct.has_variants" style="margin-top:12px;">
                        <div class="po-comb-strip" style="display:inline-flex;">
                            <span class="po-comb-label">Selected:</span>
                            <span class="po-comb-pill">@{{ pendingProduct.name }}</span>
                            <span class="po-comb-pill" style="background:var(--g100);color:var(--g700);">Stock: @{{ pendingProduct.stock }}</span>
                        </div>
                        <button type="button" class="po-add-btn" style="margin-left:10px;" @click="addProductToTable">
                            <i class="fas fa-plus"></i> Add to Order
                        </button>
                    </div>

                    <!-- Loading state -->
                    <div v-if="loadingOrder" style="margin-top:12px;color:var(--g400);font-size:13px;">
                        <i class="fas fa-circle-notch fa-spin"></i> Loading existing order items…
                    </div>

                </div>
            </div>

            <!-- ── SECTION 3: Purchase Items Table ── -->
            <div class="po-card">
                <div class="po-card-header">
                    <div class="po-card-header-left">
                        <i class="fas fa-list"></i>
                        Order Items
                        <span v-if="purchaseItems.length > 0" class="po-bc-badge">@{{ purchaseItems.length }}</span>
                    </div>
                </div>
                <div class="po-card-body" style="padding:0;">
                    <div class="po-table-wrap">
                        <table class="po-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Room</th>
                                    <th>Cartoon</th>
                                    <th>Prev. Stock</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Disc %</th>
                                    <th>Tax %</th>
                                    <th>Total</th>
                                    <th>Barcodes</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="purchaseItems.length === 0 && !loadingOrder" class="po-empty-row">
                                    <td colspan="12">
                                        <div class="po-empty-row-icon"><i class="fas fa-inbox"></i></div>
                                        <div>No products added yet. Search and add products above.</div>
                                    </td>
                                </tr>
                                <tr v-for="(item, index) in purchaseItems" :key="item.rowKey">
                                    <!-- Hidden inputs -->
                                    <input type="hidden" :name="`product[${index}][id]`" v-model="item.product_id">
                                    <input type="hidden" :name="`product[${index}][variant_combination_id]`"
                                        :value="item.variant_combination_id">
                                    <input type="hidden" :name="`product[${index}][name]`" :value="item.name">
                                    <input type="hidden" :name="`product[${index}][display_name]`"
                                        :value="item.display_name">
                                    <input type="hidden" :name="`product[${index}][previous_stock]`"
                                        :value="item.previous_stock">
                                    <input type="hidden" :name="`product[${index}][barcodes]`"
                                        :value="JSON.stringify(item.barcodes || [])">
                                    <input type="hidden" :name="`product[${index}][unit_details]`"
                                        :value="JSON.stringify(item.unit_details || [])">

                                    <td style="color:var(--g400);font-size:11px;">@{{ index + 1 }}</td>
                                    <td class="po-product-cell">
                                        <div class="po-product-name">@{{ item.name }}</div>
                                        <div v-if="item.variant_combination_id" class="po-product-variant">
                                            <i class="fas fa-tag" style="font-size:9px;"></i>
                                            @{{ item.variantLabel }}
                                        </div>
                                    </td>
                                    <td>
                                        <select class="po-input po-select" style="min-width:110px;"
                                            :name="`product[${index}][warehouse_room_id]`"
                                            v-model="item.warehouse_room_id" @change="onRowRoomChange(item)"
                                            :disabled="!selectedWarehouse">
                                            <option value="">Room</option>
                                            <option v-for="room in rooms" :value="room.id" :key="`r-${room.id}`">
                                                @{{ room.title }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="po-input po-select" style="min-width:100px;"
                                            :name="`product[${index}][warehouse_cartoon_id]`"
                                            v-model="item.warehouse_cartoon_id" :disabled="!item.warehouse_room_id">
                                            <option value="">Cartoon</option>
                                            <option v-for="c in item.cartoonOptions" :value="c.id" :key="`c-${c.id}`">
                                                @{{ c.title }}</option>
                                        </select>
                                    </td>
                                    <td class="po-prev-stock">@{{ Number(item.previous_stock || 0).toFixed(0) }}</td>
                                    <td>
                                        <input type="number" class="po-input" style="width:68px;"
                                            :name="`product[${index}][quantities]`" v-model="item.quantity"
                                            @input="handleItemNumericInput(item, 'quantity', 'Quantity')" min="0" step="1">
                                    </td>
                                    <td>
                                        <input type="text" class="po-input" style="width:80px;"
                                            :name="`product[${index}][prices]`" v-model="item.price"
                                            @input="handleItemNumericInput(item, 'price', 'Unit Price')">
                                    </td>
                                    <td>
                                        <input type="text" class="po-input" style="width:55px;"
                                            :name="`product[${index}][discounts]`" v-model="item.discount"
                                            @input="handleItemNumericInput(item, 'discount', 'Discount')">
                                    </td>
                                    <td>
                                        <input type="text" class="po-input" style="width:55px;"
                                            :name="`product[${index}][taxes]`" v-model="item.tax"
                                            @input="handleItemNumericInput(item, 'tax', 'Tax')">
                                    </td>
                                    <td class="po-row-total">৳ @{{ getItemTotalPrice(item).toFixed(2) }}
                                        <input type="hidden" :name="`product[${index}][totals]`"
                                            :value="getItemTotalPrice(item)">
                                    </td>
                                    <td>
                                        <button type="button" class="po-barcode-btn" @click="openBarcodeModal(index)">
                                            <i class="fas fa-barcode"></i>
                                            Barcodes
                                            <span class="po-bc-count">@{{ (item.barcodes || []).filter(b => b).length }}</span>
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="po-del-btn" @click="removeRow(index)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ── SECTION 4: Summary & Note ── -->
            <div class="po-card">
                <div class="po-card-body">
                    <div class="po-bottom-grid">
                        <!-- Left: stats + note -->
                        <div>
                            <div class="po-stat-row">
                                <div class="po-stat-card">
                                    <div class="po-stat-label">Products</div>
                                    <div class="po-stat-val">@{{ totalProducts }}</div>
                                </div>
                                <div class="po-stat-card">
                                    <div class="po-stat-label">Total Qty</div>
                                    <div class="po-stat-val">@{{ totalQuantity }}</div>
                                </div>
                            </div>

                            <!-- Hidden other charges fields -->
                            @foreach ($other_charges_types as $key => $item)
                                <div style="display:none;">
                                    <input type="hidden" name="other_charges[{{ $key }}][title]"
                                        value="{{ $item->title }}">
                                    <input type="text"
                                        class="other_charges_amount other_charges_amount{{ $key }}"
                                        name="other_charges[{{ $key }}][amount]" value=""
                                        @keyup="calc_other_charges">
                                    <select class="other_charges_type other_charges_type{{ $key }}"
                                        name="other_charges[{{ $key }}][type]" @change="calc_other_charges">
                                        <option value="percent" {{ $item->type == 'percent' ? 'selected' : '' }}>Per%</option>
                                        <option value="fixed" {{ $item->type == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                    </select>
                                </div>
                            @endforeach

                            <div class="po-fg">
                                <label class="po-label"><i class="fas fa-sticky-note"></i> Note</label>
                                <textarea class="po-textarea" name="purchase_note" rows="3"
                                    placeholder="Optional purchase notes…">{{ $data->note }}</textarea>
                            </div>
                        </div>

                        <!-- Right: totals -->
                        <div>
                            <input type="hidden" name="subtotal_amt" :value="subtotal.toFixed(2)">
                            <input type="hidden" name="other_charges_amt" :value="other_charges_amt.toFixed(2)">
                            <input type="hidden" name="discount_to_all_amt" value="0">
                            <input type="hidden" name="discount_on_all" value="0">
                            <input type="hidden" name="discount_to_all_type" value="in_percentage">
                            <input type="hidden" name="total_round_off_amt" value="0">
                            <input type="hidden" name="grand_total_amt" :value="grand_total_amt.toFixed(2)">

                            <table class="po-totals-table">
                                <tr>
                                    <td>Subtotal</td>
                                    <td>৳ @{{ subtotal.toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td style="color:var(--g500);">Other Charges</td>
                                    <td style="color:var(--g500);">৳ @{{ other_charges_amt.toFixed(2) }}</td>
                                </tr>
                                <tr class="grand">
                                    <td>Grand Total</td>
                                    <td style="color:var(--t600);">৳ @{{ grand_total_amt.toFixed(2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="po-footer">
                    <a href="{{ route('ViewAllPurchaseProductOrder') }}" class="po-btn po-btn-ghost">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="button" id="submitPurchaseBtn" class="po-btn po-btn-primary"
                        :disabled="formSubmitting || loadingOrder" @click="submitPurchaseForm">
                        <span v-show="!formSubmitting"><i class="fas fa-save"></i>
                            {{ $data->order_status === 'received' ? 'Update & Re-apply' : 'Update Purchase' }}
                        </span>
                        <span v-show="formSubmitting" style="display:inline-flex;"><span class="po-spinner"></span> Updating…</span>
                    </button>
                </div>
            </div>

            <!-- Server validation errors -->
            <div v-if="formErrors.length" class="po-card po-card-body"
                style="margin-top:12px; border-left:4px solid #dc3545;">
                <ul class="mb-0" style="padding-left:1rem;">
                    <li v-for="(msg, idx) in formErrors" :key="idx" style="margin-bottom:4px;">@{{ msg }}</li>
                </ul>
            </div>
        </form>

        <!-- ══ BARCODE MODAL ══ -->
        <div v-if="barcodeModal.open" class="po-modal-overlay" @click.self="closeBarcodeModal">
            <div class="po-modal">
                <div class="po-modal-header">
                    <div class="po-modal-title-block">
                        <p class="po-modal-title">
                            <i class="fas fa-barcode" style="color:var(--t500);margin-right:6px;"></i>
                            Barcode Entry
                        </p>
                        <p class="po-modal-subtitle" v-if="barcodeModal.item">
                            @{{ barcodeModal.item.display_name }}
                        </p>
                    </div>
                    <button type="button" class="po-modal-close" @click="closeBarcodeModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="po-modal-body" v-if="barcodeModal.item">

                    <!-- Variant label (read-only for pre-loaded items) -->
                    <div v-if="barcodeModal.item.variant_combination_id" style="display:flex;align-items:center;gap:6px;padding:8px 12px;background:var(--t50);border:1.5px solid var(--t100);border-radius:var(--r);margin-bottom:14px;">
                        <i class="fas fa-tag" style="color:var(--t600);"></i>
                        <span style="font-size:11px;color:var(--t700);font-weight:700;">Variant:</span>
                        <span style="background:var(--t100);color:var(--t800);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;">
                            @{{ barcodeModal.item.variantLabel }}
                        </span>
                    </div>

                    <!-- Scan + Qty row -->
                    <div class="po-modal-scan-row">
                        <div class="po-fg">
                            <label class="po-label">
                                <i class="fas fa-barcode"></i> Scan / Type Barcode
                            </label>
                            <input type="text" class="po-input" ref="modalBarcodeInput"
                                v-model="barcodeModal.scanEntry" @keydown.enter.prevent.stop="addModalBarcode"
                                placeholder="Scan or type → Enter to add">
                        </div>
                        <div class="po-fg">
                            <label class="po-label">
                                <i class="fas fa-hashtag"></i> Qty
                            </label>
                            <input type="number" class="po-input" min="0" step="1"
                                v-model.number="barcodeModal.qty" @input="onModalQtyChange" placeholder="0">
                        </div>
                    </div>

                    <div v-if="barcodeModal.qty > 0" class="po-modal-scan-row" style="margin-top:10px;">
                        <div class="po-fg">
                            <label class="po-label">
                                <i class="fas fa-clone"></i> Common barcode
                            </label>
                            <input type="text" class="po-input"
                                v-model="barcodeModal.commonBarcode"
                                @keydown.enter.prevent="applyCommonModalBarcode"
                                placeholder="Enter barcode → Set to all rows">
                        </div>
                        <div class="po-fg" style="display:flex;align-items:flex-end;">
                            <button type="button" class="po-btn po-btn-primary" style="width:100%;"
                                @click="applyCommonModalBarcode">
                                <i class="fas fa-fill-drip"></i> Set barcode
                            </button>
                        </div>
                    </div>

                    <div v-if="barcodeModal.qty > 0" class="po-hint"
                        style="display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                        <label style="margin:0;font-weight:700;color:var(--g600);">
                            <input type="checkbox" v-model="barcodeModal.hasSerial"
                                @change="updateUnitDetailFlag('hasSerial', barcodeModal.hasSerial)">
                            Has Serial
                        </label>
                        <label style="margin:0;font-weight:700;color:var(--g600);">
                            <input type="checkbox" v-model="barcodeModal.hasImei"
                                @change="updateUnitDetailFlag('hasImei', barcodeModal.hasImei)">
                            Has IMEI
                        </label>
                        <label style="margin:0;font-weight:700;color:var(--g600);">
                            <input type="checkbox" v-model="barcodeModal.hasWarranty"
                                @change="updateUnitDetailFlag('hasWarranty', barcodeModal.hasWarranty)">
                            Has Warranty
                        </label>
                    </div>

                    <div v-if="barcodeModal.qty > 0 && barcodeModal.hasSerial" class="po-modal-scan-row" style="margin-top:10px;">
                        <div class="po-fg">
                            <label class="po-label">Serial prefix</label>
                            <input type="text" class="po-input" v-model="barcodeModal.serialPrefix"
                                placeholder="Example: SN-">
                        </div>
                        <div class="po-fg">
                            <label class="po-label">Start number</label>
                            <input type="number" min="1" class="po-input" v-model.number="barcodeModal.serialStart"
                                placeholder="1">
                        </div>
                        <div class="po-fg" style="display:flex;align-items:flex-end;">
                            <button type="button" class="po-btn po-btn-primary" style="width:100%;"
                                @click="generateSerialSequence">
                                <i class="fas fa-list-ol"></i> Apply serial sequence
                            </button>
                        </div>
                    </div>

                    <div v-if="barcodeModal.qty > 0 && barcodeModal.hasWarranty" class="po-modal-scan-row" style="margin-top:10px;">
                        <div class="po-fg">
                            <label class="po-label">Warranty start</label>
                            <input type="date" class="po-input" v-model="barcodeModal.commonWarrantyStart">
                        </div>
                        <div class="po-fg">
                            <label class="po-label">Warranty end</label>
                            <input type="date" class="po-input" v-model="barcodeModal.commonWarrantyEnd">
                        </div>
                        <div class="po-fg">
                            <label class="po-label">Warranty note</label>
                            <input type="text" class="po-input" v-model="barcodeModal.commonWarrantyNote"
                                placeholder="Optional note">
                        </div>
                        <div class="po-fg" style="display:flex;align-items:flex-end;">
                            <button type="button" class="po-btn po-btn-primary" style="width:100%;"
                                @click="applyCommonWarranty">
                                <i class="fas fa-calendar-check"></i> Apply warranty to all
                            </button>
                        </div>
                    </div>

                    <div class="po-hint">
                        <i class="fas fa-info-circle"></i>
                        Scan barcodes one-by-one (qty auto-increments), or enter qty to pre-fill rows.
                    </div>

                    <!-- Barcode list -->
                    <div v-if="barcodeModal.qty > 0" class="po-bc-table-wrap" style="margin-top:10px;">
                        <table class="po-bc-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Barcode <span class="po-bc-badge">@{{ barcodeModal.qty }}</span></th>
                                    <th v-if="barcodeModal.hasSerial">Serial</th>
                                    <th v-if="barcodeModal.hasImei">IMEI 1</th>
                                    <th v-if="barcodeModal.hasImei">IMEI 2</th>
                                    <th v-if="barcodeModal.hasWarranty">Supplier Warranty Start</th>
                                    <th v-if="barcodeModal.hasWarranty">Supplier Warranty End</th>
                                    <th v-if="barcodeModal.hasWarranty">Note</th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(unit, i) in barcodeModal.unitDetails" :key="i">
                                    <td>@{{ i + 1 }}</td>
                                    <td>
                                        <input type="text" class="po-input" v-model="unit.barcode"
                                            placeholder="Enter or scan barcode">
                                    </td>
                                    <td v-if="barcodeModal.hasSerial">
                                        <input type="text" class="po-input" v-model="unit.serial_no"
                                            placeholder="Serial no">
                                    </td>
                                    <td v-if="barcodeModal.hasImei">
                                        <input type="text" class="po-input" v-model="unit.imei_1"
                                            placeholder="IMEI 1">
                                    </td>
                                    <td v-if="barcodeModal.hasImei">
                                        <input type="text" class="po-input" v-model="unit.imei_2"
                                            placeholder="IMEI 2">
                                    </td>
                                    <td v-if="barcodeModal.hasWarranty">
                                        <input type="date" class="po-input"
                                            v-model="unit.supplier_warranty_start_date">
                                    </td>
                                    <td v-if="barcodeModal.hasWarranty">
                                        <input type="date" class="po-input"
                                            v-model="unit.supplier_warranty_end_date">
                                    </td>
                                    <td v-if="barcodeModal.hasWarranty">
                                        <input type="text" class="po-input" v-model="unit.warranty_note"
                                            placeholder="Warranty note">
                                    </td>
                                    <td>
                                        <button type="button" class="po-btn po-btn-outline-danger po-btn-sm"
                                            @click="removeModalBarcode(i)" title="Remove barcode">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="po-modal-footer">
                    <button type="button" class="po-btn po-btn-ghost" @click="clearModalBarcodes"
                        style="margin-right:auto;">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                    <button type="button" class="po-btn po-btn-ghost" @click="closeBarcodeModal">
                        Cancel
                    </button>
                    <button type="button" class="po-btn po-btn-primary" @click="saveBarcodeModal">
                        <i class="fas fa-check"></i> Apply Barcodes
                    </button>
                </div>
            </div>
        </div>

    </div><!-- end po_component -->
@endsection

@section('footer_js')
    <script src="{{ versioned_url('assets/plugins/select2/select2.min.js') }}"></script>
@endsection
