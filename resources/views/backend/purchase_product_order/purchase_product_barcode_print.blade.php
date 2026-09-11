@extends('backend.master')

@section('header_css')
<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.7.15/vue.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<style>
    .barcode-builder-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(520px, 640px);
        gap: 16px;
        align-items: start;
    }

    .builder-card {
        border: 1px solid #e4e9f0;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }

    .builder-card-header {
        padding: 12px 14px;
        border-bottom: 1px solid #edf1f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }

    .builder-card-body { padding: 14px; }
    .barcode-table-wrap { max-height: 560px; overflow: auto; }
    .barcode-table th { position: sticky; top: 0; background: #f8fafc; z-index: 1; white-space: nowrap; }
    .barcode-table td { vertical-align: middle !important; }

    .product-mini {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 9px;
        align-items: center;
        min-width: 260px;
    }

    .product-mini img {
        width: 42px;
        height: 42px;
        border-radius: 5px;
        object-fit: cover;
        border: 1px solid #dde5ee;
        background: #f4f7fb;
    }

    .product-mini-title {
        font-weight: 700;
        color: #17233c;
        line-height: 1.25;
    }

    .product-mini-sub {
        color: #67758a;
        font-size: 12px;
        margin-top: 2px;
    }

    .qty-input { width: 82px; }
    .label-size-select { min-width: 128px; }
    .warning-text { color: #d97706; font-size: 11px; font-weight: 700; margin-top: 3px; }

    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .line-editor {
        border: 1px solid #edf1f5;
        border-radius: 6px;
        padding: 9px;
        margin-bottom: 8px;
        background: #fbfcfe;
    }

    .line-editor-grid {
        display: grid;
        grid-template-columns: 1fr 72px 92px;
        gap: 6px;
        align-items: center;
    }

    .preview-stage {
        overflow: auto;
        padding: 28px 18px 80px;
        background: #f5f7fa;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
        display: flex;
        justify-content: center;
        min-height: 270px;
    }

    .barcode-label {
        position: relative;
        background: #fff;
        border: 1px solid #1f2937;
        overflow: hidden;
        box-sizing: border-box;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .label-line {
        position: absolute;
        cursor: move;
        user-select: none;
        line-height: 1.15;
        overflow: hidden;
        white-space: nowrap;
        padding: 1px 2px;
        box-sizing: border-box;
    }

    .label-line.active { outline: 1px dashed #0ea5e9; background: rgba(14, 165, 233, .08); }
    .label-line del { color: #667085; margin-left: 4px; }
    .label-price-final { font-weight: 800; }

    .barcode-element {
        position: absolute;
        cursor: move;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .barcode-element svg { width: 100%; height: 100%; }
    .print-area { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 2mm; }
    .print-label { margin: 0; border-color: transparent; }

    @media (max-width: 1199.98px) {
        .barcode-builder-shell { grid-template-columns: 1fr; }
    }

    @media print {
        .no-print, .main-menu, .navbar, .footer, .page-title-box { display: none !important; }
        @page { margin: 0; }
        body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
        .content-page, .content, .container-fluid { margin: 0 !important; padding: 0 !important; }
        .print-area { display: flex !important; gap: 0 !important; }
        .print-area.print-mode-label { display: block !important; }
        .print-area.print-mode-label .print-label { page-break-after: always; break-after: page; }
        .barcode-label { page-break-inside: avoid; break-inside: avoid; border: 0 !important; }
    }
    .barcode-card svg {
        max-width: 80%;
        height: auto;
    }
</style>
@endsection

@section('page_title')
    Purchase Product Order Barcode Print
@endsection
@section('page_heading')
    Barcode Print Builder for Purchase Order #{{ $purchase_id }}
@endsection

@section('content')
<div id="purchase_product_barcode_print">
    <div class="barcode-builder-shell no-print">
        <div class="builder-card">
            <div class="builder-card-header">
                <div>
                    <h5 class="mb-0">Grouped Barcode List</h5>
                    <small class="text-muted">Same barcode is shown once with available stock quantity.</small>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="loadGroups">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="builder-card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" v-model.trim="filters.search" class="form-control form-control-sm" placeholder="Product, barcode, SKU">
                    </div>
                    <div class="col-md-3">
                        <input type="text" v-model.trim="filters.supplier" class="form-control form-control-sm" placeholder="Supplier">
                    </div>
                    <div class="col-md-3">
                        <input type="text" v-model.trim="filters.category" class="form-control form-control-sm" placeholder="Category / Brand">
                    </div>
                    <div class="col-md-2">
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" class="custom-control-input" id="stockOnly" v-model="filters.stockOnly">
                            <label class="custom-control-label" for="stockOnly">In stock</label>
                        </div>
                    </div>
                </div>

                <div class="barcode-table-wrap">
                    <table class="table table-bordered table-hover barcode-table mb-0">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Details</th>
                                <th>Print Qty</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="9" class="text-center py-4">Loading barcodes...</td>
                            </tr>
                            <tr v-if="!loading && filteredGroups.length === 0">
                                <td colspan="9" class="text-center text-muted py-4">No barcode group found.</td>
                            </tr>
                            <tr v-for="(group, index) in filteredGroups" :key="group.id" :class="{'table-info': selectedGroup && selectedGroup.id === group.id}">
                                <td>@{{ index + 1 }}</td>
                                <td>
                                    <div class="product-mini">
                                        <img :src="group.product_image" v-on:error="imageFallback">
                                        <div>
                                            <div class="product-mini-title">@{{ group.product_name }}</div>
                                            <div class="product-mini-sub">
                                                <span v-if="group.variant_title">@{{ group.variant_title }}</span>
                                                <span v-if="group.sku"> · SKU: @{{ group.sku }}</span>
                                                <span v-if="group.has_imei"> · IMEI</span>
                                                <span v-if="group.has_warranty"> · Warranty</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><code>@{{ group.barcode }}</code></td>
                                <td>
                                    <span class="badge badge-success">@{{ group.available_qty }}</span>
                                    <small class="d-block text-muted">Total @{{ group.total_qty }}</small>
                                </td>
                                <td>
                                    <strong>৳@{{ formatPrice(activePrice(group)) }}</strong>
                                    <small v-if="group.discount_price > 0 && group.sales_price > group.discount_price" class="d-block text-muted">
                                        <del>৳@{{ formatPrice(group.sales_price) }}</del>
                                    </small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" @click="showDetails(group)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm qty-input" v-model.number="group.print_quantity" @input="validateQty(group)">
                                    <div class="warning-text" v-if="group.print_quantity > group.available_qty">Over stock</div>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm label-size-select" v-model="group.sizeKey" @change="selectGroup(group)">
                                        <option v-for="size in labelSizes" :value="size.key">@{{ size.label }}</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" @click="selectGroup(group)">
                                        Customize
                                    </button>
                                    <button class="btn btn-sm btn-success mt-1" @click="printGroup(group)">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="builder-card">
            <div class="builder-card-header">
                <div>
                    <h5 class="mb-0">Label Builder</h5>
                    <small class="text-muted" v-if="selectedGroup">@{{ selectedGroup.product_name }}</small>
                    <small class="text-muted" v-else>Select a barcode to customize.</small>
                </div>
                <button class="btn btn-sm btn-outline-secondary" @click="resetTemplate" :disabled="!selectedGroup">Reset</button>
            </div>
            <div class="builder-card-body">
                <div v-if="!selectedGroup" class="text-center text-muted py-4">
                    Choose a barcode from the list to preview and print.
                </div>

                <div v-else>
                    <div class="settings-grid mb-3">
                        <div>
                            <label class="mb-1">Label Size</label>
                            <select class="form-control form-control-sm" v-model="selectedGroup.sizeKey" @change="applyGroupSize">
                                <option v-for="size in labelSizes" :value="size.key">@{{ size.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1">Print Quantity</label>
                            <input type="number" min="1" class="form-control form-control-sm" v-model.number="selectedGroup.print_quantity">
                        </div>
                        <div>
                            <label class="mb-1">Print Paper</label>
                            <select class="form-control form-control-sm" v-model="printMode">
                                <option value="label">Barcode Printer</option>
                                <option value="a4">A4 Sheet</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1">Preview Zoom</label>
                            <input type="range" min="1" max="2.8" step="0.1" class="form-control-range" v-model.number="previewZoom">
                            <small class="text-muted">@{{ previewZoom.toFixed(1) }}x</small>
                        </div>
                        <div>
                            <label class="mb-1">Regular Price</label>
                            <input type="number" min="0" step="0.01" class="form-control form-control-sm" v-model.number="selectedGroup.label_regular_price" @input="renderBarcodes">
                        </div>
                        <div>
                            <label class="mb-1">Discount Price</label>
                            <div class="input-group input-group-sm">
                                <input type="number" min="0" step="0.01" class="form-control" v-model.number="selectedGroup.label_discount_price" @input="renderBarcodes">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" @click="resetSelectedPrice" type="button">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedSize.key === 'custom'" class="settings-grid mb-3">
                        <div>
                            <label class="mb-1">Width</label>
                            <input type="number" min="0.5" step="0.1" class="form-control form-control-sm" v-model.number="customSize.width">
                        </div>
                        <div>
                            <label class="mb-1">Height</label>
                            <input type="number" min="0.5" step="0.1" class="form-control form-control-sm" v-model.number="customSize.height">
                        </div>
                    </div>

                    <div class="mb-2">
                        <button class="btn btn-sm btn-outline-primary" @click="addLine('top')">
                            <i class="fas fa-plus"></i> Top Line
                        </button>
                        <button class="btn btn-sm btn-outline-primary" @click="addLine('bottom')">
                            <i class="fas fa-plus"></i> Bottom Line
                        </button>
                        <button class="btn btn-sm btn-outline-success" @click="addPriceLine">
                            <i class="fas fa-plus"></i> Price
                        </button>
                        <small class="d-block text-muted mt-1" v-pre>Placeholders: @{{product_name}}, @{{variant_name}}, @{{barcode}}, @{{price_block}}, @{{serial_no}}, @{{imei_1}}, @{{imei_2}}, @{{warranty_end}}</small>
                    </div>

                    <div class="line-editor" v-for="line in template.lines" :key="line.id">
                        <div class="line-editor-grid">
                            <input class="form-control form-control-sm" v-model="line.text" placeholder="@{{product_name}}">
                            <input type="number" class="form-control form-control-sm" v-model.number="line.font_size" min="6" max="24">
                            <select class="form-control form-control-sm" v-model="line.font_weight">
                                <option value="normal">Normal</option>
                                <option value="bold">Bold</option>
                            </select>
                        </div>
                        <div class="line-editor-grid mt-2">
                            <select class="form-control form-control-sm" v-model="line.align">
                                <option value="left">Left</option>
                                <option value="center">Center</option>
                                <option value="right">Right</option>
                            </select>
                            <input type="number" class="form-control form-control-sm" v-model.number="line.x" min="0">
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" v-model.number="line.y" min="0">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-danger" @click="removeLine(line.id)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="preview-stage">
                        <div class="barcode-label" :style="previewLabelStyle" ref="previewLabel">
                            <div class="barcode-element"
                                 :style="barcodeStyle"
                                 @mousedown.prevent="startDrag($event, 'barcode', template.barcode)">
                                <svg id="preview-barcode"></svg>
                            </div>
                            <div v-for="line in visibleLines"
                                 :key="'preview-' + line.id"
                                 class="label-line"
                                 :class="{active: activeElement === line.id}"
                                 :style="lineStyle(line)"
                                 @mousedown.prevent="startDrag($event, line.id, line)">
                                <span v-html="renderLineHtml(line.text)"></span>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-success btn-block mt-3" @click="printSelected">
                        <i class="fas fa-print"></i> Print @{{ selectedGroup.print_quantity || 0 }} Labels
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="print-area" id="print-area" :class="printAreaClass">
        <div v-if="selectedGroup"
             v-for="n in printCopies"
             :key="'print-' + n"
             class="barcode-label print-label"
             :style="labelStyle">
            <div class="barcode-element" :style="barcodeStyle">
                <svg :id="'print-barcode-' + n"></svg>
            </div>
            <div v-for="line in visibleLines"
                 :key="'print-line-' + n + '-' + line.id"
                 class="label-line"
                 :style="lineStyle(line)">
                <span v-html="renderLineHtml(line.text)"></span>
            </div>
        </div>
    </div>

    <div class="modal fade" id="barcodeDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Barcode Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" v-if="detailsGroup">
                    <div class="row">
                        <div class="col-md-7">
                            <p><strong>Product:</strong> @{{ detailsGroup.product_name }}</p>
                            <p><strong>Barcode:</strong> <code>@{{ detailsGroup.barcode }}</code></p>
                            <p><strong>SKU:</strong> @{{ detailsGroup.sku || '-' }}</p>
                            <p><strong>Variant:</strong> @{{ detailsGroup.variant_title || '-' }}</p>
                            <p><strong>Category:</strong> @{{ detailsGroup.category || '-' }} | <strong>Brand:</strong> @{{ detailsGroup.brand || '-' }}</p>
                            <p><strong>Serial:</strong> @{{ detailsGroup.serial_no || '-' }}</p>
                            <p><strong>IMEI:</strong> @{{ imeiText(detailsGroup) || '-' }}</p>
                            <p><strong>Warranty End:</strong> @{{ detailsGroup.supplier_warranty_end_date || detailsGroup.customer_warranty_end_date || '-' }}</p>
                        </div>
                        <div class="col-md-5">
                            <p><strong>Supplier:</strong> @{{ detailsGroup.supplier || '-' }}</p>
                            <p><strong>Warehouse:</strong> @{{ detailsGroup.warehouse || '-' }}</p>
                            <p><strong>Available:</strong> @{{ detailsGroup.available_qty }}</p>
                            <p><strong>Total Purchased:</strong> @{{ detailsGroup.total_qty }}</p>
                            <p><strong>Sold:</strong> @{{ detailsGroup.sold_qty }}</p>
                        </div>
                    </div>
                    <h6 class="mt-3">Batch Summary</h6>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Purchase Date</th>
                                <th>Batch</th>
                                <th>Supplier</th>
                                <th>Purchased</th>
                                <th>Available</th>
                                <th>Purchase Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="batch in detailsGroup.batches">
                                <td>@{{ batch.purchase_date || '-' }}</td>
                                <td>@{{ batch.batch_no || '-' }}</td>
                                <td>@{{ batch.supplier || '-' }}</td>
                                <td>@{{ batch.total_qty }}</td>
                                <td>@{{ batch.available_qty }}</td>
                                <td>৳@{{ formatPrice(batch.purchase_price) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <h6 class="mt-3">Unit Tracking</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th>Serial</th>
                                    <th>IMEI 1</th>
                                    <th>IMEI 2</th>
                                    <th>Supplier Warranty</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="unit in detailsGroup.units">
                                    <td><code>@{{ unit.code || '-' }}</code></td>
                                    <td>@{{ unit.unit_status || '-' }}</td>
                                    <td>@{{ unit.serial_no || '-' }}</td>
                                    <td>@{{ unit.imei_1 || '-' }}</td>
                                    <td>@{{ unit.imei_2 || '-' }}</td>
                                    <td>@{{ unit.supplier_warranty_end_date || '-' }}</td>
                                    <td>@{{ unit.warranty_note || '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-3">
                        <input type="text" v-model.trim="filters.category" class="form-control form-control-sm" placeholder="Category / Brand">
                    </div>
                    <div class="col-md-2">
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" class="custom-control-input" id="stockOnly" v-model="filters.stockOnly">
                            <label class="custom-control-label" for="stockOnly">In stock</label>
                        </div>
                    </div>
                </div>

                <div class="barcode-table-wrap">
                    <table class="table table-bordered table-hover barcode-table mb-0">
                        <thead>
                            <tr>
                                <th>SL</th>
                                <th>Product</th>
                                <th>Barcode</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Details</th>
                                <th>Print Qty</th>
                                <th>Size</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="9" class="text-center py-4">Loading barcodes...</td>
                            </tr>
                            <tr v-if="!loading && filteredGroups.length === 0">
                                <td colspan="9" class="text-center text-muted py-4">No barcode group found.</td>
                            </tr>
                            <tr v-for="(group, index) in filteredGroups" :key="group.id" :class="{'table-info': selectedGroup && selectedGroup.id === group.id}">
                                <td>@{{ index + 1 }}</td>
                                <td>
                                    <div class="product-mini">
                                        <img :src="group.product_image" v-on:error="imageFallback">
                                        <div>
                                            <div class="product-mini-title">@{{ group.product_name }}</div>
                                            <div class="product-mini-sub">
                                                <span v-if="group.variant_title">@{{ group.variant_title }}</span>
                                                <span v-if="group.sku"> · SKU: @{{ group.sku }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><code>@{{ group.barcode }}</code></td>
                                <td>
                                    <span class="badge badge-success">@{{ group.available_qty }}</span>
                                    <small class="d-block text-muted">Total @{{ group.total_qty }}</small>
                                </td>
                                <td>
                                    <strong>৳@{{ formatPrice(activePrice(group)) }}</strong>
                                    <small v-if="group.discount_price > 0 && group.sales_price > group.discount_price" class="d-block text-muted">
                                        <del>৳@{{ formatPrice(group.sales_price) }}</del>
                                    </small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" @click="showDetails(group)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                                <td>
                                    <input type="number" min="1" class="form-control form-control-sm qty-input" v-model.number="group.print_quantity" @input="validateQty(group)">
                                    <div class="warning-text" v-if="group.print_quantity > group.available_qty">Over stock</div>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm label-size-select" v-model="group.sizeKey" @change="selectGroup(group)">
                                        <option v-for="size in labelSizes" :value="size.key">@{{ size.label }}</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" @click="selectGroup(group)">
                                        Customize
                                    </button>
                                    <button class="btn btn-sm btn-success mt-1" @click="printGroup(group)">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
new Vue({
    el: '#purchase_product_barcode_print',
    data: {
        purchase_product_order_id: {{ $purchase_id }},
        loading: true,
        groups: [],
        selectedGroup: null,
        detailsGroup: null,
        filters: { search: '', supplier: '', category: '', stockOnly: true },
        labelSizes: [
            { key: '1x1', label: '1 inch x 1 inch', width: 1, height: 1, unit: 'in' },
            { key: '1.5x1', label: '1.5 inch x 1 inch', width: 1.5, height: 1, unit: 'in' },
            { key: '2x1', label: '2 inch x 1 inch', width: 2, height: 1, unit: 'in' },
            { key: '2x1.5', label: '2 inch x 1.5 inch', width: 2, height: 1.5, unit: 'in' },
            { key: '2x2', label: '2 inch x 2 inch', width: 2, height: 2, unit: 'in' },
            { key: 'custom', label: 'Custom Size', width: 2, height: 1, unit: 'in' }
        ],
        customSize: { width: 2, height: 1, unit: 'in' },
        template: {
            barcode: { x: 14, y: 50, width: 164, height: 32 },
            lines: [
                { id: 'line_company', text: '@{{company_name}}', type: 'top', x: 6, y: 4, width: 180, font_size: 9, font_weight: 'bold', align: 'center', visible: true },
                { id: 'line_product', text: '@{{product_name}}', type: 'top', x: 6, y: 16, width: 180, font_size: 8, font_weight: 'bold', align: 'center', visible: true },
                { id: 'line_variant', text: '@{{variant_name}}', type: 'top', x: 6, y: 28, width: 180, font_size: 8, font_weight: 'normal', align: 'center', visible: true },
                { id: 'line_price_block', text: '@{{price_block}}', type: 'top', x: 6, y: 39, width: 180, font_size: 9, font_weight: 'bold', align: 'center', visible: true }
            ]
        },
        activeElement: null,
        drag: null,
        preparingPrint: false,
        printMode: 'label',
        previewZoom: 1.8,
        storageKey: 'purchase_barcode_label_template_v4'
    },
    mounted() {
        this.loadSavedTemplate();
        this.loadGroups();
        window.addEventListener('mousemove', this.onDrag);
        window.addEventListener('mouseup', this.stopDrag);
    },
    beforeDestroy() {
        window.removeEventListener('mousemove', this.onDrag);
        window.removeEventListener('mouseup', this.stopDrag);
    },
    computed: {
        filteredGroups() {
            const search = this.filters.search.toLowerCase();
            const supplier = this.filters.supplier.toLowerCase();
            const category = this.filters.category.toLowerCase();

            return this.groups.filter(group => {
                const haystack = [
                    group.product_name,
                    group.barcode,
                    group.sku,
                    group.variant_title
                ].join(' ').toLowerCase();
                const categoryHaystack = [group.category, group.brand].join(' ').toLowerCase();

                if (this.filters.stockOnly && group.available_qty < 1) return false;
                if (search && !haystack.includes(search)) return false;
                if (supplier && !(group.supplier || '').toLowerCase().includes(supplier)) return false;
                if (category && !categoryHaystack.includes(category)) return false;
                return true;
            });
        },
        selectedSize() {
            if (!this.selectedGroup) return this.labelSizes[2];
            return this.labelSizes.find(size => size.key === this.selectedGroup.sizeKey) || this.labelSizes[2];
        },
        labelDimensions() {
            if (this.selectedSize.key === 'custom') {
                return {
                    width: this.customSize.width + this.customSize.unit,
                    height: this.customSize.height + this.customSize.unit
                };
            }
            return {
                width: this.selectedSize.width + this.selectedSize.unit,
                height: this.selectedSize.height + this.selectedSize.unit
            };
        },
        labelStyle() {
            return {
                width: this.labelDimensions.width,
                height: this.labelDimensions.height
            };
        },
        previewLabelStyle() {
            return Object.assign({}, this.labelStyle, {
                transform: 'scale(' + this.previewZoom + ')',
                transformOrigin: 'top center',
                marginBottom: ((this.previewZoom - 1) * 140) + 'px'
            });
        },
        printAreaClass() {
            return this.printMode === 'label' ? 'print-mode-label' : 'print-mode-a4';
        },
        barcodeStyle() {
            return {
                left: this.template.barcode.x + 'px',
                top: this.template.barcode.y + 'px',
                width: this.template.barcode.width + 'px',
                height: this.template.barcode.height + 'px'
            };
        },
        visibleLines() {
            return this.template.lines.filter(line => line.visible !== false);
        },
        printCopies() {
            if (!this.preparingPrint) return [];
            const qty = parseInt((this.selectedGroup && this.selectedGroup.print_quantity) || 0, 10);
            return Array.from({ length: Math.max(qty, 0) }, (v, i) => i + 1);
        }
    },
    watch: {
        selectedGroup: {
            deep: true,
            handler() { this.renderBarcodes(); }
        },
        template: {
            deep: true,
            handler() {
                this.renderBarcodes();
                this.saveTemplate();
            }
        },
        customSize: {
            deep: true,
            handler() {
                this.renderBarcodes();
                this.saveTemplate();
            }
        },
        printMode() {
            this.saveTemplate();
        },
        previewZoom() {
            this.saveTemplate();
        }
    },
    methods: {
        loadGroups() {
            this.loading = true;
            axios.get(`{{ url('api/purchase-barcode-units') }}/${this.purchase_product_order_id}`)
                .then(response => {
                    console.log('Barcode groups loaded:', response.data.groups);
                    this.groups = (response.data.groups || []).map(group => {
                        group.print_quantity = group.print_quantity || group.available_qty || 1;
                        group.sizeKey = group.sizeKey || '2x1';
                        group.original_regular_price = Number(group.sales_price || 0);
                        group.original_discount_price = Number(group.discount_price || 0);
                        group.label_regular_price = group.label_regular_price != null
                            ? Number(group.label_regular_price || 0)
                            : group.original_regular_price;
                        group.label_discount_price = group.label_discount_price != null
                            ? Number(group.label_discount_price || 0)
                            : group.original_discount_price;
                        return group;
                    });
                    this.selectedGroup = this.groups.find(group => group.available_qty > 0) || this.groups[0] || null;
                    this.loading = false;
                    this.renderBarcodes();
                })
                .catch(error => {
                    this.loading = false;
                    const message = error.response && error.response.data && error.response.data.message
                        ? error.response.data.message
                        : 'Unable to load barcode groups.';
                    Swal.fire('Error', message, 'error');
                });
        },
        selectGroup(group) {
            this.selectedGroup = group;
            this.applyGroupSize();
        },
        printGroup(group) {
            this.selectGroup(group);
            this.$nextTick(() => this.printSelected());
        },
        applyGroupSize() {
            this.$nextTick(() => this.renderBarcodes());
        },
        validateQty(group) {
            if (!group.print_quantity || group.print_quantity < 1) group.print_quantity = 1;
        },
        activePrice(group) {
            const discount = this.discountPrice(group);
            return discount > 0 ? discount : this.regularPrice(group);
        },
        regularPrice(group) {
            return Number((group && group.label_regular_price != null ? group.label_regular_price : group.sales_price) || 0);
        },
        discountPrice(group) {
            return Number((group && group.label_discount_price != null ? group.label_discount_price : group.discount_price) || 0);
        },
        imeiText(group) {
            if (!group) return '';
            return [group.imei_1, group.imei_2].filter(Boolean).join(' / ');
        },
        resetSelectedPrice() {
            if (!this.selectedGroup) return;
            this.$set(this.selectedGroup, 'label_regular_price', Number(this.selectedGroup.original_regular_price || this.selectedGroup.sales_price || 0));
            this.$set(this.selectedGroup, 'label_discount_price', Number(this.selectedGroup.original_discount_price || this.selectedGroup.discount_price || 0));
            this.renderBarcodes();
        },
        addLine(type) {
            const y = type === 'top' ? 6 : 78;
            this.template.lines.push({
                id: 'line_' + Date.now(),
                text: type === 'top' ? '@{{product_name}}' : '@{{barcode}}',
                type: type,
                x: 6,
                y: y,
                width: 180,
                font_size: 9,
                font_weight: 'normal',
                align: 'center',
                visible: true
            });
        },
        addPriceLine() {
            this.template.lines.push({
                id: 'line_price_' + Date.now(),
                text: '@{{price_block}}',
                type: 'top',
                x: 6,
                y: 30,
                width: 180,
                font_size: 9,
                font_weight: 'bold',
                align: 'center',
                visible: true
            });
        },
        removeLine(id) {
            this.template.lines = this.template.lines.filter(line => line.id !== id);
        },
        defaultTemplate() {
            return {
                barcode: { x: 14, y: 50, width: 164, height: 32 },
                lines: [
                    { id: 'line_company', text: '@{{company_name}}', type: 'top', x: 6, y: 4, width: 180, font_size: 9, font_weight: 'bold', align: 'center', visible: true },
                    { id: 'line_product', text: '@{{product_name}}', type: 'top', x: 6, y: 16, width: 180, font_size: 8, font_weight: 'bold', align: 'center', visible: true },
                    { id: 'line_variant', text: '@{{variant_name}}', type: 'top', x: 6, y: 28, width: 180, font_size: 8, font_weight: 'normal', align: 'center', visible: true },
                    { id: 'line_price_block', text: '@{{price_block}}', type: 'top', x: 6, y: 39, width: 180, font_size: 9, font_weight: 'bold', align: 'center', visible: true }
                ]
            };
        },
        resetTemplate() {
            this.template = this.defaultTemplate();
            this.resetSelectedPrice();
            this.saveTemplate();
        },
        lineStyle(line) {
            return {
                left: line.x + 'px',
                top: line.y + 'px',
                width: (line.width || 180) + 'px',
                fontSize: line.font_size + 'px',
                fontWeight: line.font_weight,
                textAlign: line.align
            };
        },
        renderText(text) {
            if (!this.selectedGroup) return text;
            const group = this.selectedGroup;
            const price = this.formatPrice(this.activePrice(group));
            const regularPrice = this.formatPrice(this.regularPrice(group));
            const discountPrice = this.discountPrice(group) > 0 ? this.formatPrice(this.discountPrice(group)) : '';
            const replacements = {
                '@{{product_name}}': group.product_name || '',
                '@{{variant_name}}': group.variant_title || '',
                '@{{barcode}}': group.barcode || '',
                '@{{price}}': price,
                '@{{regular_price}}': regularPrice,
                '@{{discount_price}}': discountPrice,
                '@{{price_block}}': discountPrice ? discountPrice + ' ' + regularPrice : regularPrice,
                '@{{sku}}': group.sku || '',
                '@{{serial_no}}': group.serial_no || '',
                '@{{imei_1}}': group.imei_1 || '',
                '@{{imei_2}}': group.imei_2 || '',
                '@{{warranty_start}}': group.supplier_warranty_start_date || group.customer_warranty_start_date || '',
                '@{{warranty_end}}': group.supplier_warranty_end_date || group.customer_warranty_end_date || '',
                '@{{warranty_note}}': group.warranty_note || '',
                '@{{category}}': group.category || '',
                '@{{brand}}': group.brand || '',
                '@{{company_name}}': group.company_name || '',
                '@{{custom_text}}': ''
            };

            return Object.keys(replacements).reduce(function (value, key) {
                return value.split(key).join(replacements[key]);
            }, text || '');
        },
        escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
        renderPriceBlockHtml(group) {
            const regular = this.regularPrice(group);
            const discount = this.discountPrice(group);

            if (discount > 0 && discount < regular) {
                return '<span class="label-price-final">৳' + this.escapeHtml(this.formatPrice(discount)) + '</span><del>৳' + this.escapeHtml(this.formatPrice(regular)) + '</del>';
            }

            return '<span class="label-price-final">৳' + this.escapeHtml(this.formatPrice(regular)) + '</span>';
        },
        renderLineHtml(text) {
            if (!this.selectedGroup) return this.escapeHtml(text || '');

            const group = this.selectedGroup;
            const priceBlockToken = '@{{price_block}}';
            const safeReplacements = {
                '@{{product_name}}': this.escapeHtml(group.product_name || ''),
                '@{{variant_name}}': this.escapeHtml(group.variant_title || ''),
                '@{{barcode}}': this.escapeHtml(group.barcode || ''),
                '@{{price}}': this.escapeHtml('৳' + this.formatPrice(this.activePrice(group))),
                '@{{regular_price}}': this.escapeHtml('৳' + this.formatPrice(this.regularPrice(group))),
                '@{{discount_price}}': this.discountPrice(group) > 0 ? this.escapeHtml('৳' + this.formatPrice(this.discountPrice(group))) : '',
                '@{{sku}}': this.escapeHtml(group.sku || ''),
                '@{{serial_no}}': this.escapeHtml(group.serial_no || ''),
                '@{{imei_1}}': this.escapeHtml(group.imei_1 || ''),
                '@{{imei_2}}': this.escapeHtml(group.imei_2 || ''),
                '@{{warranty_start}}': this.escapeHtml(group.supplier_warranty_start_date || group.customer_warranty_start_date || ''),
                '@{{warranty_end}}': this.escapeHtml(group.supplier_warranty_end_date || group.customer_warranty_end_date || ''),
                '@{{warranty_note}}': this.escapeHtml(group.warranty_note || ''),
                '@{{category}}': this.escapeHtml(group.category || ''),
                '@{{brand}}': this.escapeHtml(group.brand || ''),
                '@{{company_name}}': this.escapeHtml(group.company_name || ''),
                '@{{custom_text}}': ''
            };

            let html = this.escapeHtml(text || '');
            Object.keys(safeReplacements).forEach(function (key) {
                html = html.split(key).join(safeReplacements[key]);
            });

            return html.split(priceBlockToken).join(this.renderPriceBlockHtml(group));
        },
        loadSavedTemplate() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;

                const saved = JSON.parse(raw);
                if (!saved || saved.version !== 4) return;

                if (saved.template && saved.template.barcode && Array.isArray(saved.template.lines)) {
                    this.template = saved.template;
                }
                if (saved.customSize) this.customSize = saved.customSize;
                if (saved.printMode) this.printMode = saved.printMode;
                if (saved.previewZoom) this.previewZoom = saved.previewZoom;
            } catch (error) {
                localStorage.removeItem(this.storageKey);
            }
        },
        saveTemplate() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    version: 4,
                    template: this.template,
                    customSize: this.customSize,
                    printMode: this.printMode,
                    previewZoom: this.previewZoom
                }));
            } catch (error) {}
        },
        renderBarcodes() {
            if (!this.selectedGroup) return;
            this.$nextTick(() => {
                this.drawBarcode('preview-barcode');
                this.printCopies.forEach(n => this.drawBarcode('print-barcode-' + n));
            });
        },
        drawBarcode(id) {
            const svg = document.getElementById(id);
            if (!svg || !this.selectedGroup) return;
            try {
                JsBarcode(svg, this.selectedGroup.barcode, {
                    format: 'CODE128',
                    width: 1.4,
                    height: 34,
                    fontSize: 10,
                    displayValue: true,
                    margin: 0
                });
            } catch (error) {
                svg.innerHTML = '';
            }
        },
        startDrag(event, id, target) {
            this.activeElement = id;
            this.drag = {
                target: target,
                startX: event.clientX,
                startY: event.clientY,
                originalX: target.x,
                originalY: target.y
            };
        },
        onDrag(event) {
            if (!this.drag) return;
            const label = this.$refs.previewLabel;
            const maxX = label ? label.offsetWidth - 12 : 500;
            const maxY = label ? label.offsetHeight - 8 : 300;
            const nextX = this.drag.originalX + ((event.clientX - this.drag.startX) / this.previewZoom);
            const nextY = this.drag.originalY + ((event.clientY - this.drag.startY) / this.previewZoom);
            this.drag.target.x = Math.max(0, Math.min(nextX, maxX));
            this.drag.target.y = Math.max(0, Math.min(nextY, maxY));
        },
        stopDrag() {
            this.drag = null;
        },
        showDetails(group) {
            this.detailsGroup = group;
            $('#barcodeDetailsModal').modal('show');
        },
        printSelected() {
            if (!this.selectedGroup) {
                Swal.fire('Select barcode', 'Please select a barcode first.', 'warning');
                return;
            }
            if (!this.selectedGroup.print_quantity || this.selectedGroup.print_quantity < 1) {
                Swal.fire('Invalid quantity', 'Print quantity must be at least 1.', 'warning');
                return;
            }
            if (this.selectedGroup.print_quantity > this.selectedGroup.available_qty) {
                Swal.fire({
                    title: 'Print quantity is greater than available stock',
                    text: 'Do you still want to print?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Print anyway'
                }).then(result => {
                    if (result.isConfirmed) this.executePrint();
                });
                return;
            }
            this.executePrint();
        },
        executePrint() {
            this.preparingPrint = true;
            this.applyPrintPageSize();
            this.renderBarcodes();
            this.$nextTick(() => {
                this.renderBarcodes();
                setTimeout(() => {
                    window.print();
                    setTimeout(() => {
                        this.preparingPrint = false;
                        this.removePrintPageSize();
                    }, 500);
                }, 200);
            });
        },
        applyPrintPageSize() {
            this.removePrintPageSize();

            const style = document.createElement('style');
            style.id = 'dynamic-barcode-print-page-size';

            if (this.printMode === 'a4') {
                style.textContent = '@media print { @page { size: A4; margin: 0; } .print-area.print-mode-a4 { width: 210mm; align-content: flex-start; } }';
            } else {
                style.textContent = '@media print { @page { size: ' + this.labelDimensions.width + ' ' + this.labelDimensions.height + '; margin: 0; } .print-area.print-mode-label { width: ' + this.labelDimensions.width + '; } .print-area.print-mode-label .print-label { width: ' + this.labelDimensions.width + ' !important; height: ' + this.labelDimensions.height + ' !important; } }';
            }

            document.head.appendChild(style);
        },
        removePrintPageSize() {
            const existingStyle = document.getElementById('dynamic-barcode-print-page-size');
            if (existingStyle) existingStyle.remove();
        },
        formatPrice(price) {
            return parseFloat(price || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },
        imageFallback(event) {
            event.target.src = '{{ asset('assets/images/default-product.png') }}';
        }
    }
});
</script>
@endsection
