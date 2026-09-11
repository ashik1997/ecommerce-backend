@extends('backend.master')

@section('header_css')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ versioned_asset('assets/js/vue.min.js') }}"></script>
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
            width: 68px;
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
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
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
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        #order_details_component .modal-overlay.open {
            display: flex;
        }

        #order_details_component .modal {
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
    <div class="container" style="max-width: 1500px;">
        <div id="order_details_component">

            <!-- TOPBAR -->
            <div class="topbar">
                <div class="topbar-brand">⬡ ORDER MGR</div>
                <div class="topbar-order">@{{ order . order_code }}</div>
                <div class="topbar-right">
                    <span class="badge" :class="'badge-' + orderStatus">@{{ orderStatus }}</span>
                    <span class="text-muted text-xs font-mono">@{{ order . sale_date }}</span>
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
                                <select v-model="customer.district">
                                    <option>Bandarban</option>
                                    <option>Dhaka</option>
                                    <option>Chittagong</option>
                                    <option>Sylhet</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Upazila</label>
                                <select v-model="customer.upazila">
                                    <option>Rowangchhari</option>
                                    <option>Bandarban Sadar</option>
                                    <option>Lama</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid-3">
                            <div class="form-group">
                                <label>Thana</label>
                                <input type="text" v-model="customer.thana" placeholder="Thana / Police Station">
                            </div>
                            <div class="form-group">
                                <label>Post Office</label>
                                <input type="text" v-model="customer.post_office" placeholder="Post office">
                            </div>
                            <div class="form-group">
                                <label>Full Address</label>
                                <input type="text" v-model="customer.address">
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
                        <span class="text-muted text-xs font-mono" style="margin-left:auto">@{{ products . length }}
                            item@{{ products . length !== 1 ? 's' : '' }}</span>
                    </div>
                    <div class="section-body" style="padding-bottom:0">
                        <div class="product-search-wrap">
                            <span class="search-icon">⌕</span>
                            <input type="text" v-model="productSearch"
                                placeholder="Search product by name, SKU or barcode to add...">
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
                                    <td class="font-mono" style="color:var(--text3);font-size:12px">@{{ i + 1 }}</td>
                                    <td>
                                        <div class="product-cell">
                                            <img :src="p.img" class="product-img" >
                                            <div class="product-info">
                                                <div class="product-name" @click="openWhModal(i)">@{{ p . name }}</div>
                                                <div class="product-meta">
                                                    <span v-if="p.variant" class="meta-tag">Var: @{{ p . variant }}</span>
                                                    <span class="meta-tag">WH: @{{ p . warehouse || '—' }}</span>
                                                    <span class="meta-tag green">Stock: 15</span>
                                                </div>
                                                <input type="text" class="td-input sm" v-model="p.barcode"
                                                    placeholder="Barcode" style="width:130px;margin-top:4px">
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
                                                placeholder="Enter coupon..." style="flex:1">
                                            <button class="btn btn-outline"
                                                style="padding:8px 12px;font-size:12px;white-space:nowrap"
                                                @click="applyCoupon">Apply</button>
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
                                        @{{ fmt(summary . couponDiscount) }}</span></div>
                                <div class="total-row"><span class="tl">Extra Charge</span><span class="tv">+ ৳
                                        @{{ fmt(summary . extraCharge) }}</span></div>
                                <div class="total-row"><span class="tl">Delivery</span><span class="tv">+ ৳
                                        @{{ fmt(summary . deliveryCharge) }}</span></div>
                                <div class="total-row"><span class="tl">Round Off</span><span class="tv">৳
                                        @{{ fmt(summary . roundOff) }}</span></div>
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
                                <div class="pay-method-name">@{{ m . label }}</div>
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
                                    ['s-' + s.key]: orderStatus === s.key }" @click="orderStatus = s.key">
                                @{{ s . icon }} @{{ s . label }}</div>
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
                                <option>Dhaka — Banani Branch</option>
                                <option>Chittagong — GEC Branch</option>
                                <option>Sylhet — Zindabazar Branch</option>
                            </select>
                        </div>

                        <div class="grid-3">
                            <div class="form-group">
                                <label>Expected Delivery Date</label>
                                <input type="date" v-model="delivery.expectedDate">
                            </div>
                            <div class="form-group" style="grid-column:span 2">
                                <label>Courier Method</label>
                                <div class="courier-options">
                                    <label v-for="c in courierOptions" :key="c.key" class="courier-opt"
                                        :class="{ active: delivery.courier === c.key }">
                                        <input type="radio" name="courier" :value="c.key"
                                            v-model="delivery.courier"> @{{ c . label }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Courier Delivery Address</label>
                            <textarea rows="2" v-model="delivery.courierAddress"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Note for Courier</label>
                            <textarea rows="2" v-model="delivery.courierNote" placeholder="Fragile, call before delivery..."></textarea>
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
                    <button class="btn btn-success" @click="whModal.invoiceOpen = true">📋 Preview &amp; Submit</button>
                </div>
            </div>

            <!-- WAREHOUSE MODAL -->
            <div class="modal-overlay" :class="{ open: whModal.open }">
                <div class="modal">
                    <div class="modal-header">
                        <div class="modal-title">Assign Warehouse — @{{ whModal . productName }}</div>
                        <button class="modal-close" @click="closeWhModal">✕</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted text-xs" style="margin-bottom:14px">Select warehouse → room → carton, then
                            scan the product barcode to confirm.</p>

                        <label>Step 1 — Select Warehouse</label>
                        <div class="wh-grid mt8">
                            <div v-for="w in warehouses" :key="w.name" class="wh-card"
                                :class="{ selected: whModal.warehouse === w.name }" @click="selectWarehouse(w.name)">
                                <div class="wh-card-name">@{{ w . icon }} @{{ w . name }}</div>
                                <div class="wh-card-stock" :class="{ out: w.stock === 0 }">@{{ w . stock }} units
                                </div>
                            </div>
                        </div>

                        <div v-if="whModal.warehouse" class="mt8">
                            <label>Step 2 — Select Room</label>
                            <div class="grid-2 mt8">
                                <div v-for="r in rooms" :key="r.name" class="wh-card"
                                    :class="{ selected: whModal.room === r.name }" @click="selectRoom(r.name)">
                                    <div class="wh-card-name">🚪 @{{ r . name }}</div>
                                    <div class="wh-card-stock">@{{ r . stock }} units</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="whModal.room" class="mt8">
                            <label>Step 3 — Select Carton</label>
                            <div class="grid-2 mt8">
                                <div v-for="c in cartons" :key="c.name" class="wh-card"
                                    :class="{ selected: whModal.carton === c.name }" @click="selectCarton(c.name)">
                                    <div class="wh-card-name">📦 @{{ c . name }}</div>
                                    <div class="wh-card-stock">@{{ c . stock }} units</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="whModal.carton" class="mt8">
                            <div class="wh-path">
                                <span>Path:</span>
                                <span class="wh-path-sep">›</span><span
                                    class="wh-path-node">@{{ whModal . warehouse }}</span>
                                <span class="wh-path-sep">›</span><span class="wh-path-node">@{{ whModal . room }}</span>
                                <span class="wh-path-sep">›</span><span class="wh-path-node">@{{ whModal . carton }}</span>
                            </div>
                            <label>Step 4 — Scan / Enter Barcode</label>
                            <div class="flex gap8 mt8">
                                <input type="text" v-model="whModal.barcode"
                                    placeholder="Scan or type product barcode..." style="flex:1;font-family:var(--mono)"
                                    ref="barcodeInput">
                                <button class="btn btn-primary" @click="confirmBarcode">✓ Confirm</button>
                            </div>
                            <p class="text-muted text-xs mt8">Scan the physical item to verify you picked the correct
                                product.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INVOICE MODAL -->
            <div class="modal-overlay" :class="{ open: whModal.invoiceOpen }">
                <div class="modal invoice-modal">
                    <div class="modal-header">
                        <div class="modal-title">📋 Invoice Preview</div>
                        <button class="modal-close" @click="whModal.invoiceOpen = false">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="invoice-header-box">
                            <div>
                                <div class="invoice-co">⬡ ShopManager</div>
                                <div class="invoice-ord">@{{ order . order_code }}</div>
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
                                <div style="font-weight:600">@{{ customer . name }}</div>
                                <div class="text-muted">@{{ customer . phone }}</div>
                                <div class="text-muted">@{{ customer . address }}, @{{ customer . upazila }},
                                    @{{ customer . district }}</div>
                            </div>
                            <div>
                                <div class="inv-section-title">Delivery</div>
                                <div class="text-muted">Expected: @{{ delivery . expectedDate }}</div>
                                <div class="text-muted">Method:
                                    @{{ delivery . method === 'home' ? 'Home Delivery' : 'Store Pickup' }}</div>
                                <div class="text-muted">Courier:
                                    @{{ delivery . courier === 'none' ? 'None' : delivery . courier }}</div>
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
                                        <th>Discount</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(p, i) in products" :key="p.id">
                                        <td>@{{ i + 1 }}</td>
                                        <td style="font-size:12px">@{{ p . name }}</td>
                                        <td class="font-mono">৳ @{{ fmt(p . unit) }}</td>
                                        <td class="font-mono">
                                            @{{ p . discount_amount > 0 ? '—৳' + fmt(p . discount_amount) : '—' }}</td>
                                        <td style="text-align:center">@{{ p . qty }}</td>
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
                            <div class="inv-row"><span>Delivery</span><span class="inv-v font-mono">৳
                                    @{{ fmt(summary . deliveryCharge) }}</span></div>
                            <div class="inv-row"><span>Grand Total</span><span class="inv-v">৳
                                    @{{ fmt(grandTotal) }}</span></div>
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
                        <button class="btn btn-primary" @click="$window.print()">🖨️ Print</button>
                        <button class="btn btn-success" @click="confirmOrder">✅ Confirm Order</button>
                    </div>
                </div>
            </div>

            <!-- TOAST -->
            <div v-if="showToast" class="toast">✅ Order Confirmed Successfully!</div>

        </div><!-- /#order_details_component -->
    </div>
@endsection

@push('js')
    <script>
        new Vue({
            el: '#order_details_component',

            data: function() {
                return {
                    // ── ORDER META ──
                    order: {
                        order_code: 'ORD-26030110002',
                        sale_date: '2026-03-01',
                        note: 'af asd fa'
                    },

                    // ── CUSTOMER ──
                    customer: {
                        order_source: 'ecommerce',
                        name: 'test',
                        phone: '1646365656',
                        email: '',
                        district: 'Bandarban',
                        upazila: 'Rowangchhari',
                        thana: '',
                        post_office: '',
                        address: 'sadf asdf asdf'
                    },

                    // ── PRODUCTS ──
                    productSearch: '',
                    products: [{
                            id: 66,
                            name: 'Toshiba MG10ADA800E SATA 7200RPM 8TB Enterprise Hard Disk Drive',
                            img: 'https://placehold.co/44x44/dde1ef/3b6ef0?text=HDD',
                            unit: 32500,
                            discount_amount: 0,
                            qty: 1,
                            variant: 'S',
                            warehouse: '',
                            barcode: ''
                        },
                        {
                            id: 67,
                            name: 'Acer Nitro VG270 X1 27" FHD 200Hz IPS Gaming Monitor',
                            img: 'https://placehold.co/44x44/dde1ef/6c44f4?text=MON',
                            unit: 23000,
                            discount_amount: 0,
                            qty: 1,
                            variant: 'Green-L',
                            warehouse: '',
                            barcode: ''
                        },
                        {
                            id: 68,
                            name: 'Acer SA272Y P1 27" 144Hz IPS FHD Monitor White',
                            img: 'https://placehold.co/44x44/dde1ef/16a05c?text=MON',
                            unit: 19800,
                            discount_amount: 0,
                            qty: 1,
                            variant: null,
                            warehouse: '',
                            barcode: ''
                        },
                        {
                            id: 69,
                            name: 'Acer Nitro VG240Y X1 23.8" FHD 200Hz IPS Gaming Monitor',
                            img: 'https://placehold.co/44x44/dde1ef/b07d00?text=MON',
                            unit: 18500,
                            discount_amount: 500,
                            qty: 1,
                            variant: null,
                            warehouse: '',
                            barcode: ''
                        },
                        {
                            id: 70,
                            name: 'Acer SA242Y P1 23.8" 144Hz IPS FHD Monitor White',
                            img: 'https://placehold.co/44x44/dde1ef/d93535?text=MON',
                            unit: 14900,
                            discount_amount: 900,
                            qty: 1,
                            variant: 'Green',
                            warehouse: '',
                            barcode: ''
                        },
                        {
                            id: 71,
                            name: 'Dahua DHI-LM22-A201YW 21.45" Professional FHD Display Monitor',
                            img: 'https://placehold.co/44x44/dde1ef/c4601a?text=MON',
                            unit: 10300,
                            discount_amount: 1300,
                            qty: 1,
                            variant: 'Green-S',
                            warehouse: '',
                            barcode: ''
                        }
                    ],

                    // ── SUMMARY ──
                    summary: {
                        discountType: '',
                        discountValue: 0,
                        couponCode: '',
                        couponDiscount: 0,
                        extraCharge: 0,
                        deliveryCharge: 120,
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
                    statusOptions: [{
                            key: 'pending',
                            icon: '⏳',
                            label: 'Pending'
                        },
                        {
                            key: 'accepted',
                            icon: '✅',
                            label: 'Accepted'
                        },
                        {
                            key: 'processing',
                            icon: '⚙️',
                            label: 'Processing'
                        },
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
                        outlet: 'Dhaka — Banani Branch',
                        expectedDate: '2026-03-08',
                        courier: 'none',
                        courierAddress: 'sadf asdf asdf, Rowangchhari, Bandarban',
                        courierNote: ''
                    },
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
                        {
                            key: 'redx',
                            label: '🔴 RedX'
                        },
                        {
                            key: 'sundarban',
                            label: '🌿 Sundarban'
                        },
                        {
                            key: 'ecourier',
                            label: '⚡ eCourier'
                        }
                    ],

                    // ── WAREHOUSE MODAL ──
                    whModal: {
                        open: false,
                        invoiceOpen: false,
                        productIdx: null,
                        productName: '',
                        warehouse: null,
                        room: null,
                        carton: null,
                        barcode: ''
                    },
                    warehouses: [{
                            name: 'Main Warehouse',
                            icon: '🏭',
                            stock: 48
                        },
                        {
                            name: 'Dhaka Store',
                            icon: '🏪',
                            stock: 12
                        },
                        {
                            name: 'CTG Warehouse',
                            icon: '🏗️',
                            stock: 0
                        }
                    ],
                    rooms: [{
                            name: 'Room A',
                            stock: 30
                        },
                        {
                            name: 'Room B',
                            stock: 18
                        }
                    ],
                    cartons: [{
                            name: 'Carton CTN-001',
                            stock: 15
                        },
                        {
                            name: 'Carton CTN-002',
                            stock: 15
                        }
                    ],

                    // ── TOAST ──
                    showToast: false
                };
            },

            computed: {
                // Product line total
                productTotal: function() {
                    return function(p) {
                        return Math.max(0, (p.unit - (p.discount_amount || 0))) * (p.qty || 1);
                    };
                },

                // Discount % display
                discPct: function() {
                    return function(p) {
                        if (!p.unit || !p.discount_amount) return 0;
                        return parseFloat(((p.discount_amount / p.unit) * 100).toFixed(1));
                    };
                },

                // Subtotal of all products
                subtotal: function() {
                    return this.products.reduce(function(s, p) {
                        return s + Math.max(0, (p.unit - (p.discount_amount || 0))) * (p.qty || 1);
                    }, 0);
                },

                // Order-level discount amount
                discountAmount: function() {
                    var v = this.summary.discountValue || 0;
                    if (this.summary.discountType === 'percent') return this.subtotal * (v / 100);
                    if (this.summary.discountType === 'fixed') return v;
                    return 0;
                },

                // Grand total
                grandTotal: function() {
                    return this.subtotal -
                        this.discountAmount -
                        (this.summary.couponDiscount || 0) +
                        (this.summary.extraCharge || 0) +
                        (this.summary.deliveryCharge || 0) +
                        (this.summary.roundOff || 0);
                },

                // Sum of active payment method amounts
                totalPaid: function() {
                    return this.payMethods.reduce(function(s, m) {
                        return s + (m.active ? (m.amount || 0) : 0);
                    }, 0);
                },

                // Due
                totalDue: function() {
                    return Math.max(0, this.grandTotal - this.totalPaid);
                },

                // Exchange
                exchangeAmount: function() {
                    return Math.max(0, (this.payment.customerGave || 0) - this.totalPaid);
                },

                // Today formatted
                today: function() {
                    return new Date().toLocaleDateString('en-BD', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            },

            methods: {
                // ── FORMAT ──
                fmt: function(n) {
                    return Math.round(n || 0).toLocaleString('en-IN');
                },

                // ── PRODUCTS ──
                recalcProduct: function(i) {
                    // Vue's reactivity handles it via computed; just force update if needed
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
                    this.products.push({
                        id: Date.now(),
                        name: 'New Product',
                        img: '',
                        unit: 0,
                        discount_amount: 0,
                        qty: 1,
                        variant: null,
                        warehouse: '',
                        barcode: ''
                    });
                },

                // ── PAYMENT ──
                togglePayMethod: function(m) {
                    m.active = !m.active;
                    if (!m.active) m.amount = 0;
                },

                // ── COUPON ──
                applyCoupon: function() {
                    // Stub: hook real logic here
                    alert('Coupon applied (stub). Code: ' + this.summary.couponCode);
                },

                // ── STATUS ──
                // (handled inline with v-model / @click)

                // ── WAREHOUSE MODAL ──
                openWhModal: function(idx) {
                    this.whModal.open = true;
                    this.whModal.productIdx = idx;
                    this.whModal.productName = this.products[idx].name.substring(0, 35) + '…';
                    this.whModal.warehouse = null;
                    this.whModal.room = null;
                    this.whModal.carton = null;
                    this.whModal.barcode = '';
                },

                closeWhModal: function() {
                    this.whModal.open = false;
                },

                selectWarehouse: function(name) {
                    this.whModal.warehouse = name;
                    this.whModal.room = null;
                    this.whModal.carton = null;
                    this.whModal.barcode = '';
                },

                selectRoom: function(name) {
                    this.whModal.room = name;
                    this.whModal.carton = null;
                    this.whModal.barcode = '';
                },

                selectCarton: function(name) {
                    this.whModal.carton = name;
                    this.whModal.barcode = '';
                    var self = this;
                    this.$nextTick(function() {
                        if (self.$refs.barcodeInput) self.$refs.barcodeInput.focus();
                    });
                },

                confirmBarcode: function() {
                    var bc = (this.whModal.barcode || '').trim();
                    if (!bc) {
                        alert('Please scan or enter a barcode.');
                        return;
                    }
                    var idx = this.whModal.productIdx;
                    if (idx !== null) {
                        this.$set(this.products, idx, Object.assign({}, this.products[idx], {
                            warehouse: this.whModal.warehouse,
                            barcode: bc
                        }));
                    }
                    this.closeWhModal();
                    alert('✓ Barcode "' + bc + '" confirmed!\nLocation: ' + this.whModal.warehouse + ' › ' +
                        this.whModal.room + ' › ' + this.whModal.carton);
                },

                // ── ORDER CONFIRM ──
                confirmOrder: function() {
                    this.whModal.invoiceOpen = false;
                    var self = this;
                    this.showToast = true;
                    setTimeout(function() {
                        self.showToast = false;
                    }, 3000);
                },

                // ── RESET ──
                resetForm: function() {
                    if (confirm('Reset all changes?')) location.reload();
                }
            }
        });
    </script>
@endpush
