@extends('backend.master')

@section('header_css')
@endsection

@section('page_title')
    Dashboard
@endsection

@section('page_heading')
    Overview
@endsection

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    @php
        $upcomingSupplierChequesCount = 0;
        $upcomingSupplierChequesAmount = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('supplier_cheque_payments')) {
            $upcomingSupplierChequesCount = \App\Http\Controllers\Account\Models\SupplierChequePayment::where(
                'status',
                'pending',
            )
                ->whereBetween('execution_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count();
            $upcomingSupplierChequesAmount = \App\Http\Controllers\Account\Models\SupplierChequePayment::where(
                'status',
                'pending',
            )
                ->whereBetween('execution_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->sum('amount');
        }
    @endphp

    <style>
        :root {
            --teal: #0d9488;
            --teal-light: #14b8a6;
            --teal-dark: #0f766e;
            --teal-glow: rgba(13, 148, 136, .18);
            --bg: #f0f2f7;
            --card-bg: #ffffff;
            --text: #1a2332;
            --muted: #6b7a99;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
            --blue: #3b82f6;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── SIDEBAR ─────────────────────────────────────────── */
        .sidebar {
            width: 64px;
            height: 100vh;
            background: linear-gradient(175deg, #0d9488 0%, #064e3b 100%);
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            gap: 6px;
            z-index: 100;
            box-shadow: 4px 0 24px rgba(13, 148, 136, .25);
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, .2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, .25);
        }

        .nav-item {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: rgba(255, 255, 255, .55);
            font-size: 16px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 22px;
            background: #fff;
            border-radius: 0 4px 4px 0;
        }

        .nav-badge {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
            border: 1.5px solid #0d9488;
        }

        .sidebar-bottom {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        /* ── LAYOUT ─────────────────────────────────────────── */
        .layout {
            margin-left: 0px;
        }

        /* ── TOPBAR ─────────────────────────────────────────── */
        .topbar {
            background: #fff;
            min-height: 62px;
            height: auto;
            flex-wrap: wrap;
            display: flex;
            align-items: center;
            padding: 10px 28px;
            gap: 16px;
            border-bottom: 1px solid #e8edf5;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
        }

        .topbar-brand {
            font-family: sans-serif;
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--teal), #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -.5px;
        }

        .topbar-divider {
            width: 1px;
            height: 28px;
            background: #e8edf5;
        }

        .store-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: #f0faf9;
            border: 1px solid #99f6e4;
            border-radius: 20px;
            padding: 4px 12px 4px 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--teal-dark);
        }

        .store-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1)
            }

            50% {
                opacity: .6;
                transform: scale(1.3)
            }
        }

        .date-tabs {
            display: flex;
            gap: 2px;
            background: #f0f2f7;
            border-radius: 10px;
            padding: 3px;
            margin-left: auto;
        }

        .date-tab {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            color: var(--muted);
            transition: all .2s;
            border: none;
            background: none;
            white-space: nowrap;
        }

        .date-tab.active {
            background: #fff;
            color: var(--teal);
            box-shadow: 0 1px 8px rgba(0, 0, 0, .08);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f5f7fb;
            border: 1px solid #e8edf5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 14px;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }

        .icon-btn:hover {
            background: #e8f5f3;
            color: var(--teal);
            border-color: #99f6e4;
        }

        .notif-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 7px;
            height: 7px;
            background: var(--danger);
            border-radius: 50%;
            border: 1.5px solid #f5f7fb;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--teal), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
        }

        /* ── MAIN ────────────────────────────────────────────── */
        .main {
            padding: 24px 28px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }

        .page-title {
            font-family: sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: var(--text);
        }

        .page-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--teal), var(--teal-dark));
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all .2s;
            box-shadow: 0 4px 14px rgba(13, 148, 136, .35);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, .45);
        }

        /* ── KPI CARDS ───────────────────────────────────────── */
        .kpi-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }

        .kpi-card {
            max-width: 230px;
            min-width: 170px;
            flex: 1 1 170px;
            border-radius: 18px;
            padding: 20px 18px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: transform .25s, box-shadow .25s;
            animation: fadeUp .5s both;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, .14);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .kpi-card:nth-child(1) {
            animation-delay: .05s
        }

        .kpi-card:nth-child(2) {
            animation-delay: .10s
        }

        .kpi-card:nth-child(3) {
            animation-delay: .15s
        }

        .kpi-card:nth-child(4) {
            animation-delay: .20s
        }

        .kpi-card:nth-child(5) {
            animation-delay: .25s
        }

        .kpi-card:nth-child(6) {
            animation-delay: .30s
        }

        /* gradient variants */
        .g-teal {
            background: linear-gradient(135deg, #0d9488 0%, #0e7490 100%);
            color: #fff;
        }

        .g-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);
            color: #fff;
        }

        .g-emerald {
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            color: #fff;
        }

        .g-amber {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            color: #fff;
        }

        .g-purple {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            color: #fff;
        }

        .g-slate {
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
            color: #fff;
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, .2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            opacity: .75;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-family: sans-serif;
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .kpi-change {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, .2);
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 11px;
            font-weight: 700;
        }

        .kpi-sub {
            font-size: 10px;
            opacity: .65;
            margin-top: 6px;
        }

        .kpi-deco {
            position: absolute;
            right: -18px;
            bottom: -18px;
            width: 90px;
            height: 90px;
            background: rgba(255, 255, 255, .08);
            border-radius: 50%;
        }

        .kpi-deco2 {
            position: absolute;
            right: 20px;
            bottom: -30px;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, .06);
            border-radius: 50%;
        }

        /* ── SECTION TITLE ───────────────────────────────────── */
        .sec-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .sec-title {
            font-family: sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sec-title i {
            color: var(--teal);
            font-size: 14px;
        }

        .sec-link {
            font-size: 12px;
            font-weight: 600;
            color: var(--teal);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .sec-link:hover {
            text-decoration: underline;
        }

        /* ── CARD BASE ───────────────────────────────────────── */
        .card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            border: 1px solid #edf0f7;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .04);
            transition: box-shadow .2s;
        }

        .card:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, .08);
        }

        /* ── CHARTS ROW ─────────────────────────────────────── */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .charts-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .chart-wrap {
            position: relative;
            height: 240px;
        }

        /* ── ORDER STATUS CARDS ─────────────────────────────── */
        .status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 22px;
        }

        .status-card {
            flex: 1 1 130px;
            max-width: 230px;
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            border: 1px solid #edf0f7;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
            cursor: pointer;
            transition: all .2s;
            animation: fadeUp .5s both;
        }

        .status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .1);
        }

        .status-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .status-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .status-pct {
            font-size: 11px;
            font-weight: 700;
            border-radius: 20px;
            padding: 2px 8px;
        }

        .status-count {
            font-family: sans-serif;
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
        }

        .status-label {
            font-size: 11px;
            color: var(--muted);
            margin-top: 3px;
            font-weight: 500;
        }

        .status-bar {
            height: 4px;
            border-radius: 4px;
            background: #f0f2f7;
            margin-top: 10px;
            overflow: hidden;
        }

        .status-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width .8s cubic-bezier(.4, 0, .2, 1);
        }

        /* ── TABLES ──────────────────────────────────────────── */
        .table-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr th {
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid #f0f2f7;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #f8f9fc;
            transition: background .15s;
            cursor: pointer;
        }

        tbody tr:hover {
            background: #f8fffe;
        }

        tbody tr td {
            padding: 10px 12px;
            font-size: 12.5px;
            vertical-align: middle;
        }

        .td-id {
            font-family: monospace;
            font-size: 11.5px;
            color: var(--teal);
            font-weight: 700;
        }

        .td-name {
            font-weight: 600;
            color: var(--text);
        }

        .td-sub {
            font-size: 10px;
            color: var(--muted);
            margin-top: 1px;
        }

        .td-amount {
            font-weight: 700;
            font-size: 13px;
            color: var(--text);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 700;
        }

        .badge-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge-info {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .badge-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge-purple {
            background: #f5f3ff;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }

        .stock-ok {
            color: var(--success);
            font-weight: 700;
            font-size: 11px;
        }

        .stock-low {
            color: var(--warning);
            font-weight: 700;
            font-size: 11px;
        }

        .stock-critical {
            color: var(--danger);
            font-weight: 700;
            font-size: 11px;
        }

        .action-btn {
            padding: 4px 10px;
            border-radius: 7px;
            border: none;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
        }

        .action-btn-primary {
            background: linear-gradient(135deg, var(--teal), var(--teal-dark));
            color: #fff;
        }

        .action-btn-ghost {
            background: #f0f2f7;
            color: var(--muted);
        }

        .action-btn:hover {
            filter: brightness(1.1);
        }

        /* ── FUNNEL ─────────────────────────────────────────── */
        .funnel-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        @media(max-width:1200px) {
            .funnel-row {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .funnel-card {
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
        }

        .funnel-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, .12);
        }

        .funnel-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            opacity: .75;
            margin-bottom: 6px;
        }

        .funnel-val {
            font-family: sans-serif;
            font-size: 30px;
            font-weight: 800;
        }

        .funnel-sub {
            font-size: 12px;
            opacity: .75;
            margin-top: 4px;
        }

        .funnel-bar {
            margin-top: 14px;
            height: 6px;
            background: rgba(255, 255, 255, .25);
            border-radius: 6px;
            overflow: hidden;
        }

        .funnel-bar-fill {
            height: 100%;
            border-radius: 6px;
            background: rgba(255, 255, 255, .7);
        }

        /* ── BOTTOM ROW ──────────────────────────────────────── */
        .bottom-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        /* ── EXPENSE TABLE ───────────────────────────────────── */
        .exp-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid #f0f2f7;
        }

        .exp-row:last-child {
            border-bottom: none;
        }

        .exp-name {
            flex: 1;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text);
        }

        .exp-amt {
            font-weight: 700;
            font-size: 13px;
            color: var(--text);
            min-width: 55px;
            text-align: right;
        }

        .exp-pct {
            font-size: 11px;
            color: var(--muted);
            min-width: 40px;
            text-align: right;
        }

        .exp-bar-wrap {
            width: 90px;
            height: 6px;
            background: #f0f2f7;
            border-radius: 6px;
            overflow: hidden;
        }

        .exp-bar-fill {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(90deg, var(--teal), #3b82f6);
            transition: width .8s;
        }

        /* ── INSIGHT CARD ────────────────────────────────────── */
        .insight-card {
            background: linear-gradient(135deg, #0d9488, #0e7490);
            color: #fff;
            border-radius: 16px;
            padding: 20px;
        }

        .insight-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .insight-title {
            font-family: sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .insight-body {
            font-size: 12px;
            opacity: .8;
            line-height: 1.6;
        }

        .insight-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, .2);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .insight-btn:hover {
            background: rgba(255, 255, 255, .3);
        }

        /* ── CUSTOMER RATIO ──────────────────────────────────── */
        .cust-ratio {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cust-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cust-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .cust-info {
            flex: 1;
        }

        .cust-name {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text);
        }

        .cust-sub {
            font-size: 11px;
            color: var(--muted);
        }

        .cust-num {
            font-family: sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--teal);
        }

        /* ── MONTHLY TABLE ───────────────────────────────────── */
        .monthly-table-wrap {
            overflow-x: auto;
        }

        .monthly-table-wrap table thead th {
            background: #f8f9fc;
        }

        .monthly-table-wrap table tbody td {
            white-space: nowrap;
        }

        .col-pos {
            color: var(--success);
            font-weight: 700;
        }

        .col-neg {
            color: var(--danger);
            font-weight: 700;
        }

        .col-head {
            background: #e8f5f3 !important;
            color: var(--teal-dark) !important;
        }

        .col-head2 {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }

        /* ── COURIER STRIP ───────────────────────────────────── */
        .courier-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .courier-card {
            flex: 1 1 130px;
            max-width: 230px;
            background: #fff;
            border-radius: 14px;
            padding: 15px 16px;
            border: 1px solid #edf0f7;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all .2s;
        }

        .courier-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
        }

        .courier-ico {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .courier-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
        }

        .courier-stat {
            font-size: 10.5px;
            color: var(--muted);
            margin-top: 2px;
        }

        .courier-cnt {
            font-family: sans-serif;
            font-size: 20px;
            font-weight: 800;
            color: var(--teal);
            margin-left: auto;
        }

        .ecom-panel {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        .ecom-card-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .ecom-metric {
            background: #fff;
            border: 1px solid #edf0f7;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        }

        .ecom-metric-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .ecom-metric-value {
            font-family: sans-serif;
            color: var(--text);
            font-size: 24px;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 6px;
        }

        .ecom-metric-sub {
            color: var(--muted);
            font-size: 11px;
            margin-top: 5px;
        }

        .ecom-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .ecom-status {
            background: #fff;
            border: 1px solid #edf0f7;
            border-radius: 12px;
            padding: 12px;
        }

        .ecom-status-head {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
        }

        .ecom-status-name {
            color: var(--text);
            font-size: 12px;
            font-weight: 800;
        }

        .ecom-status-count {
            font-family: sans-serif;
            font-size: 21px;
            font-weight: 800;
            margin-top: 8px;
        }

        .ecom-status-value {
            color: var(--muted);
            font-size: 11px;
            margin-top: 3px;
        }

        .ecom-mini-table {
            width: 100%;
        }

        .ecom-mini-table td {
            padding: 9px 0;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
        }

        .ecom-order-code {
            color: var(--text);
            font-size: 12px;
            font-weight: 800;
        }

        .ecom-order-meta {
            color: var(--muted);
            font-size: 10.5px;
            margin-top: 2px;
        }

        /* ── SCROLLBAR ───────────────────────────────────────── */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        @media(max-width:900px) {
            .sidebar {
                display: none;
            }

            .layout {
                margin-left: 0;
            }

            .charts-row,
            .charts-row-3,
            .funnel-row,
            .table-grid,
            .ecom-panel,
            .bottom-row {
                grid-template-columns: 1fr;
            }

            .ecom-card-grid,
            .ecom-status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .topbar {
                padding: 0 14px;
            }

            .main {
                padding: 14px;
            }

            .date-tabs {
                /* display: none; */
            }
        }

        .date-custom-panel {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            width: 100%;
            flex-basis: 100%;
            margin-top: 0;
            padding: 10px 12px;
            background: var(--card-bg);
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .06);
            margin-bottom: 15px;
        }

        .date-custom-panel input[type="date"] {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
        }

        .kpi-error {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
        }
    </style>

    <!-- LAYOUT -->
    <div class="layout" id="dashboard-app">

        <!-- TOPBAR -->
        <header class="topbar">
            <span class="topbar-brand"><i class="fa-solid fa-store" style="font-size:16px;"></i> Analytics</span>
            <div class="topbar-divider"></div>
            <div class="store-badge"><span class="store-dot"></span> {{ auth()->user()->name }}</div>
            <div class="date-tabs">
                <button type="button" class="date-tab" v-bind:class="{ active: preset === 'today' && !showCustomPicker }"
                    v-on:click="setPreset('today')">Today</button>
                <button type="button" class="date-tab"
                    v-bind:class="{ active: preset === 'yesterday' && !showCustomPicker }"
                    v-on:click="setPreset('yesterday')">Yesterday</button>
                <button type="button" class="date-tab" v-bind:class="{ active: preset === 'week' && !showCustomPicker }"
                    v-on:click="setPreset('week')">This Week</button>
                <button type="button" class="date-tab"
                    v-bind:class="{ active: preset === 'last_30_days' && !showCustomPicker }"
                    v-on:click="setPreset('last_30_days')">Last 30 Days</button>
                <button type="button" class="date-tab"
                    v-bind:class="{ active: preset === 'this_month' && !showCustomPicker }"
                    v-on:click="setPreset('this_month')">This Month</button>
                <button type="button" class="date-tab" v-bind:class="{ active: showCustomPicker || preset === 'custom' }"
                    v-on:click="toggleCustom()">Custom ▾</button>
            </div>
            <div class="date-custom-panel" v-if="showCustomPicker" v-on:click.stop>
                <label style="font-size:12px;color:var(--muted);">From</label>
                <input type="date" v-model="customFrom" />
                <label style="font-size:12px;color:var(--muted);">To</label>
                <input type="date" v-model="customTo" />
                <button type="button" class="btn-primary" style="padding:6px 14px;font-size:13px;"
                    v-on:click="applyCustomRange">Apply</button>
            </div>
        </header>

        <!-- MAIN -->
        <main class="main">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="page-title"><i class="fa-solid fa-chart-pie"
                            style="color:var(--teal);font-size:18px;margin-right:8px;"></i>Business Overview</div>
                    <div class="page-sub"><i class="fa-regular fa-clock" style="margin-right:4px;"></i>
                        @{{ rangeSummary }} &nbsp;·&nbsp; Last updated: @{{ lastUpdatedLabel }}</div>
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="btn-primary"><i class="fa-solid fa-plus"></i> New Order</button>
                    <button class="btn-primary"
                        style="background:linear-gradient(135deg,#334155,#1e293b);box-shadow:0 4px 14px rgba(0,0,0,.2);">
                        <i class="fa-solid fa-download"></i> Export
                    </button>
                </div>
            </div>

            @if ($upcomingSupplierChequesCount > 0)
                <div
                    style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div>
                        <strong>Warning:</strong> {{ $upcomingSupplierChequesCount }} supplier
                        cheque{{ $upcomingSupplierChequesCount > 1 ? 's' : '' }}
                        will execute within the next 7 days.
                        <span style="margin-left:10px;">Total Amount:
                            ৳{{ number_format($upcomingSupplierChequesAmount, 2) }}</span>
                    </div>
                    <a href="{{ route('supplier-cheque-payments.index', ['filter' => 'upcoming']) }}" class="btn-primary"
                        style="text-decoration:none;">View Details</a>
                </div>
            @endif

            <!-- At a glance CARDS -->
            <div class="kpi-error" v-if="kpiError">@{{ kpiError }}</div>
            <div class="kpi-row" v-if="loading && !kpis.length" style="opacity:.65;">
                <div class="kpi-card g-teal" v-for="n in 7" v-bind:key="'sk-' + n" style="min-height:140px;">
                    <div class="kpi-label">Loading…</div>
                    <div class="kpi-value">—</div>
                </div>
            </div>
            <div class="kpi-row" v-else>
                <div class="kpi-card" v-bind:class="kpiCardClass(kpi.title)" v-for="kpi in kpis" v-bind:key="kpi.title">
                    <div class="kpi-deco"></div>
                    <div class="kpi-deco2"></div>
                    <div class="kpi-icon"><i class="fa-solid" v-bind:class="kpiIcon(kpi.title)"></i></div>
                    <div class="kpi-label">@{{ kpiLabel(kpi.title) }}</div>
                    <div class="kpi-value">@{{ formatKpiValue(kpi) }}</div>
                    <div class="kpi-change">
                        <i class="fa-solid" v-bind:class="trendIconClass(kpi.compared_trend)"></i>
                        @{{ formatTrendPercent(kpi) }}
                    </div>
                    <div class="kpi-sub">@{{ kpiSubline(kpi) }}</div>
                </div>

                <div class="kpi-card g-amber">
                    <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="kpi-label">Total Website Visitor</div>
                    <div class="kpi-value">@{{ formatInt(websiteVisitors.current || 0) }}</div>
                    <div class="kpi-change" v-if="websiteVisitors && websiteVisitors.compared">
                        <i class="fa-solid" v-bind:class="trendIconClass(websiteVisitors.compared.trend)"></i>
                        @{{ (Number(websiteVisitors.compared.percentage || 0) > 0 ? '+' : '') + Number(websiteVisitors.compared.percentage || 0).toFixed(1) + '%' }}
                    </div>
                    <div class="kpi-sub" v-if="websiteVisitors && websiteVisitors.compared">
                        Compared: @{{ formatInt(websiteVisitors.compared.value || 0) }}
                    </div>
                </div>
            </div>

            <!-- ORDER STATUS -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-truck-fast"></i> Order Status</div>
                <a class="sec-link" v-bind:href="ordersListUrl">View all orders <i
                        class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="status-row">
                <div class="status-card" v-for="(st, idx) in orderStatusesDisplay" v-bind:key="st.key"
                    v-bind:style="orderStatusCardAnim(idx, st.key)">
                    <div class="status-top">
                        <div class="status-icon"
                            v-bind:style="{ background: orderStatusTheme(st.key).iconBg, color: orderStatusTheme(st.key).iconColor }">
                            <i class="fa-solid" v-bind:class="orderStatusFaIcon(st.key)"></i>
                        </div>
                        <span class="status-pct"
                            v-bind:style="{ background: orderStatusTheme(st.key).pctBg, color: orderStatusTheme(st.key).pctColor }">@{{ st.pct }}%</span>
                    </div>
                    <div class="status-count" v-bind:style="orderStatusCountStyle(st.key)">@{{ st.count }}</div>
                    <div class="status-label">@{{ st.label }}</div>
                    <div class="status-bar">
                        <div class="status-bar-fill"
                            v-bind:style="{ width: orderStatusBarWidth(st.pct), background: orderStatusTheme(st.key).bar }">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ECOMMERCE ORDERS -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-bag-shopping"></i> Ecommerce Order Health</div>
                <a class="sec-link" v-bind:href="ecommerceOrdersUrl">View ecommerce orders <i
                        class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="ecom-panel">
                <div>
                    <div class="ecom-card-grid">
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Incoming Orders</div>
                            <div class="ecom-metric-value">@{{ formatInt(ecommerceSummary.total_orders) }}</div>
                            <div class="ecom-metric-sub">@{{ ecommerceSummary.acceptance_rate }}% accepted</div>
                        </div>
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Accepted Value</div>
                            <div class="ecom-metric-value">@{{ formatMoney(ecommerceSummary.accepted_value) }}</div>
                            <div class="ecom-metric-sub">@{{ formatInt(ecommerceSummary.accepted_orders) }} finance-counted orders</div>
                        </div>
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Courier COD</div>
                            <div class="ecom-metric-value">@{{ formatMoney(ecommerceSummary.cod_total) }}</div>
                            <div class="ecom-metric-sub">@{{ formatInt(ecommerceSummary.couriered_orders) }} couriered orders</div>
                        </div>
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Courier Cost</div>
                            <div class="ecom-metric-value col-neg">@{{ formatMoney(ecommerceSummary.courier_cost_total) }}</div>
                            <div class="ecom-metric-sub">Delivery media charge</div>
                        </div>
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Excluded Value</div>
                            <div class="ecom-metric-value col-neg">@{{ formatMoney(ecommerceSummary.excluded_value) }}</div>
                            <div class="ecom-metric-sub">Pending/cancelled/returned: @{{ ecommerceSummary.excluded_rate }}%</div>
                        </div>
                        <div class="ecom-metric">
                            <div class="ecom-metric-label">Settlement Pending</div>
                            <div class="ecom-metric-value">@{{ formatMoney(ecommerceSummary.settlement_pending) }}</div>
                            <div class="ecom-metric-sub">@{{ ecommerceSummary.settlement_rate }}% couriered settled</div>
                        </div>
                    </div>

                    <div class="ecom-status-grid">
                        <div class="ecom-status" v-for="st in ecommerceStatusDisplay" v-bind:key="st.key"
                            v-bind:style="{ borderColor: ecomStatusTheme(st.key).border }">
                            <div class="ecom-status-head">
                                <div class="status-icon"
                                    v-bind:style="{ background: ecomStatusTheme(st.key).bg, color: ecomStatusTheme(st.key).fg }">
                                    <i class="fa-solid" v-bind:class="st.icon"></i>
                                </div>
                                <span class="status-pct"
                                    v-bind:style="{ background: ecomStatusTheme(st.key).bg, color: ecomStatusTheme(st.key).fg }">@{{ st.pct }}%</span>
                            </div>
                            <div class="ecom-status-count" v-bind:style="{ color: ecomStatusTheme(st.key).fg }">
                                @{{ formatInt(st.count) }}</div>
                            <div class="ecom-status-name">@{{ st.label }}</div>
                            <div class="ecom-status-value">@{{ formatMoney(st.value) }}</div>
                            <div class="status-bar">
                                <div class="status-bar-fill"
                                    v-bind:style="{ width: orderStatusBarWidth(st.pct), background: ecomStatusTheme(st.key).fg }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card" style="padding:16px;">
                    <div class="sec-header" style="margin-bottom:8px;">
                        <div style="font-size:13px;font-weight:800;color:var(--text);">Recent Ecommerce Orders</div>
                        <span style="font-size:11px;color:var(--muted);"
                            v-if="ecommerceAnalyticsLoading">Loading...</span>
                    </div>
                    <table class="ecom-mini-table">
                        <tbody>
                            <tr v-if="!ecommerceRecentOrders.length">
                                <td colspan="2" style="color:var(--muted);font-size:12px;">No ecommerce orders in this
                                    range.</td>
                            </tr>
                            <tr v-for="row in ecommerceRecentOrders" v-bind:key="row.id">
                                <td>
                                    <div class="ecom-order-code">@{{ row.order_code }}</div>
                                    <div class="ecom-order-meta">@{{ row.customer_name }} · @{{ row.customer_phone || 'No phone' }}</div>
                                    <div class="ecom-order-meta">
                                        @{{ row.courier ? courierDisplayName(row.courier) : 'No courier' }}
                                        <span v-if="row.settled_from_courier"> · settled</span>
                                    </div>
                                </td>
                                <td style="text-align:right;">
                                    <div v-html="orderStatusBadge(row.order_status)"></div>
                                    <div class="ecom-order-meta"
                                        v-bind:class="row.finance_eligible ? 'col-pos' : 'col-neg'">
                                        @{{ row.finance_eligible ? 'Finance counted' : 'Finance excluded' }}
                                    </div>
                                    <div style="font-weight:800;margin-top:3px;">@{{ formatMoney(row.total) }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- COURIERS -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-motorcycle"></i> Courier Breakdown</div>
            </div>
            <div class="courier-row">
                <div class="courier-card" v-for="row in courierRowsDisplay" v-bind:key="row.courier_name">
                    <div class="courier-ico"
                        v-bind:style="{ background: courierTheme(row.courier_name).bg, color: courierTheme(row.courier_name).fg }">
                        <i class="fa-solid" v-bind:class="courierTheme(row.courier_name).icon"></i>
                    </div>
                    <div>
                        <div class="courier-name">@{{ courierDisplayName(row.courier_name) }}</div>
                        <div class="courier-stat">@{{ courierStatLine(row) }}</div>
                    </div>
                    <div class="courier-cnt">@{{ formatMoney(row.total_fee) }}</div>
                </div>
            </div>

            <!-- FUNNEL -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-filter"></i> Sales Funnel · Quotations → Conversions
                </div>
            </div>
            <div class="funnel-row" style="margin-bottom:22px;">
                <div class="funnel-card" style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;">
                    <div class="funnel-label"><i class="fa-solid fa-file-lines" style="margin-right:6px;"></i>Quotations
                        Created</div>
                    <div class="funnel-val">@{{ formatInt(quotationFunnelDisplay.created.count) }}</div>
                    <div class="funnel-sub">@{{ formatMoney(quotationFunnelDisplay.created.value) }} total value</div>
                    <div class="funnel-bar">
                        <div class="funnel-bar-fill" style="width:100%;"></div>
                    </div>
                </div>
                <div class="funnel-card" style="background:linear-gradient(135deg,#64748b,#475569);color:#fff;">
                    <div class="funnel-label"><i class="fa-solid fa-clock" style="margin-right:6px;"></i>Pending</div>
                    <div class="funnel-val">@{{ formatInt(quotationFunnelDisplay.pending.count) }} <span style="font-size:16px;opacity:.8">·
                            @{{ quotationFunnelDisplay.pending.pct_of_created }}%</span></div>
                    <div class="funnel-sub">@{{ formatMoney(quotationFunnelDisplay.pending.value) }} · of created</div>
                    <div class="funnel-bar">
                        <div class="funnel-bar-fill"
                            v-bind:style="{ width: funnelBarPct(quotationFunnelDisplay.pending.pct_of_created) }"></div>
                    </div>
                </div>
                <div class="funnel-card" style="background:linear-gradient(135deg,#d97706,#b45309);color:#fff;">
                    <div class="funnel-label"><i class="fa-solid fa-magnifying-glass" style="margin-right:6px;"></i>In
                        Review</div>
                    <div class="funnel-val">@{{ formatInt(quotationFunnelDisplay.in_review.count) }} <span style="font-size:16px;opacity:.8">·
                            @{{ quotationFunnelDisplay.in_review.pct_of_created }}%</span></div>
                    <div class="funnel-sub">@{{ formatMoney(quotationFunnelDisplay.in_review.value) }} · of created</div>
                    <div class="funnel-bar">
                        <div class="funnel-bar-fill"
                            v-bind:style="{ width: funnelBarPct(quotationFunnelDisplay.in_review.pct_of_created) }"></div>
                    </div>
                </div>
                <div class="funnel-card" style="background:linear-gradient(135deg,#059669,#0d9488);color:#fff;">
                    <div class="funnel-label"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i>Converted
                        to Orders</div>
                    <div class="funnel-val">@{{ formatInt(quotationFunnelDisplay.converted.count) }} <span style="font-size:16px;opacity:.8">·
                            @{{ quotationFunnelDisplay.converted.pct_of_created }}%</span></div>
                    <div class="funnel-sub">@{{ formatMoney(quotationFunnelDisplay.converted.value) }} · @{{ quotationFunnelDisplay.converted.pct_of_created }}% conversion rate</div>
                    <div class="funnel-bar">
                        <div class="funnel-bar-fill"
                            v-bind:style="{ width: funnelBarPct(quotationFunnelDisplay.converted.pct_of_created) }"></div>
                    </div>
                </div>
                <div class="funnel-card" style="background:linear-gradient(135deg,#dc2626,#9f1239);color:#fff;">
                    <div class="funnel-label"><i class="fa-solid fa-xmark" style="margin-right:6px;"></i>Lost
                        Quotations</div>
                    <div class="funnel-val">@{{ formatInt(quotationFunnelDisplay.lost.count) }}</div>
                    <div class="funnel-sub">@{{ formatMoney(quotationFunnelDisplay.lost.value) }} lost potential · @{{ quotationFunnelDisplay.lost.pct_of_created }}%</div>
                    <div class="funnel-bar">
                        <div class="funnel-bar-fill"
                            v-bind:style="{ width: funnelBarPct(quotationFunnelDisplay.lost.pct_of_created) }"></div>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW 1 -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-chart-area"></i> Revenue & Sales Analytics</div>
            </div>
            <div style="margin:-6px 0 14px; font-size:12px; color:var(--muted);">
                Revenue is the total of item sales (sum of <b>total_price</b>) for <b>invoiced</b> and <b>delivered</b>
                orders. Profit is the stored per-item <b>net_profit</b> (can be negative when selling below purchase price).
            </div>
            <div class="charts-row">
                <div class="card">
                    <div class="sec-header" style="margin-bottom:12px;">
                        <div style="font-size:13px;font-weight:700;color:var(--text);">Revenue Trend <span
                                style="font-size:11px;color:var(--muted);font-weight:400;">(Daily)</span></div>
                        <span v-bind:style="revenueTrendBadgeStyle"
                            style="font-size:11px;border-radius:20px;padding:3px 9px;font-weight:700;border:1px solid transparent;"><i
                                class="fa-solid" v-bind:class="revenueTrendIcon"></i> @{{ revenueTrendPctLabel }}</span>
                    </div>
                    <div class="chart-wrap"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="card">
                    <div class="sec-header" style="margin-bottom:12px;">
                        <div style="font-size:13px;font-weight:700;color:var(--text);">Sales vs Expenses vs Profit
                        </div>
                    </div>
                    <div class="chart-wrap"><canvas id="revExpChart"></canvas></div>
                </div>
            </div>

            <!-- CHARTS ROW 2 -->
            <div class="charts-row-3">
                <div class="card">
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Top 10 Products by Revenue</div>
                    <div class="chart-wrap"><canvas id="productChart"></canvas></div>
                </div>
                <div class="card">
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Payment Methods</div>
                    <div class="chart-wrap"><canvas id="paymentChart"></canvas></div>
                </div>
                <div class="card">
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Orders by Category</div>
                    <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
                </div>
            </div>

            <!-- BOTTOM CHARTS -->
            <div class="charts-row">
                <div class="card">
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Orders Count VS Profit
                    </div>
                    <div class="chart-wrap"><canvas id="ordersAvgChart"></canvas></div>
                </div>
                <div class="card">
                    <div style="font-size:13px;font-weight:700;margin-bottom:12px;">Profit by Category</div>
                    <div class="chart-wrap"><canvas id="catRevenueChart"></canvas></div>
                </div>
            </div>

            <!-- TABLES -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-table-list"></i> Orders & Products</div>
            </div>
            <div class="table-grid">
                <!-- Pending Orders -->
                <div class="card" style="padding:0;">
                    <div
                        style="padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                            <div style="font-size:13px;font-weight:700;">Orders</div>
                            <button type="button" class="date-tab"
                                v-bind:class="{ active: ordersTableMode === 'high_value' }"
                                v-on:click="setOrdersMode('high_value')">
                                High Value (@{{ ordersCounts.high_value || 0 }})
                            </button>
                            <button type="button" class="date-tab"
                                v-bind:class="{ active: ordersTableMode === 'pending' }"
                                v-on:click="setOrdersMode('pending')">
                                Pending (@{{ ordersCounts.pending || 0 }})
                            </button>
                        </div>
                        <a class="sec-link" v-bind:href="ordersListUrl" style="font-size:11px;">View all <i
                                class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Days</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="ordersTableLoading">
                                    <td colspan="6" style="padding:16px;color:var(--muted);">Loading…</td>
                                </tr>
                                <tr v-else-if="!ordersTableRows.length">
                                    <td colspan="6" style="padding:16px;color:var(--muted);">No orders found.</td>
                                </tr>
                                <tr v-else v-for="row in ordersTableRows" v-bind:key="row.id">
                                    <td class="td-id">@{{ row.order_code }}</td>
                                    <td>
                                        <div class="td-name">@{{ row.customer_name }}</div>
                                    </td>
                                    <td class="td-amount">@{{ formatMoney(row.total) }}</td>
                                    <td v-html="orderStatusBadge(row.order_status)"></td>
                                    <td><span v-bind:style="daysStyle(row.created_at)">@{{ daysSince(row.created_at) }}d</span></td>
                                    <td>
                                        <div style="display:flex;gap:5px;">
                                            <a class="action-btn action-btn-ghost"
                                                v-bind:href="orderViewUrl(row.id)">View</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        style="padding:12px 16px;border-top:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                        <div style="font-size:12px;color:var(--muted);">
                            Showing @{{ ordersPagination.current_page }} / @{{ ordersPagination.last_page }} · Total @{{ ordersPagination.total }}
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" class="action-btn action-btn-ghost"
                                v-bind:disabled="ordersPagination.current_page <= 1 || ordersTableLoading"
                                v-on:click="gotoOrdersPage(ordersPagination.current_page - 1)">Prev</button>
                            <button type="button" class="action-btn action-btn-ghost"
                                v-bind:disabled="ordersPagination.current_page >= ordersPagination.last_page || ordersTableLoading"
                                v-on:click="gotoOrdersPage(ordersPagination.current_page + 1)">Next</button>
                        </div>
                    </div>
                </div>
                <!-- Trending Products -->
                <div class="card" style="padding:0;">
                    <div
                        style="padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;">
                        <div style="font-size:13px;font-weight:700;">Trending Products</div>
                        <a class="sec-link" href="#" style="font-size:11px;">View all <i
                                class="fa-solid fa-arrow-right"></i></a>
                    </div>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Rate</th>
                                    <th>Revenue</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="trendingProductsLoading">
                                    <td colspan="5" style="padding:16px;color:var(--muted);">Loading…</td>
                                </tr>
                                <tr v-else-if="!trendingProductsRows.length">
                                    <td colspan="5" style="padding:16px;color:var(--muted);">No products found.</td>
                                </tr>
                                <tr v-else v-for="row in trendingProductsRows" v-bind:key="row.product_id">
                                    <td>
                                        <div class="td-name">@{{ row.name }}</div>
                                    </td>
                                    <td style="font-weight:700;">@{{ row.total_sold }}</td>
                                    <td><span style="font-weight:800;">@{{ formatMoney(row.price) }}</span></td>
                                    <td style="font-weight:700;font-size:12px;">@{{ formatMoney(row.total_sales) }}</td>
                                    <td>
                                        <span v-if="row.stock > 10" class="stock-ok"><i
                                                class="fa-solid fa-circle-check"></i> OK</span>
                                        <span v-else-if="row.stock > 0" class="stock-low"><i
                                                class="fa-solid fa-triangle-exclamation"></i> Low</span>
                                        <span v-else class="stock-critical"><i class="fa-solid fa-circle-exclamation"></i>
                                            Critical</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        style="padding:12px 16px;border-top:1px solid #f0f2f7;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                        <div style="font-size:12px;color:var(--muted);">
                            Showing @{{ trendingProductsPagination.current_page }} / @{{ trendingProductsPagination.last_page }} · Total @{{ trendingProductsPagination.total }}
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <button type="button" class="action-btn action-btn-ghost"
                                v-bind:disabled="trendingProductsPagination.current_page <= 1 || trendingProductsLoading"
                                v-on:click="gotoTrendingProductsPage(trendingProductsPagination.current_page - 1)">Prev</button>
                            <button type="button" class="action-btn action-btn-ghost"
                                v-bind:disabled="trendingProductsPagination.current_page >= trendingProductsPagination.last_page || trendingProductsLoading"
                                v-on:click="gotoTrendingProductsPage(trendingProductsPagination.current_page + 1)">Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOTTOM ROW: CATEGORY + CUSTOMERS + EXPENSES -->
            <div class="bottom-row">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <!-- Category Revenue -->
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-layer-group"
                                style="color:var(--teal);margin-right:6px;"></i>Profit by Category</div>
                        <div>
                            <div v-if="categoryProfitLoading" style="color:var(--muted);font-size:12px;">Loading…</div>
                            <div v-else-if="!categoryProfitItems.length" style="color:var(--muted);font-size:12px;">No
                                data</div>
                            <div v-else>
                                <div v-for="c in categoryProfitItems" v-bind:key="c.category_id || c.category_name"
                                    style="margin-bottom:10px;">
                                    <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                                        <span
                                            style="font-size:12px;font-weight:600;color:#1a2332;display:flex;align-items:center;gap:6px;">
                                            <span
                                                style="width:8px;height:8px;border-radius:50%;background:var(--teal);display:inline-block;"></span>@{{ c.category_name }}
                                        </span>
                                        <span
                                            style="font-size:12px;font-weight:800;color:#1a2332;">@{{ formatMoney(c.profit) }}</span>
                                    </div>
                                    <div style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;">
                                        <div
                                            v-bind:style="{ height: '100%', width: Math.min(100, Math.max(0, Number(c.pct||0))) + '%', background: 'var(--teal)', borderRadius: '6px', transition: 'width .8s' }">
                                        </div>
                                    </div>
                                    <div
                                        style="display:flex;justify-content:space-between;margin-top:4px;color:var(--muted);font-size:11px;">
                                        <span>Share</span><span>@{{ Number(c.pct || 0).toFixed(1) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Expenses -->
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-receipt"
                                style="color:var(--teal);margin-right:6px;"></i>Expenses by Categories</div>
                        <div>
                            <div v-if="expensesByCategoryLoading" style="color:var(--muted);font-size:12px;">Loading…
                            </div>
                            <div v-else-if="!expensesByCategoryItems.length" style="color:var(--muted);font-size:12px;">No
                                data</div>
                            <div v-else>
                                <div v-for="e in expensesByCategoryItems" v-bind:key="e.category_id || e.category_name"
                                    class="exp-row">
                                    <div class="exp-name">@{{ e.category_name }}</div>
                                    <div class="exp-amt">@{{ formatMoney(e.expense) }}</div>
                                    <div class="exp-bar-wrap">
                                        <div class="exp-bar-fill"
                                            v-bind:style="{ width: Math.min(100, Math.max(0, Number(e.pct||0))) + '%' }">
                                        </div>
                                    </div>
                                    <div class="exp-pct">@{{ Number(e.pct || 0).toFixed(1) }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Right -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <!-- Customers -->
                    <div class="card">
                        <div style="font-size:13px;font-weight:700;margin-bottom:14px;"><i class="fa-solid fa-users"
                                style="color:var(--teal);margin-right:6px;"></i>Customer Breakdown</div>
                        <div class="cust-ratio">
                            <div class="cust-item">
                                <div class="cust-icon" style="background:#eff6ff;color:#3b82f6;"><i
                                        class="fa-solid fa-user-plus"></i></div>
                                <div class="cust-info">
                                    <div class="cust-name">New Customers</div>
                                    <div class="cust-sub">@{{ Number(customerBreakdownDisplay.new.pct || 0).toFixed(1) }}% · First-time buyers</div>
                                </div>
                                <div class="cust-num">@{{ formatInt(customerBreakdownDisplay.new.count || 0) }}</div>
                            </div>
                            <div
                                style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;margin:-6px 0 4px;">
                                <div
                                    v-bind:style="{ height:'100%', width: Math.min(100, Math.max(0, Number(customerBreakdownDisplay.new.pct||0))) + '%', background: 'linear-gradient(90deg,#3b82f6,#6366f1)', borderRadius:'6px' }">
                                </div>
                            </div>
                            <div class="cust-item">
                                <div class="cust-icon" style="background:#ecfdf5;color:#10b981;"><i
                                        class="fa-solid fa-rotate"></i></div>
                                <div class="cust-info">
                                    <div class="cust-name">Returning</div>
                                    <div class="cust-sub">@{{ Number(customerBreakdownDisplay.returning.pct || 0).toFixed(1) }}% · Repeat buyers</div>
                                </div>
                                <div class="cust-num" style="color:#10b981;">@{{ formatInt(customerBreakdownDisplay.returning.count || 0) }}</div>
                            </div>
                            <div
                                style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;margin:-6px 0 4px;">
                                <div
                                    v-bind:style="{ height:'100%', width: Math.min(100, Math.max(0, Number(customerBreakdownDisplay.returning.pct||0))) + '%', background: 'linear-gradient(90deg,#10b981,#0d9488)', borderRadius:'6px' }">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Insight -->
                    <div class="insight-card" v-bind:style="returnRateInsightCardStyle">
                        <div class="insight-icon"><i v-bind:class="returnRateInsightIconClass"></i></div>
                        <div class="insight-title">@{{ returnRateInsightTitle }}</div>
                        <div v-if="returnRateInsightLoading" class="insight-body">Loading return insight...</div>
                        <div v-else class="insight-body">
                            Return rate at @{{ Number(returnRateInsightDisplay.rate || 0).toFixed(1) }}% · target @{{ Number(returnRateInsightDisplay.target || 0).toFixed(1) }}%.
                            @{{ returnRateInsightDisplay.top_category.category_name }} is the top return category with
                            @{{ formatInt(returnRateInsightDisplay.top_category.returned_qty || 0) }} returned units.
                        </div>
                        <div v-if="!returnRateInsightLoading"
                            style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;">
                            <div v-for="stat in returnRateInsightStats" v-bind:key="stat.key"
                                style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);border-radius:8px;padding:8px;min-width:0;">
                                <div
                                    style="font-size:10px;opacity:.75;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    @{{ stat.label }}</div>
                                <div
                                    style="font-size:13px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    @{{ stat.value }}</div>
                            </div>
                        </div>
                        <a class="insight-btn" v-bind:href="returnOrdersListUrl">
                            <i class="fa-solid fa-arrow-right"></i> Investigate Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- MONTHLY SUMMARY TABLE -->
            <div class="sec-header">
                <div class="sec-title"><i class="fa-solid fa-table"></i> Monthly P&L Summary</div>
            </div>
            <div class="card monthly-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="col-head">Gross Sales</th>
                            <th class="col-head">Discounts</th>
                            <th class="col-head">Returns</th>
                            <th class="col-head">Net Sales</th>
                            <th class="col-head">Total Sales</th>
                            <th class="col-head2">Revenue</th>
                            <th class="col-head2">Direct Costs</th>
                            <th class="col-head2">Gross Profit</th>
                            <th class="col-head2">Gross Margin</th>
                            <th class="col-head2">Op. Expenses</th>
                            <th class="col-head2">Net Profit</th>
                            <th class="col-head2">Net Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="monthlyProfitLossLoading">
                            <td colspan="13" style="text-align:center;color:var(--muted);padding:18px;">Loading...</td>
                        </tr>
                        <tr v-else-if="!monthlyProfitLossRows.length">
                            <td colspan="13" style="text-align:center;color:var(--muted);padding:18px;">No data</td>
                        </tr>
                        <tr v-else v-for="(row, idx) in monthlyProfitLossRows" v-bind:key="row.month_key"
                            v-bind:style="idx === 0 ? { background: '#f0faf9', fontWeight: '700' } : {}">
                            <td style="font-weight:700;">@{{ row.month }}</td>
                            <td class="col-pos">@{{ formatMoney(row.gross_sales) }}</td>
                            <td class="col-neg">@{{ formatSignedMoney(row.discounts, true) }}</td>
                            <td class="col-neg">@{{ formatSignedMoney(row.returns, true) }}</td>
                            <td>@{{ formatMoney(row.net_sales) }}</td>
                            <td>@{{ formatMoney(row.total_sales) }}</td>
                            <td style="font-weight:700;">@{{ formatMoney(row.revenue) }}</td>
                            <td class="col-neg">@{{ formatSignedMoney(row.direct_costs, true) }}</td>
                            <td v-bind:class="moneyClass(row.gross_profit)">@{{ formatSignedMoney(row.gross_profit) }}</td>
                            <td>@{{ formatPercent(row.gross_margin) }}</td>
                            <td class="col-neg">@{{ formatSignedMoney(row.operating_expenses, true) }}</td>
                            <td v-bind:class="moneyClass(row.net_profit)">@{{ formatSignedMoney(row.net_profit) }}</td>
                            <td style="font-weight:700;">@{{ formatPercent(row.net_margin) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
    <script>
        (function() {
            var analyticsV2Url = @json(url('/api/analytics-v2/at-a-glance'));
            var courierBreakdownUrl = @json(url('/api/analytics-v2/courier-breakdown'));
            var quotationFunnelUrl = @json(url('/api/analytics-v2/quotation-funnel'));
            var revenueTrendUrl = @json(url('/api/analytics-v2/revenue-trend'));
            var revExpProfitUrl = @json(url('/api/analytics-v2/rev-exp-profit-trend'));
            var topProductsUrl = @json(url('/api/analytics-v2/top-products-by-sales'));
            var paymentMethodsUrl = @json(url('/api/analytics-v2/payment-methods'));
            var topCategoriesUrl = @json(url('/api/analytics-v2/top-categories-by-orders'));
            var ordersProfitUrl = @json(url('/api/analytics-v2/order-count-vs-profit'));
            var topCategoryProfitUrl = @json(url('/api/analytics-v2/top-categories-by-profit'));
            var categoryProfitShareUrl = @json(url('/api/analytics-v2/category-profit-share'));
            var expensesByCategoryUrl = @json(url('/api/analytics-v2/expenses-by-category'));
            var customerBreakdownUrl = @json(url('/api/analytics-v2/customer-breakdown'));
            var websiteVisitorsUrl = @json(url('/api/analytics-v2/website-visitors'));
            var returnRateInsightUrl = @json(url('/api/analytics-v2/return-rate-insight'));
            var monthlyProfitLossUrl = @json(url('/api/analytics-v2/monthly-profit-loss'));
            var ordersTableUrl = @json(url('/api/analytics-v2/orders-table'));
            var trendingProductsUrl = @json(url('/api/analytics-v2/trending-products'));
            var ecommerceOverviewUrl = @json(url('/api/analytics-v2/ecommerce-overview'));
            var ordersListUrl = @json(route('ViewAllProductOrder'));
            var ecommerceOrdersUrl = @json(route('OrderListPage', ['order_source' => 'ecommerce']));
            var returnOrdersListUrl = @json(route('ViewAllProductOrderReturns'));

            var d_app = new Vue({
                el: '#dashboard-app',
                data: {
                    preset: 'this_month',
                    customFrom: '',
                    customTo: '',
                    showCustomPicker: false,
                    loading: false,
                    kpiError: '',
                    kpis: [],
                    windows: null,
                    lastUpdatedLabel: '—',
                    ordersListUrl: ordersListUrl,
                    ecommerceOrdersUrl: ecommerceOrdersUrl,
                    returnOrdersListUrl: returnOrdersListUrl,
                    orderStatuses: [],
                    ecommerceAnalyticsLoading: false,
                    ecommerceSummary: {
                        total_orders: 0,
                        accepted_orders: 0,
                        excluded_orders: 0,
                        pending_orders: 0,
                        canceled_orders: 0,
                        couriered_orders: 0,
                        settled_orders: 0,
                        accepted_value: 0,
                        excluded_value: 0,
                        cod_total: 0,
                        courier_cost_total: 0,
                        settlement_pending: 0,
                        acceptance_rate: 0,
                        excluded_rate: 0,
                        settlement_rate: 0,
                    },
                    ecommerceStatuses: [],
                    ecommerceRecentOrders: [],
                    courierRows: [],
                    quotationFunnel: null,
                    revenueTrendCompared: null,
                    ordersTableMode: 'high_value',
                    ordersCounts: {
                        pending: 0,
                        high_value: 0
                    },
                    ordersTableRows: [],
                    ordersTableLoading: false,
                    ordersPagination: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0
                    },
                    trendingProductsRows: [],
                    trendingProductsLoading: false,
                    trendingProductsPagination: {
                        current_page: 1,
                        last_page: 1,
                        per_page: 10,
                        total: 0
                    },
                    categoryProfitItems: [],
                    categoryProfitLoading: false,
                    expensesByCategoryItems: [],
                    expensesByCategoryLoading: false,
                    customerBreakdown: null,
                    websiteVisitors: {
                        current: 0,
                        compared: {
                            trend: 'flat',
                            value: 0,
                            percentage: 0
                        }
                    },
                    returnRateInsight: null,
                    returnRateInsightLoading: false,
                    monthlyProfitLossRows: [],
                    monthlyProfitLossLoading: false,
                },
                computed: {
                    returnRateInsightDisplay: function() {
                        var d = this.returnRateInsight || {};
                        var top = d.top_category || {};
                        return {
                            rate: Number(d.rate || 0),
                            target: Number(d.target || 8),
                            status: d.status || 'ok',
                            total_orders: Number(d.total_orders || 0),
                            returned_orders: Number(d.returned_orders || 0),
                            return_count: Number(d.return_count || 0),
                            returned_value: Number(d.returned_value || 0),
                            top_category: {
                                category_id: top.category_id || null,
                                category_name: top.category_name || 'No returns',
                                returned_qty: Number(top.returned_qty || 0),
                                returned_value: Number(top.returned_value || 0),
                                return_count: Number(top.return_count || 0),
                            },
                        };
                    },
                    returnRateInsightTitle: function() {
                        var status = this.returnRateInsightDisplay.status;
                        if (status === 'alert') return 'Return Rate Alert';
                        if (status === 'warning') return 'Return Rate Watch';
                        return 'Return Rate Healthy';
                    },
                    returnRateInsightIconClass: function() {
                        var status = this.returnRateInsightDisplay.status;
                        if (status === 'alert') return 'fa-solid fa-triangle-exclamation';
                        if (status === 'warning') return 'fa-solid fa-circle-exclamation';
                        return 'fa-solid fa-circle-check';
                    },
                    returnRateInsightCardStyle: function() {
                        var status = this.returnRateInsightDisplay.status;
                        if (status === 'alert') {
                            return {
                                background: 'linear-gradient(135deg,#dc2626,#b91c1c)'
                            };
                        }
                        if (status === 'warning') {
                            return {
                                background: 'linear-gradient(135deg,#f59e0b,#d97706)'
                            };
                        }
                        return {
                            background: 'linear-gradient(135deg,#0d9488,#0e7490)'
                        };
                    },
                    returnRateInsightStats: function() {
                        var d = this.returnRateInsightDisplay;
                        return [{
                                key: 'orders',
                                label: 'Returned orders',
                                value: this.formatInt(d.returned_orders) + ' / ' + this.formatInt(d
                                    .total_orders)
                            },
                            {
                                key: 'returns',
                                label: 'Return slips',
                                value: this.formatInt(d.return_count)
                            },
                            {
                                key: 'value',
                                label: 'Return value',
                                value: this.formatMoney(d.returned_value)
                            },
                        ];
                    },
                    customerBreakdownDisplay: function() {
                        var z = {
                            count: 0,
                            pct: 0
                        };
                        var d = this.customerBreakdown || {};
                        return {
                            new: (d.new || z),
                            returning: (d.returning || z),
                            total: Number(d.total || 0),
                        };
                    },
                    revenueTrendPctLabel: function() {
                        if (!this.revenueTrendCompared) return '0%';
                        var p = Number(this.revenueTrendCompared.percentage) || 0;
                        var sign = p > 0 ? '+' : '';
                        return sign + p.toFixed(1) + '%';
                    },
                    revenueTrendIcon: function() {
                        var t = this.revenueTrendCompared ? this.revenueTrendCompared.trend : 'flat';
                        if (t === 'up') return 'fa-arrow-trend-up';
                        if (t === 'down') return 'fa-arrow-trend-down';
                        return 'fa-minus';
                    },
                    revenueTrendBadgeStyle: function() {
                        var t = this.revenueTrendCompared ? this.revenueTrendCompared.trend : 'flat';
                        if (t === 'down') {
                            return {
                                background: '#fef2f2',
                                color: '#b91c1c',
                                borderColor: '#fecaca'
                            };
                        }
                        if (t === 'up') {
                            return {
                                background: '#ecfdf5',
                                color: '#065f46',
                                borderColor: '#a7f3d0'
                            };
                        }
                        return {
                            background: '#f1f5f9',
                            color: '#475569',
                            borderColor: '#cbd5e1'
                        };
                    },
                    quotationFunnelDisplay: function() {
                        var z = {
                            count: 0,
                            value: 0,
                            pct_of_created: 0
                        };
                        var d = this.quotationFunnel || {};
                        return {
                            created: d.created || {
                                count: 0,
                                value: 0
                            },
                            pending: d.pending || z,
                            in_review: d.in_review || z,
                            converted: d.converted || z,
                            lost: d.lost || z,
                        };
                    },
                    courierRowsDisplay: function() {
                        if (this.courierRows && this.courierRows.length) {
                            return this.courierRows;
                        }
                        return [{
                                courier_name: 'pathao',
                                total_orders: 0,
                                total_delivered: 0,
                                total_pending: 0,
                                total_cancelled: 0,
                                total_returned: 0,
                                total_fee: 0
                            },
                            {
                                courier_name: 'steadfast',
                                total_orders: 0,
                                total_delivered: 0,
                                total_pending: 0,
                                total_cancelled: 0,
                                total_returned: 0,
                                total_fee: 0
                            },
                            {
                                courier_name: 'carrybee',
                                total_orders: 0,
                                total_delivered: 0,
                                total_pending: 0,
                                total_cancelled: 0,
                                total_returned: 0,
                                total_fee: 0
                            },
                        ];
                    },
                    orderStatusesDisplay: function() {
                        if (this.orderStatuses && this.orderStatuses.length) {
                            return this.orderStatuses;
                        }
                        return [{
                                key: 'pending',
                                label: 'Pending',
                                count: 0,
                                pct: 0
                            },
                            {
                                key: 'invoiced',
                                label: 'Invoiced',
                                count: 0,
                                pct: 0
                            },
                            {
                                key: 'delivered',
                                label: 'Delivered',
                                count: 0,
                                pct: 0
                            },
                            {
                                key: 'canceled',
                                label: 'Canceled',
                                count: 0,
                                pct: 0
                            },
                        ];
                    },
                    ecommerceStatusDisplay: function() {
                        if (this.ecommerceStatuses && this.ecommerceStatuses.length) {
                            return this.ecommerceStatuses;
                        }
                        return [{
                                key: 'pending',
                                label: 'Pending Review',
                                icon: 'fa-clock',
                                count: 0,
                                value: 0,
                                pct: 0
                            },
                            {
                                key: 'accepted',
                                label: 'Accepted',
                                icon: 'fa-circle-check',
                                count: 0,
                                value: 0,
                                pct: 0
                            },
                            {
                                key: 'processing',
                                label: 'Processing',
                                icon: 'fa-gears',
                                count: 0,
                                value: 0,
                                pct: 0
                            },
                            {
                                key: 'delivered',
                                label: 'Delivered',
                                icon: 'fa-truck',
                                count: 0,
                                value: 0,
                                pct: 0
                            },
                        ];
                    },
                    rangeSummary: function() {
                        if (this.windows && this.windows.current) {
                            var a = this.windows.current.start_date;
                            var b = this.windows.current.end_date;
                            if (a === b) {
                                return this.windows.preset_label + ' · ' + a;
                            }
                            return this.windows.preset_label + ' · ' + a + ' – ' + b;
                        }
                        return 'This Month';
                    },
                },
                methods: {
                    kpiLabel: function(title) {
                        var labels = {
                            total_sale: 'Total Sale (delivered, invoiced)',
                            profit_from_sale: 'Profit From Sale (delivered, invoiced)',
                            manual_incomes: 'Manual Incomes',
                            deposits: 'Deposits',
                            expenses: 'Expenses',
                            delivery_cost: 'Delivery Cost',
                            net_profit: 'Net Profit',
                        };
                        return labels[title] || title;
                    },
                    kpiCardClass: function(title) {
                        var map = {
                            total_sale: 'g-teal',
                            profit_from_sale: 'g-teal',
                            manual_incomes: 'g-emerald',
                            deposits: 'g-amber',
                            expenses: 'g-blue',
                            delivery_cost: 'g-purple',
                            net_profit: 'g-slate',
                        };
                        return map[title] || 'g-slate';
                    },
                    kpiIcon: function(title) {
                        var map = {
                            total_sale: 'fa-bangladeshi-taka-sign',
                            profit_from_sale: 'fa-bangladeshi-taka-sign',
                            manual_incomes: 'fa-sack-dollar',
                            deposits: 'fa-file-invoice-dollar',
                            expenses: 'fa-box-open',
                            delivery_cost: 'fa-truck',
                            net_profit: 'fa-cart-shopping',
                        };
                        return map[title] || 'fa-chart-line';
                    },
                    trendIconClass: function(trend) {
                        if (trend === 'down') {
                            return 'fa-arrow-trend-down';
                        }
                        if (trend === 'up') {
                            return 'fa-arrow-trend-up';
                        }
                        return 'fa-minus';
                    },
                    formatMoney: function(n) {
                        var x = parseFloat(n);
                        if (isNaN(x)) {
                            x = 0;
                        }
                        var parts = x.toFixed(2).split('.');
                        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        return '৳' + intPart + '.' + parts[1];
                    },
                    formatSignedMoney: function(n, forceNegative) {
                        var x = parseFloat(n);
                        if (isNaN(x)) {
                            x = 0;
                        }
                        if (forceNegative && x > 0) {
                            x = -x;
                        }
                        if (x < 0) {
                            return '-৳' + Math.abs(x).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }
                        return this.formatMoney(x);
                    },
                    formatPercent: function(n) {
                        var x = parseFloat(n);
                        if (isNaN(x)) {
                            x = 0;
                        }
                        return x.toFixed(2) + '%';
                    },
                    moneyClass: function(n) {
                        return Number(n || 0) >= 0 ? 'col-pos' : 'col-neg';
                    },
                    formatInt: function(n) {
                        var x = parseInt(n, 10);
                        if (isNaN(x)) {
                            x = 0;
                        }
                        return x.toLocaleString();
                    },
                    orderStatusBadge: function(status) {
                        var s = String(status || '').toLowerCase();
                        var badgeColors = {
                            pending: 'info',
                            accepted: 'success',
                            processing: 'primary',
                            canceled: 'danger',
                            cancelled: 'danger',
                            invoiced: 'success',
                            delivered: 'primary',
                            returned: 'warning',
                        };
                        var icon =
                            s === 'cancelled' || s === 'canceled' ? 'times' :
                            s === 'accepted' ? 'circle-check' :
                            s === 'processing' ? 'gears' :
                            s === 'invoiced' ? 'file-invoice' :
                            s === 'delivered' ? 'truck' :
                            s === 'returned' ? 'rotate-left' : 'clock';
                        var color = badgeColors[s] || 'secondary';
                        return '<span class=\"badge badge-' + color + '\"><i class=\"fa-solid fa-' + icon +
                            '\"></i> ' + (s ? (s.charAt(0).toUpperCase() + s.slice(1)) : '—') + '</span>';
                    },
                    daysSince: function(iso) {
                        if (!iso) return 0;
                        var d = new Date(iso);
                        if (isNaN(d.getTime())) return 0;
                        return Math.floor((Date.now() - d.getTime()) / (1000 * 60 * 60 * 24));
                    },
                    daysStyle: function(iso) {
                        var days = this.daysSince(iso);
                        if (days >= 4) return {
                            color: '#ef4444',
                            fontWeight: '700'
                        };
                        if (days >= 2) return {
                            color: '#f59e0b',
                            fontWeight: '700'
                        };
                        return {
                            color: '#6b7a99'
                        };
                    },
                    orderViewUrl: function(id) {
                        // existing system route patterns vary; keep a safe fallback
                        return '/show/product-order/manage/' + id;
                    },
                    setOrdersMode: function(mode) {
                        this.ordersTableMode = mode;
                        this.gotoOrdersPage(1);
                    },
                    gotoOrdersPage: function(page) {
                        this.fetchOrdersTable(page);
                    },
                    fetchOrdersTable: function(page) {
                        var self = this;
                        self.ordersTableLoading = true;
                        var params = {
                            preset: self.preset,
                            mode: self.ordersTableMode,
                            page: page || 1,
                            per_page: 10
                        };
                        if (self.preset === 'custom') {
                            params.from = self.customFrom;
                            params.to = self.customTo;
                        }
                        axios.get(ordersTableUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (!res.data || !res.data.success) return;
                                self.ordersCounts = res.data.counts || self.ordersCounts;
                                self.ordersTableRows = res.data.data || [];
                                self.ordersPagination = res.data.pagination || self.ordersPagination;
                            })
                            .catch(function() {})
                            .then(function() {
                                self.ordersTableLoading = false;
                            });
                    },
                    gotoTrendingProductsPage: function(page) {
                        this.fetchTrendingProducts(page);
                    },
                    fetchTrendingProducts: function(page) {
                        var self = this;
                        self.trendingProductsLoading = true;
                        var params = {
                            preset: self.preset,
                            page: page || 1,
                            per_page: 10
                        };
                        if (self.preset === 'custom') {
                            params.from = self.customFrom;
                            params.to = self.customTo;
                        }
                        axios.get(trendingProductsUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (!res.data || !res.data.success) return;
                                self.trendingProductsRows = res.data.data || [];
                                self.trendingProductsPagination = res.data.pagination || self
                                    .trendingProductsPagination;
                            })
                            .catch(function() {})
                            .then(function() {
                                self.trendingProductsLoading = false;
                            });
                    },
                    funnelBarPct: function(pct) {
                        var p = Math.min(100, Math.max(0, Number(pct) || 0));
                        return p + '%';
                    },
                    formatKpiValue: function(kpi) {
                        return this.formatMoney(kpi.value);
                    },
                    formatTrendPercent: function(kpi) {
                        if (kpi.compared_trend === 'flat' || kpi.compared_percentage === null || kpi
                            .compared_percentage === undefined) {
                            return '0%';
                        }
                        var p = Number(kpi.compared_percentage);
                        var sign = p > 0 ? '+' : '';
                        return sign + p.toFixed(1) + '%';
                    },
                    kpiSubline: function(kpi) {
                        return 'vs prior period · ' + this.formatMoney(kpi.compared_value);
                    },
                    orderStatusTheme: function(key) {
                        var themes = {
                            pending: {
                                iconBg: '#f1f5f9',
                                iconColor: '#64748b',
                                pctBg: '#f1f5f9',
                                pctColor: '#64748b',
                                bar: '#94a3b8',
                                countColor: '',
                                border: '',
                            },
                            invoiced: {
                                iconBg: '#eff6ff',
                                iconColor: '#3b82f6',
                                pctBg: '#eff6ff',
                                pctColor: '#3b82f6',
                                bar: '#3b82f6',
                                countColor: '#3b82f6',
                                border: '',
                            },
                            delivered: {
                                iconBg: '#ecfdf5',
                                iconColor: '#10b981',
                                pctBg: '#ecfdf5',
                                pctColor: '#10b981',
                                bar: '#10b981',
                                countColor: '#10b981',
                                border: '',
                            },
                            canceled: {
                                iconBg: '#fef2f2',
                                iconColor: '#ef4444',
                                pctBg: '#fef2f2',
                                pctColor: '#ef4444',
                                bar: '#ef4444',
                                countColor: '#ef4444',
                                border: '1px solid #fecaca',
                            },
                        };
                        return themes[key] || themes.pending;
                    },
                    orderStatusFaIcon: function(key) {
                        var icons = {
                            pending: 'fa-clock',
                            invoiced: 'fa-file-invoice',
                            delivered: 'fa-circle-check',
                            canceled: 'fa-ban',
                        };
                        return icons[key] || 'fa-circle';
                    },
                    ecomStatusTheme: function(key) {
                        var themes = {
                            pending: {
                                bg: '#fff7ed',
                                fg: '#f97316',
                                border: '#fed7aa'
                            },
                            accepted: {
                                bg: '#ecfdf5',
                                fg: '#059669',
                                border: '#a7f3d0'
                            },
                            processing: {
                                bg: '#eff6ff',
                                fg: '#2563eb',
                                border: '#bfdbfe'
                            },
                            invoiced: {
                                bg: '#eef2ff',
                                fg: '#4f46e5',
                                border: '#c7d2fe'
                            },
                            delivered: {
                                bg: '#ecfdf5',
                                fg: '#10b981',
                                border: '#a7f3d0'
                            },
                            canceled: {
                                bg: '#fef2f2',
                                fg: '#dc2626',
                                border: '#fecaca'
                            },
                            // cancelled: { bg: '#fef2f2', fg: '#dc2626', border: '#fecaca' },
                            returned: {
                                bg: '#fff1f2',
                                fg: '#e11d48',
                                border: '#fecdd3'
                            },
                        };
                        return themes[key] || {
                            bg: '#f1f5f9',
                            fg: '#64748b',
                            border: '#e2e8f0'
                        };
                    },
                    orderStatusCountStyle: function(key) {
                        var c = this.orderStatusTheme(key).countColor;
                        return c ? {
                            color: c
                        } : {};
                    },
                    orderStatusBarWidth: function(pct) {
                        var w = Math.min(100, Math.max(0, Number(pct) || 0));
                        return w + '%';
                    },
                    orderStatusCardAnim: function(idx, key) {
                        var style = {
                            animationDelay: (idx * 0.05) + 's'
                        };
                        var b = this.orderStatusTheme(key).border;
                        if (b) {
                            style.border = b;
                        }
                        return style;
                    },
                    courierTheme: function(slug) {
                        var key = String(slug || '').toLowerCase();
                        var map = {
                            pathao: {
                                bg: '#eff6ff',
                                fg: '#3b82f6',
                                icon: 'fa-motorcycle'
                            },
                            steadfast: {
                                bg: '#fef2f2',
                                fg: '#ef4444',
                                icon: 'fa-truck-fast'
                            },
                            carrybee: {
                                bg: '#ecfdf5',
                                fg: '#10b981',
                                icon: 'fa-van-shuttle'
                            },
                        };
                        return map[key] || {
                            bg: '#f1f5f9',
                            fg: '#64748b',
                            icon: 'fa-truck'
                        };
                    },
                    courierDisplayName: function(slug) {
                        var key = String(slug || '').toLowerCase();
                        var names = {
                            pathao: 'Pathao',
                            steadfast: 'Steadfast',
                            carrybee: 'CarryBee'
                        };
                        return names[key] || key.charAt(0).toUpperCase() + key.slice(1);
                    },
                    courierStatLine: function(row) {
                        var d = Number(row.total_delivered) || 0;
                        var p = Number(row.total_pending) || 0;
                        return d + ' delivered · ' + p + ' pending';
                    },
                    setPreset: function(p) {
                        this.preset = p;
                        this.showCustomPicker = false;
                        this.fetchKpis();
                    },
                    toggleCustom: function() {
                        this.showCustomPicker = !this.showCustomPicker;
                        if (this.showCustomPicker) {
                            var d = new Date();
                            var iso = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') +
                                '-' + String(d.getDate()).padStart(2, '0');
                            if (!this.customFrom) {
                                this.customFrom = iso;
                            }
                            if (!this.customTo) {
                                this.customTo = iso;
                            }
                        }
                    },
                    applyCustomRange: function() {
                        if (!this.customFrom || !this.customTo) {
                            this.kpiError = 'Please select both from and to dates.';
                            return;
                        }
                        this.kpiError = '';
                        this.preset = 'custom';
                        this.fetchKpis();
                    },
                    fetchKpis: function() {
                        var self = this;
                        self.loading = true;
                        self.kpiError = '';
                        var params = {
                            preset: self.preset
                        };
                        if (self.preset === 'custom') {
                            params.from = self.customFrom;
                            params.to = self.customTo;
                        }

                        var pending = 19;

                        function finishSlice() {
                            pending -= 1;
                            if (pending <= 0) {
                                self.loading = false;
                            }
                        }

                        axios.get(analyticsV2Url, {
                                params: params
                            })
                            .then(function(res) {
                                if (!res.data || !res.data.success) {
                                    self.kpiError = (res.data && res.data.message) ? res.data.message :
                                        'Unable to load metrics.';
                                    return;
                                }
                                self.windows = res.data.windows;
                                self.kpis = res.data.kpis || [];
                                self.orderStatuses = res.data.order_status || [];
                                self.lastUpdatedLabel = new Date().toLocaleString(undefined, {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                });
                            })
                            .catch(function(err) {
                                var msg = (err.response && err.response.data && err.response.data
                                        .message) ?
                                    err.response.data.message :
                                    (err.message || 'Request failed');
                                self.kpiError = msg;
                            })
                            .then(finishSlice);

                        axios.get(courierBreakdownUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.couriers)) {
                                    self.courierRows = res.data.couriers;
                                } else {
                                    self.courierRows = [];
                                }
                            })
                            .catch(function() {
                                self.courierRows = [];
                            })
                            .then(finishSlice);

                        self.ecommerceAnalyticsLoading = true;
                        axios.get(ecommerceOverviewUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success) {
                                    self.ecommerceSummary = Object.assign({}, self.ecommerceSummary, res
                                        .data.summary || {});
                                    self.ecommerceStatuses = res.data.statuses || [];
                                    self.ecommerceRecentOrders = res.data.recent_orders || [];
                                } else {
                                    self.ecommerceStatuses = [];
                                    self.ecommerceRecentOrders = [];
                                }
                            })
                            .catch(function() {
                                self.ecommerceStatuses = [];
                                self.ecommerceRecentOrders = [];
                            })
                            .then(function() {
                                self.ecommerceAnalyticsLoading = false;
                            })
                            .then(finishSlice);

                        axios.get(quotationFunnelUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.quotation_funnel) {
                                    self.quotationFunnel = res.data.quotation_funnel;
                                } else {
                                    self.quotationFunnel = null;
                                }
                            })
                            .catch(function() {
                                self.quotationFunnel = null;
                            })
                            .then(finishSlice);

                        axios.get(revenueTrendUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.series && window
                                    .updateRevenueChart) {
                                    window.updateRevenueChart(res.data.series);
                                    self.revenueTrendCompared = res.data.compared || null;
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(revExpProfitUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.series && window
                                    .updateRevExpChart) {
                                    window.updateRevExpChart(res.data.series);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(topProductsUrl, {
                                params: Object.assign({
                                    limit: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items) &&
                                    window.updateTopProductsChart) {
                                    window.updateTopProductsChart(res.data.items);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(paymentMethodsUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items) &&
                                    window.updatePaymentChart) {
                                    window.updatePaymentChart(res.data.items);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(topCategoriesUrl, {
                                params: Object.assign({
                                    limit: 5
                                }, params)
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items) &&
                                    window.updateCategoryChart) {
                                    window.updateCategoryChart(res.data.items);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(ordersProfitUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.series && window
                                    .updateOrdersProfitChart) {
                                    window.updateOrdersProfitChart(res.data.series);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        axios.get(topCategoryProfitUrl, {
                                params: Object.assign({
                                    limit: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items) &&
                                    window.updateCategoryProfitChart) {
                                    window.updateCategoryProfitChart(res.data.items);
                                }
                            })
                            .catch(function() {
                                // keep existing chart data
                            })
                            .then(finishSlice);

                        self.categoryProfitLoading = true;
                        axios.get(categoryProfitShareUrl, {
                                params: Object.assign({
                                    limit: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items)) {
                                    self.categoryProfitItems = res.data.items;
                                } else {
                                    self.categoryProfitItems = [];
                                }
                            })
                            .catch(function() {
                                self.categoryProfitItems = [];
                            })
                            .then(function() {
                                self.categoryProfitLoading = false;
                            })
                            .then(finishSlice);

                        self.expensesByCategoryLoading = true;
                        axios.get(expensesByCategoryUrl, {
                                params: Object.assign({
                                    limit: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items)) {
                                    self.expensesByCategoryItems = res.data.items;
                                } else {
                                    self.expensesByCategoryItems = [];
                                }
                            })
                            .catch(function() {
                                self.expensesByCategoryItems = [];
                            })
                            .then(function() {
                                self.expensesByCategoryLoading = false;
                            })
                            .then(finishSlice);

                        axios.get(customerBreakdownUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.breakdown) {
                                    self.customerBreakdown = res.data.breakdown;
                                } else {
                                    self.customerBreakdown = null;
                                }
                            })
                            .catch(function() {
                                self.customerBreakdown = null;
                            })
                            .then(finishSlice);

                        axios.get(websiteVisitorsUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success) {
                                    self.websiteVisitors = {
                                        current: Number(res.data.current || 0),
                                        compared: res.data.compared || {
                                            trend: 'flat',
                                            value: 0,
                                            percentage: 0
                                        },
                                    };
                                } else {
                                    self.websiteVisitors = {
                                        current: 0,
                                        compared: {
                                            trend: 'flat',
                                            value: 0,
                                            percentage: 0
                                        }
                                    };
                                }
                            })
                            .catch(function() {
                                self.websiteVisitors = {
                                    current: 0,
                                    compared: {
                                        trend: 'flat',
                                        value: 0,
                                        percentage: 0
                                    }
                                };
                            })
                            .then(finishSlice);

                        self.returnRateInsightLoading = true;
                        axios.get(returnRateInsightUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && res.data.breakdown) {
                                    self.returnRateInsight = res.data.breakdown;
                                } else {
                                    self.returnRateInsight = null;
                                }
                            })
                            .catch(function() {
                                self.returnRateInsight = null;
                            })
                            .then(function() {
                                self.returnRateInsightLoading = false;
                            })
                            .then(finishSlice);

                        self.monthlyProfitLossLoading = true;
                        axios.get(monthlyProfitLossUrl, {
                                params: params
                            })
                            .then(function(res) {
                                if (res.data && res.data.success && Array.isArray(res.data.items)) {
                                    self.monthlyProfitLossRows = res.data.items;
                                } else {
                                    self.monthlyProfitLossRows = [];
                                }
                            })
                            .catch(function() {
                                self.monthlyProfitLossRows = [];
                            })
                            .then(function() {
                                self.monthlyProfitLossLoading = false;
                            })
                            .then(finishSlice);

                        // Orders table (separate loading flag, but keep overall page spinner in sync)
                        axios.get(ordersTableUrl, {
                                params: Object.assign({
                                    mode: self.ordersTableMode,
                                    page: 1,
                                    per_page: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (!res.data || !res.data.success) return;
                                self.ordersCounts = res.data.counts || self.ordersCounts;
                                self.ordersTableRows = res.data.data || [];
                                self.ordersPagination = res.data.pagination || self.ordersPagination;
                            })
                            .catch(function() {})
                            .then(finishSlice);

                        // Trending products table
                        axios.get(trendingProductsUrl, {
                                params: Object.assign({
                                    page: 1,
                                    per_page: 10
                                }, params)
                            })
                            .then(function(res) {
                                if (!res.data || !res.data.success) return;
                                self.trendingProductsRows = res.data.data || [];
                                self.trendingProductsPagination = res.data.pagination || self
                                    .trendingProductsPagination;
                            })
                            .catch(function() {})
                            .then(finishSlice);
                    },
                },
                mounted: function() {
                    this.fetchKpis();
                },
            });
        })();
    </script>

    <script>
        // ── DATA ──────────────────────────────────────────────────────

        function formatCurrency(amount) {
            return '৳' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        var revenueChartInstance = null;
        window.updateRevenueChart = function(series) {
            if (!revenueChartInstance || !series) return;

            var labels = Array.isArray(series.labels) ? series.labels : [];
            var revenue = Array.isArray(series.revenue) ? series.revenue : [];
            var profit = Array.isArray(series.profit) ? series.profit : [];

            revenueChartInstance.data.labels = labels;
            revenueChartInstance.data.datasets[0].data = revenue;
            if (revenueChartInstance.data.datasets[1]) {
                revenueChartInstance.data.datasets[1].data = profit;
            }
            revenueChartInstance.update();
        };

        var revExpChartInstance = null;
        window.updateRevExpChart = function(series) {
            if (!revExpChartInstance || !series) return;

            var labels = Array.isArray(series.labels) ? series.labels : [];
            var sales = Array.isArray(series.sales) ? series.sales : (Array.isArray(series.revenue) ? series.revenue :
            []);
            var expenses = Array.isArray(series.expenses) ? series.expenses : [];
            var profit = Array.isArray(series.profit) ? series.profit : [];

            revExpChartInstance.data.labels = labels;
            revExpChartInstance.data.datasets[0].data = sales;
            revExpChartInstance.data.datasets[1].data = expenses;
            revExpChartInstance.data.datasets[2].data = profit;
            revExpChartInstance.update();
        };

        var paymentChartInstance = null;

        function paymentPalette() {
            return ['#0d9488', '#e91e8c', '#f59e0b', '#10b981', '#8b5cf6', '#3b82f6', '#64748b', '#ef4444'];
        }
        window.updatePaymentChart = function(items) {
            if (!paymentChartInstance) return;
            if (!Array.isArray(items)) items = [];

            var labels = items.map(function(i) {
                return i.label || 'Unknown';
            });
            var values = items.map(function(i) {
                return Number(i.percentage || 0);
            });
            var colors = paymentPalette().slice(0, Math.max(labels.length, 1));

            paymentChartInstance.data.labels = labels;
            paymentChartInstance.data.datasets[0].data = values;
            paymentChartInstance.data.datasets[0].backgroundColor = colors;
            paymentChartInstance.update();
        };

        var categoryChartInstance = null;
        window.updateCategoryChart = function(items) {
            if (!categoryChartInstance) return;
            if (!Array.isArray(items)) items = [];

            var labels = items.map(function(i) {
                return i.category_name || 'Uncategorized';
            });
            var values = items.map(function(i) {
                return Number(i.total_orders || 0);
            });

            categoryChartInstance.data.labels = labels;
            categoryChartInstance.data.datasets[0].data = values;
            categoryChartInstance.update();
        };

        var ordersProfitChartInstance = null;
        window.updateOrdersProfitChart = function(series) {
            if (!ordersProfitChartInstance || !series) return;

            var labels = Array.isArray(series.labels) ? series.labels : [];
            var orders = Array.isArray(series.orders) ? series.orders : [];
            var profit = Array.isArray(series.profit) ? series.profit : [];

            ordersProfitChartInstance.data.labels = labels;
            ordersProfitChartInstance.data.datasets[0].data = orders;
            ordersProfitChartInstance.data.datasets[1].data = profit;
            ordersProfitChartInstance.update();
        };

        var categoryProfitChartInstance = null;

        function categoryProfitPalette() {
            return ['#0d9488', '#10b981', '#f59e0b', '#8b5cf6', '#3b82f6', '#e91e8c', '#ef4444', '#64748b', '#22c55e',
                '#f97316'
            ];
        }
        window.updateCategoryProfitChart = function(items) {
            if (!categoryProfitChartInstance) return;
            if (!Array.isArray(items)) items = [];

            var labels = items.map(function(i) {
                return i.category_name || 'Uncategorized';
            });
            var values = items.map(function(i) {
                return Number(i.profit || 0);
            });

            categoryProfitChartInstance.data.labels = labels;
            categoryProfitChartInstance.data.datasets[0].data = values;
            categoryProfitChartInstance.data.datasets[0].backgroundColor = categoryProfitPalette().slice(0, Math.max(
                labels.length, 1));
            categoryProfitChartInstance.update();
        };

        // ── CHARTS ────────────────────────────────────────────────────
        const font = {
            family: "'DM Sans', sans-serif"
        };
        Chart.defaults.font.family = font.family;
        Chart.defaults.color = '#9ca3af';

        // Revenue Trend
        revenueChartInstance = new Chart('revenueChart', {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue (৳)',
                    data: [],
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13,148,136,.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: .45,
                    pointBackgroundColor: '#0d9488',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }, {
                    label: 'Profit (৳)',
                    data: [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.08)',
                    borderWidth: 2,
                    fill: false,
                    tension: .45,
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: c => (c.dataset && c.dataset.label ? (c.dataset.label + ': ') : '') + '৳' +
                                Number(c.raw || 0).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (Number(v) / 1000).toFixed(0) + 'k'
                        }
                    }
                }
            }
        });

        // Rev/Exp/Profit
        revExpChartInstance = new Chart('revExpChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                        label: 'Sales',
                        data: [],
                        backgroundColor: 'rgba(59,130,246,.7)',
                        borderRadius: 5
                    },
                    {
                        label: 'Expenses',
                        data: [],
                        backgroundColor: 'rgba(156,163,175,.5)',
                        borderRadius: 5
                    },
                    {
                        label: 'Net Profit',
                        data: [],
                        backgroundColor: 'rgba(16,185,129,.8)',
                        borderRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => (c.dataset && c.dataset.label ? (c.dataset.label + ': ') : '') + '৳' +
                                Number(c.raw || 0).toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (Number(v) / 1000).toFixed(0) + 'k'
                        }
                    }
                }
            }
        });

        var productChartInstance = null;
        window.updateTopProductsChart = function(items) {
            if (!productChartInstance) return;
            if (!Array.isArray(items)) items = [];

            var labels = items.map(function(i) {
                return i.product_name || 'Unknown';
            });
            var values = items.map(function(i) {
                return Number(i.sales || 0);
            });

            productChartInstance.data.labels = labels;
            productChartInstance.data.datasets[0].data = values;
            productChartInstance.update();
        };

        // Top Products
        productChartInstance = new Chart('productChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Sales',
                    data: [],
                    backgroundColor: 'rgba(13,148,136,.75)',
                    borderRadius: [0, 4, 4, 0],
                    hoverBackgroundColor: '#0d9488'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: c => '৳' + c.raw.toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            callback: v => '৳' + (v / 1000).toFixed(0) + 'k'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 9
                            }
                        }
                    }
                }
            }
        });

        // Payment Pie
        paymentChartInstance = new Chart('paymentChart', {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: paymentPalette(),
                    hoverOffset: 8,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => c.label + ': ' + Number(c.raw || 0).toFixed(1) + '%'
                        }
                    }
                }
            }
        });

        // Category Bar
        categoryChartInstance = new Chart('categoryChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Orders',
                    data: [],
                    backgroundColor: 'rgba(139,92,246,.75)',
                    borderRadius: 5,
                    hoverBackgroundColor: '#8b5cf6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 8
                            },
                            maxRotation: 30
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Orders Count vs Profit (dual axis)
        ordersProfitChartInstance = new Chart('ordersAvgChart', {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                        type: 'bar',
                        label: 'Orders Count',
                        data: [],
                        backgroundColor: 'rgba(16,185,129,.7)',
                        borderRadius: 5,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Profit (৳)',
                        data: [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        tension: .4,
                        pointRadius: 4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => (c.dataset && c.dataset.label ? (c.dataset.label + ': ') : '') + (c
                                    .dataset && c.dataset.yAxisID === 'y1' ? '৳' : '') + Number(c.raw || 0)
                                .toLocaleString()
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: '#f0f2f7'
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        },
                        position: 'left'
                    },
                    y1: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            callback: v => '৳' + (Number(v) / 1000).toFixed(0) + 'k'
                        },
                        position: 'right'
                    }
                }
            }
        });

        // Category Profit Donut
        categoryProfitChartInstance = new Chart('catRevenueChart', {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    backgroundColor: categoryProfitPalette(),
                    hoverOffset: 8,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
                            },
                            boxWidth: 10,
                            padding: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: c => c.label + ': ৳' + Number(c.raw || 0).toLocaleString()
                        }
                    }
                }
            }
        });

        // ── TABLES (legacy static demo; keep guarded) ─────────────────────────
        const orders = [];

        const tbody = document.getElementById('ordersTableBody');
        if (tbody) {
            orders.forEach(o => {
                // order status badge
                // pending, cancelled, invoiced, delivered
                const badgeColors = {
                    pending: 'info',
                    cancelled: 'danger',
                    invoiced: 'success',
                    delivered: 'primary'
                };
                const statusBadge = `<span class="badge badge-${badgeColors[o.order_status] ?? 'secondary'}">
                    <i class="fa-solid fa-${o.order_status === 'cancelled' ? 'times' : o.order_status === 'invoiced' ? 'file-invoice' : 'truck'}"></i> ${o.order_status.charAt(0).toUpperCase() + o.order_status.slice(1)}
                </span>`;
                const days = Math.floor((new Date() - new Date(o.created_at)) / (1000 * 60 * 60 * 24));
                const daysColor = days >= 4 ? 'color:#ef4444;font-weight:700;' : days >= 2 ?
                    'color:#f59e0b;font-weight:700;' : 'color:#6b7a99;';
                const bg = o.order_note ? 'background:#fffdf0;' : '';
                tbody.innerHTML += `<tr style="${bg}">
            
                <td class="td-id">${o.order_code}</td>
                <td><div class="td-name">${o.customer_name}</div></td>
                <td class="td-amount">${o.total}</td>
                <td>${statusBadge}</td>
                <td><span style="${daysColor}">${days}d</span></td>
                <td><div style="display:flex;gap:5px;">
                <button class="action-btn action-btn-ghost">View</button>
                </div></td>
            </tr>`;
            });
        }

        const trendingProducts = [];

        const ptbody = document.getElementById('productsTableBody');
        if (ptbody) {
            let rows = '';

            trendingProducts.forEach(p => {

                const rate = p.price;

                const rateColor = rate >= 900 ? '#10b981' :
                    rate >= 800 ? '#f59e0b' :
                    '#ef4444';

                const stockEl = p.total_sold > 10 ?
                    '<span class="stock-ok"><i class="fa-solid fa-circle-check"></i> OK</span>' :
                    p.total_sold === 1 ?
                    '<span class="stock-low"><i class="fa-solid fa-triangle-exclamation"></i> Low</span>' :
                    '<span class="stock-critical"><i class="fa-solid fa-circle-exclamation"></i> Critical</span>';

                rows += `<tr>
                <td>
                    <div class="td-name">${p.name}</div>
                    <div class="td-sub">${p.created_at ?? ''}</div>
                </td>
                <td style="font-weight:700;">${p.total_sold}</td>
                <td><span style="font-weight:800;color:${rateColor};">${formatCurrency(p.price)}</span></td>
                <td style="font-weight:700;font-size:12px;">${formatCurrency(p.total_revenue)}</td>
                <td>${stockEl}</td>
            </tr>`;
            });

            ptbody.innerHTML = rows;
        }

        // ── CATEGORY REVENUE LIST ──────────────────────────────────────
        const catData = [{
                name: 'Saree & Ethnic',
                pct: 38,
                color: '#0d9488'
            },
            {
                name: 'Men\'s Wear',
                pct: 24,
                color: '#10b981'
            },
            {
                name: 'Footwear',
                pct: 16,
                color: '#f59e0b'
            },
            {
                name: 'Kids',
                pct: 12,
                color: '#8b5cf6'
            },
            {
                name: 'Other',
                pct: 10,
                color: '#6b7280'
            },
        ];
        const catList = document.getElementById('catRevenueList');
        if (catList) {
            catData.forEach(c => {
                catList.innerHTML += `<div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;font-weight:600;color:#1a2332;display:flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:50%;background:${c.color};display:inline-block;"></span>${c.name}
                </span>
                <span style="font-size:12px;font-weight:800;color:#1a2332;">${c.pct}%</span>
                </div>
                <div style="height:6px;background:#f0f2f7;border-radius:6px;overflow:hidden;">
                <div style="height:100%;width:${c.pct}%;background:${c.color};border-radius:6px;transition:width .8s;"></div>
                </div>
            </div>`;
            });
        }

        // ── EXPENSES LIST ──────────────────────────────────────────────
        const expenses = [{
                name: 'Wages & Salaries',
                amt: '৳4,58,000',
                pct: 41.29
            },
            {
                name: 'Subcontractors',
                amt: '৳3,68,000',
                pct: 33.18
            },
            {
                name: 'Rent (Office)',
                amt: '৳2,10,000',
                pct: 18.93
            },
            {
                name: 'Payroll Tax',
                amt: '৳59,900',
                pct: 5.4
            },
            {
                name: 'Payment Fees',
                amt: '৳15,300',
                pct: 1.38
            },
        ];
        const expList = document.getElementById('expenseList');
        if (expList) {
            expenses.forEach(e => {
                expList.innerHTML += `<div class="exp-row">
                <div class="exp-name">${e.name}</div>
                <div class="exp-amt">${e.amt}</div>
                <div class="exp-bar-wrap"><div class="exp-bar-fill" style="width:${e.pct}%;"></div></div>
                <div class="exp-pct">${e.pct}%</div>
            </div>`;
            });
        }

        // Date tabs
        document.querySelectorAll('.date-tab').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.date-tab').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
@endsection

@section('footer_js')
@endsection
