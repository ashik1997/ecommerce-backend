@extends('backend.master')

@section('title', 'Create Stock Adjustment')

@section('content')

<style>
/* =============================================
   STOCK ADJUSTMENT COMPONENT — TEAL BRAND
   Prefix: .stock_adjust_component
   ============================================= */

.stock_adjust_component *,
.stock_adjust_component *::before,
.stock_adjust_component *::after {
    box-sizing: border-box;
}

.stock_adjust_component {
    --teal-50:  #f0fdfa;
    --teal-100: #ccfbf1;
    --teal-200: #99f6e4;
    --teal-400: #2dd4bf;
    --teal-500: #14b8a6;
    --teal-600: #0d9488;
    --teal-700: #0f766e;
    --teal-800: #115e59;
    --teal-900: #134e4a;

    --gray-50:  #f8fafc;
    --gray-100: #f1f5f9;
    --gray-200: #e2e8f0;
    --gray-300: #cbd5e1;
    --gray-400: #94a3b8;
    --gray-500: #64748b;
    --gray-600: #475569;
    --gray-700: #334155;
    --gray-800: #1e293b;
    --gray-900: #0f172a;

    --success:  #10b981;
    --danger:   #ef4444;
    --warning:  #f59e0b;
    --info:     #3b82f6;

    --radius-sm: 6px;
    --radius:    10px;
    --radius-lg: 14px;
    --radius-xl: 20px;

    --shadow-xs: 0 1px 2px rgba(0,0,0,.06);
    --shadow-sm: 0 1px 4px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04);
    --shadow:    0 4px 12px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04);

    font-family: 'Nunito', 'Segoe UI', sans-serif;
    color: var(--gray-800);
    font-size: 14px;
    line-height: 1.5;
}

/* ── PAGE HEADER ──────────────────────────── */
.stock_adjust_component .sa-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 10px;
}

.stock_adjust_component .sa-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stock_adjust_component .sa-header-icon {
    width: 38px;
    height: 38px;
    background: var(--teal-500);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 16px;
    flex-shrink: 0;
}

.stock_adjust_component .sa-header-title h4 {
    font-size: 17px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0;
    letter-spacing: -0.2px;
}

.stock_adjust_component .sa-header-title span {
    font-size: 12px;
    color: var(--gray-400);
    display: block;
    margin-top: 1px;
}

.stock_adjust_component .sa-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: var(--radius);
    font-size: 13px;
    font-weight: 600;
    border: 1.5px solid var(--gray-200);
    background: #fff;
    color: var(--gray-600);
    text-decoration: none;
    transition: all .15s;
    box-shadow: var(--shadow-xs);
}

.stock_adjust_component .sa-btn-back:hover {
    border-color: var(--teal-400);
    color: var(--teal-600);
    background: var(--teal-50);
    text-decoration: none;
}

/* ── CARD ─────────────────────────────────── */
.stock_adjust_component .sa-card {
    background: #fff;
    border-radius: var(--radius-lg);
    border: 1.5px solid var(--gray-200);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

/* ── SEARCH SECTION ───────────────────────── */
.stock_adjust_component .sa-search-section {
    padding: 20px 20px 0;
}

.stock_adjust_component .sa-search-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 8px;
}

.stock_adjust_component .sa-search-label i {
    color: var(--teal-500);
}

/* ── PRODUCT INFO STRIP ───────────────────── */
.stock_adjust_component .sa-product-strip {
    margin: 14px 20px 0;
    padding: 12px 14px;
    background: var(--teal-50);
    border: 1.5px solid var(--teal-100);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: sa-fadein .2s ease;
}

@keyframes sa-fadein {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.stock_adjust_component .sa-product-icon {
    width: 36px;
    height: 36px;
    background: var(--teal-500);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 14px;
    flex-shrink: 0;
}

.stock_adjust_component .sa-product-info {
    flex: 1;
    min-width: 0;
}

.stock_adjust_component .sa-product-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--teal-800);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin: 0 0 3px;
}

.stock_adjust_component .sa-product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 12px;
    color: var(--teal-700);
}

.stock_adjust_component .sa-stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.stock_adjust_component .sa-stock-badge.in  { background: #d1fae5; color: #065f46; }
.stock_adjust_component .sa-stock-badge.out { background: #fee2e2; color: #991b1b; }

/* ── DIVIDER ──────────────────────────────── */
.stock_adjust_component .sa-divider {
    height: 1px;
    background: var(--gray-100);
    margin: 16px 20px;
}

/* ── SECTION TITLE ────────────────────────── */
.stock_adjust_component .sa-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: var(--gray-400);
    padding: 0 20px;
    margin-bottom: 10px;
}

/* ── FORM GRID ────────────────────────────── */
.stock_adjust_component .sa-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 0 20px;
}

@media (max-width: 600px) {
    .stock_adjust_component .sa-form-grid {
        grid-template-columns: 1fr;
    }
}

.stock_adjust_component .sa-form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.stock_adjust_component .sa-form-group.full {
    grid-column: 1 / -1;
}

.stock_adjust_component .sa-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-600);
    display: flex;
    align-items: center;
    gap: 5px;
}

.stock_adjust_component .sa-label i {
    color: var(--teal-400);
    font-size: 11px;
}

.stock_adjust_component .sa-label .req {
    color: var(--danger);
}

.stock_adjust_component .sa-input,
.stock_adjust_component .sa-select,
.stock_adjust_component .sa-textarea {
    width: 100%;
    padding: 8px 10px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 13px;
    color: var(--gray-800);
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
    outline: none;
    font-family: inherit;
}

.stock_adjust_component .sa-input:focus,
.stock_adjust_component .sa-select:focus,
.stock_adjust_component .sa-textarea:focus {
    border-color: var(--teal-400);
    box-shadow: 0 0 0 3px rgba(20,184,166,.12);
}

.stock_adjust_component .sa-input[readonly] {
    background: var(--gray-50);
    color: var(--gray-500);
    cursor: default;
}

.stock_adjust_component .sa-input.preview {
    background: var(--teal-50);
    color: var(--teal-700);
    font-weight: 700;
    font-size: 15px;
    letter-spacing: -0.3px;
}

.stock_adjust_component .sa-textarea {
    resize: none;
    line-height: 1.5;
}

.stock_adjust_component .sa-hint {
    font-size: 11px;
    color: var(--gray-400);
    margin-top: 2px;
}

/* ── QTY BLOCK ────────────────────────────── */
.stock_adjust_component .sa-qty-block {
    margin: 14px 20px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
    animation: sa-fadein .2s ease;
}

.stock_adjust_component .sa-qty-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: var(--gray-50);
    border-bottom: 1.5px solid var(--gray-200);
}

.stock_adjust_component .sa-qty-header-left {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-700);
}

.stock_adjust_component .sa-qty-header-left i {
    color: var(--teal-500);
}

.stock_adjust_component .sa-qty-body {
    padding: 14px;
}

/* Stock display row */
.stock_adjust_component .sa-stock-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}

@media (max-width: 480px) {
    .stock_adjust_component .sa-stock-row {
        grid-template-columns: 1fr;
    }
}

.stock_adjust_component .sa-stock-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stock_adjust_component .sa-stock-cell-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
}

/* Scan + Qty row */
.stock_adjust_component .sa-scan-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 10px;
    padding: 10px;
    background: var(--gray-50);
    border-radius: var(--radius);
    border: 1.5px dashed var(--gray-200);
    margin-bottom: 10px;
}

@media (max-width: 480px) {
    .stock_adjust_component .sa-scan-row {
        grid-template-columns: 1fr;
    }
}

.stock_adjust_component .sa-scan-row .sa-form-group {
    margin: 0;
}

.stock_adjust_component .sa-qty-input-wrap {
    width: 90px;
}

@media (max-width: 480px) {
    .stock_adjust_component .sa-qty-input-wrap {
        width: 100%;
    }
}

/* Barcode table */
.stock_adjust_component .sa-barcode-table-wrap {
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    overflow: hidden;
    /* max-height: 230px; */
    overflow-y: auto;
    animation: sa-fadein .15s ease;
}

.stock_adjust_component .sa-barcode-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.stock_adjust_component .sa-barcode-table thead th {
    padding: 7px 10px;
    background: var(--teal-600);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    z-index: 1;
}

.stock_adjust_component .sa-barcode-table thead th:first-child { width: 44px; }

.stock_adjust_component .sa-barcode-table tbody tr {
    border-bottom: 1px solid var(--gray-100);
    transition: background .1s;
}

.stock_adjust_component .sa-barcode-table tbody tr:last-child {
    border-bottom: none;
}

.stock_adjust_component .sa-barcode-table tbody tr:hover {
    background: var(--teal-50);
}

.stock_adjust_component .sa-barcode-table td {
    padding: 5px 8px;
    color: var(--gray-600);
    vertical-align: middle;
}

.stock_adjust_component .sa-barcode-table td:first-child {
    font-size: 11px;
    color: var(--gray-400);
    text-align: center;
}

.stock_adjust_component .sa-barcode-table .sa-input {
    border-color: transparent;
    background: transparent;
    padding: 5px 8px;
    font-size: 13px;
}

.stock_adjust_component .sa-barcode-table .sa-input:focus {
    background: #fff;
    border-color: var(--teal-400);
}

.stock_adjust_component .sa-barcode-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--teal-500);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    margin-left: 6px;
}

/* ── VARIANT ATTR GRID ────────────────────── */
.stock_adjust_component .sa-attr-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 10px;
    margin-bottom: 12px;
}

/* Combination pill */
.stock_adjust_component .sa-combination-strip {
    padding: 9px 12px;
    background: var(--teal-50);
    border: 1.5px solid var(--teal-200);
    border-radius: var(--radius);
    font-size: 12px;
    color: var(--teal-700);
    font-weight: 600;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 10px;
}

.stock_adjust_component .sa-combination-strip i {
    color: var(--teal-400);
    margin-right: 4px;
}

.stock_adjust_component .sa-combination-strip .sa-comb-pair {
    background: var(--teal-100);
    color: var(--teal-800);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

/* No match warning */
.stock_adjust_component .sa-no-match {
    font-size: 12px;
    color: var(--danger);
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 10px;
    background: #fff1f1;
    border-radius: var(--radius-sm);
    border: 1px solid #fecaca;
}

/* ── FOOTER ACTIONS ───────────────────────── */
.stock_adjust_component .sa-footer {
    padding: 14px 20px;
    border-top: 1.5px solid var(--gray-100);
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    background: var(--gray-50);
}

/* ── BUTTONS ──────────────────────────────── */
.stock_adjust_component .sa-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: var(--radius);
    font-size: 13px;
    font-weight: 700;
    border: 1.5px solid transparent;
    cursor: pointer;
    transition: all .15s;
    white-space: nowrap;
    font-family: inherit;
    line-height: 1;
}

.stock_adjust_component .sa-btn:disabled {
    opacity: .6;
    cursor: not-allowed;
}

.stock_adjust_component .sa-btn-primary {
    background: var(--teal-600);
    border-color: var(--teal-600);
    color: #fff;
    box-shadow: 0 2px 6px rgba(13,148,136,.3);
}

.stock_adjust_component .sa-btn-primary:hover:not(:disabled) {
    background: var(--teal-700);
    border-color: var(--teal-700);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(13,148,136,.35);
}

.stock_adjust_component .sa-btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

.stock_adjust_component .sa-btn-ghost {
    background: #fff;
    border-color: var(--gray-200);
    color: var(--gray-600);
}

.stock_adjust_component .sa-btn-ghost:hover {
    border-color: var(--gray-300);
    background: var(--gray-50);
    color: var(--gray-800);
}

.stock_adjust_component .sa-btn-outline-danger {
    background: transparent;
    border-color: #fca5a5;
    color: var(--danger);
    font-size: 12px;
    padding: 5px 10px;
}

.stock_adjust_component .sa-btn-outline-danger:hover {
    background: #fff1f1;
}

.stock_adjust_component .sa-btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

/* ── SPINNER ──────────────────────────────── */
.stock_adjust_component .sa-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: sa-spin .6s linear infinite;
    display: inline-block;
}

@keyframes sa-spin {
    to { transform: rotate(360deg); }
}

/* ── EMPTY STATE ──────────────────────────── */
.stock_adjust_component .sa-empty-state {
    padding: 28px 20px;
    text-align: center;
}

.stock_adjust_component .sa-empty-state-icon {
    width: 52px;
    height: 52px;
    background: var(--teal-50);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
    color: var(--teal-400);
}

.stock_adjust_component .sa-empty-state h5 {
    font-size: 14px;
    font-weight: 700;
    color: var(--gray-700);
    margin: 0 0 6px;
}

.stock_adjust_component .sa-empty-state p {
    font-size: 13px;
    color: var(--gray-400);
    margin: 0;
    max-width: 280px;
    margin: 0 auto;
    line-height: 1.5;
}

.stock_adjust_component .sa-steps {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 14px;
}

.stock_adjust_component .sa-step {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--gray-500);
    padding: 4px 10px;
    border-radius: 20px;
    background: var(--gray-100);
    font-weight: 600;
}

.stock_adjust_component .sa-step-num {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--teal-500);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ── SELECT2 TWEAKS ───────────────────────── */
.stock_adjust_component .select2-container .select2-selection--single {
    height: 38px !important;
    border: 1.5px solid var(--gray-200) !important;
    border-radius: var(--radius) !important;
    display: flex;
    align-items: center;
    transition: border-color .15s, box-shadow .15s;
}

.stock_adjust_component .select2-container--default.select2-container--open .select2-selection--single,
.stock_adjust_component .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--teal-400) !important;
    box-shadow: 0 0 0 3px rgba(20,184,166,.12) !important;
}

.stock_adjust_component .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: normal !important;
    padding: 0 10px !important;
    color: var(--gray-800) !important;
    font-size: 13px;
}

.stock_adjust_component .select2-container .select2-selection--single .select2-selection__placeholder {
    color: var(--gray-400) !important;
}

.stock_adjust_component .select2-container .select2-selection--single .select2-selection__arrow {
    top: 50% !important;
    transform: translateY(-50%) !important;
    right: 8px !important;
}

/* ── RESPONSIVE ───────────────────────────── */
@media (max-width: 480px) {
    .stock_adjust_component .sa-footer {
        flex-direction: column-reverse;
    }
    .stock_adjust_component .sa-footer .sa-btn {
        width: 100%;
        justify-content: center;
    }
    .stock_adjust_component .sa-search-section {
        padding: 14px 14px 0;
    }
    .stock_adjust_component .sa-product-strip,
    .stock_adjust_component .sa-divider,
    .stock_adjust_component .sa-section-title,
    .stock_adjust_component .sa-qty-block {
        margin-left: 14px;
        margin-right: 14px;
    }
    .stock_adjust_component .sa-form-grid {
        padding: 0 14px;
    }
    .stock_adjust_component .sa-footer {
        padding: 12px 14px;
    }
}
</style>

<div class="stock_adjust_component" id="stockAdjustmentApp">

    <!-- Page Header -->
    <div class="sa-header">
        <div class="sa-header-title">
            <div class="sa-header-icon">
                <i class="fas fa-sliders-h"></i>
            </div>
            <div>
                <h4>Stock Adjustment</h4>
                <span>Add or remove stock with full traceability</span>
            </div>
        </div>
        <a href="{{ route('stock-adjustment.index') }}" class="sa-btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Main Card -->
    <div class="sa-card">
        <form @submit.prevent="submitAdjustment">

            <!-- Search -->
            <div class="sa-search-section">
                <div class="sa-search-label">
                    <i class="fas fa-search"></i> Search Product
                </div>
                <select id="productSelect" style="width:100%;">
                    <option value="">Search by name, code, SKU, or barcode…</option>
                </select>
            </div>

            <!-- Product Strip -->
            <div v-if="selectedProduct" class="sa-product-strip">
                <div class="sa-product-icon"><i class="fas fa-box"></i></div>
                <div class="sa-product-info">
                    <div class="sa-product-name">@{{ selectedProduct.name }}</div>
                    <div class="sa-product-meta">
                        <span><b>Code:</b> @{{ selectedProduct.code }}</span>
                        <span v-if="selectedProduct.sku"><b>SKU:</b> @{{ selectedProduct.sku }}</span>
                        <span class="sa-stock-badge" :class="selectedProduct.stock > 0 ? 'in' : 'out'">
                            <i class="fas fa-cubes" style="font-size:10px;"></i>
                            @{{ selectedProduct.stock }} in stock
                        </span>
                    </div>
                </div>
            </div>

            <!-- Adjustment Details (shown after product select) -->
            <template v-if="selectedProduct">
                <div class="sa-divider"></div>
                <div class="sa-section-title">Adjustment Details</div>

                <div class="sa-form-grid">
                    <!-- Type -->
                    <div class="sa-form-group">
                        <label class="sa-label">
                            <i class="fas fa-tag"></i> Type <span class="req">*</span>
                        </label>
                        <select v-model="adjustmentType" class="sa-select" required>
                            <option value="">Select type…</option>
                            <optgroup label="Add Stock">
                                <option value="purchase">Purchase</option>
                                <option value="return">Return</option>
                                <option value="initial">Initial Stock</option>
                                <option value="manual add">Manual Add</option>
                            </optgroup>
                            <optgroup label="Reduce Stock">
                                <option value="sales">Sales</option>
                                <option value="waste">Waste</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="sa-form-group">
                        <label class="sa-label">
                            <i class="fas fa-pen"></i> Description
                        </label>
                        <textarea v-model="description" class="sa-textarea" rows="2"
                            placeholder="Optional notes…"></textarea>
                    </div>
                </div>
            </template>

            <!-- ─── SINGLE PRODUCT QTY ──────────────── -->
            <div v-if="selectedProduct && !selectedProduct.has_variants && adjustmentType" class="sa-qty-block">
                <div class="sa-qty-header">
                    <div class="sa-qty-header-left">
                        <i class="fas fa-calculator"></i>
                        Quantity & Barcodes
                    </div>
                    <button type="button" @click="clearSingleBarcodes" class="sa-btn sa-btn-outline-danger sa-btn-sm">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
                <div class="sa-qty-body">

                    <!-- Stock preview -->
                    <div class="sa-stock-row">
                        <div class="sa-stock-cell">
                            <div class="sa-stock-cell-label">Current Stock</div>
                            <input type="text" :value="selectedProduct.stock" class="sa-input" readonly>
                        </div>
                        <div class="sa-stock-cell">
                            <div class="sa-stock-cell-label">New Stock (Preview)</div>
                            <input type="text" :value="calculateNewStock()" class="sa-input preview" readonly>
                        </div>
                    </div>

                    <!-- Scan row -->
                    <div class="sa-scan-row">
                        <div class="sa-form-group">
                            <label class="sa-label"><i class="fas fa-barcode"></i> Scan Barcode</label>
                            <input type="text" class="sa-input"
                                v-model="singleBarcodeEntry"
                                @keydown.enter.prevent="addSingleBarcodeFromEntry"
                                placeholder="Scan or type → Enter to add">
                        </div>
                        <div class="sa-form-group sa-qty-input-wrap">
                            <label class="sa-label"><i class="fas fa-hashtag"></i> Qty <span class="req">*</span></label>
                            <input type="number" class="sa-input" min="0" step="1"
                                v-model.number="singleQuantity"
                                @input="onSingleQtyChange"
                                placeholder="0">
                        </div>
                    </div>
                    <div class="sa-hint"><i class="fas fa-info-circle"></i> Scan barcodes one-by-one (qty auto-increments), or enter qty manually.</div>

                    <!-- Barcode list -->
                    <div v-if="singleQuantity > 0" class="sa-barcode-table-wrap" style="margin-top:10px;">
                        <table class="sa-barcode-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>
                                        Barcode
                                        <span class="sa-barcode-count">@{{ singleQuantity }}</span>
                                    </th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(code, i) in singleBarcodes" :key="i">
                                    <td>@{{ i + 1 }}</td>
                                    <td>
                                        <input type="text" class="sa-input"
                                            v-model="singleBarcodes[i]"
                                            placeholder="Enter or scan barcode">
                                    </td>
                                    <td>
                                        <button type="button" class="sa-btn sa-btn-outline-danger sa-btn-sm" @click="removeSingleBarcode(i)" title="Remove barcode">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ─── VARIANT QTY ─────────────────────── -->
            <div v-if="selectedProduct && selectedProduct.has_variants && adjustmentType" class="sa-qty-block">
                <div class="sa-qty-header">
                    <div class="sa-qty-header-left">
                        <i class="fas fa-th-list"></i>
                        Variant Selection & Barcodes
                    </div>
                    <button type="button" @click="clearBarcodes" class="sa-btn sa-btn-outline-danger sa-btn-sm">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
                <div class="sa-qty-body">

                    <!-- Attribute selects -->
                    <div class="sa-attr-grid">
                        <div class="sa-form-group" v-for="attr in getVariantAttributes()" :key="attr">
                            <label class="sa-label" style="text-transform:capitalize;">@{{ attr }}</label>
                            <select class="sa-select"
                                v-model="variantSelections[attr]"
                                @change="onVariantSelectionChange">
                                <option value="">— @{{ attr }}</option>
                                <option v-for="val in selectedProduct.product_variants[attr]" :key="val" :value="val">
                                    @{{ val }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Combination pill -->
                    <div v-if="getCombinationLabel()">
                        <div v-if="matchedVariant" class="sa-combination-strip">
                            <i class="fas fa-check-circle"></i>
                            <span v-for="(value, key) in matchedVariant" :key="key">
                                <span v-if="key !== 'id'" class="sa-comb-pair">@{{ key }}: @{{ value }}</span>
                            </span>
                        </div>
                        <div v-else class="sa-no-match">
                            <i class="fas fa-exclamation-circle"></i>
                            No matching combination found for selected attributes.
                        </div>
                    </div>

                    <!-- Scan row (only when variant matched) -->
                    <div v-if="matchedVariant" class="sa-scan-row">
                        <div class="sa-form-group">
                            <label class="sa-label"><i class="fas fa-barcode"></i> Scan Barcode</label>
                            <input type="text" class="sa-input"
                                v-model="barcodeEntry"
                                @keydown.enter.prevent="addBarcodeFromEntry"
                                placeholder="Scan or type → Enter to add">
                        </div>
                        <div class="sa-form-group sa-qty-input-wrap">
                            <label class="sa-label"><i class="fas fa-hashtag"></i> Qty</label>
                            <input type="number" class="sa-input" min="0" step="1"
                                v-model.number="variantQty"
                                @input="onVariantQtyChange"
                                placeholder="0">
                        </div>
                    </div>
                    <div class="sa-hint"><i class="fas fa-info-circle"></i> Select variant attributes, then scan/enter barcodes or type quantity.</div>

                    <!-- Barcode list -->
                    <div v-if="variantQty > 0" class="sa-barcode-table-wrap" style="margin-top:10px;">
                        <table class="sa-barcode-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>
                                        Barcode
                                        <span class="sa-barcode-count">@{{ variantQty }}</span>
                                    </th>
                                    <th style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(code, i) in variantBarcodes" :key="i">
                                    <td>@{{ i + 1 }}</td>
                                    <td>
                                        <input type="text" class="sa-input"
                                            v-model="variantBarcodes[i]"
                                            placeholder="Enter or scan barcode">
                                    </td>
                                    <td>
                                        <button type="button" class="sa-btn sa-btn-outline-danger sa-btn-sm" @click="removeVariantBarcode(i)" title="Remove barcode">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div v-if="selectedProduct && adjustmentType" class="sa-footer">
                <button type="button" @click="resetForm" class="sa-btn sa-btn-ghost">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="sa-btn sa-btn-primary" :disabled="isSubmitting">
                    <span v-if="!isSubmitting"><i class="fas fa-save"></i> Save Adjustment</span>
                    <span v-else><span class="sa-spinner"></span> Saving…</span>
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="!selectedProduct" class="sa-empty-state">
                <div class="sa-empty-state-icon"><i class="fas fa-boxes"></i></div>
                <h5>Search for a product to begin</h5>
                <p>Use the search bar above to find a product by name, code, SKU, or barcode.</p>
                <div class="sa-steps">
                    <span class="sa-step"><span class="sa-step-num">1</span> Search product</span>
                    <span class="sa-step"><span class="sa-step-num">2</span> Select type</span>
                    <span class="sa-step"><span class="sa-step-num">3</span> Enter qty &amp; barcodes</span>
                    <span class="sa-step"><span class="sa-step-num">4</span> Save</span>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@push('css')
<link href="{{url('assets')}}/plugins/select2/select2.min.css" rel="stylesheet" type="text/css" />
@endpush

@push('js')
<script src="{{url('assets')}}/plugins/select2/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ asset('assets/js/vue.min.js') }}"></script>
<script src="{{ asset('assets/js/stock_adjustment_vue.js') }}?v={{ env('APP_VERSION', time()) }}"></script>
@endpush