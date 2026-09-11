@extends('backend.master')

@section('header_css')
    <link rel="stylesheet" href="/assets/plugins/select2/select2.min.css">
    <link rel="stylesheet" href="{{ versioned_asset('assets/css/pos_v3/pos_v3.css') }}">
@endsection

@section('header_js')
    @php($posV3AssetVer = time() . '_' . env('APP_VERSION', time()))
    <script>
        window.POS_V3_CONFIG = {
            configVersion: 1,
            routes: {
                search: "{{ route('pos.desktop.search') }}",
                products: "{{ route('pos.desktop.products') }}",
                categories: "{{ route('pos.desktop.categories') }}",
                nestedCategories: "{{ route('pos.categories') }}",
                barcode: "{{ route('pos.desktop.barcode') }}",
                addToCart: "{{ route('pos.desktop.add-to-cart') }}",
                hold: "{{ route('pos.desktop.hold') }}",
                getHold: "{{ route('pos.desktop.get-hold', ['id' => '__ID__']) }}",
                customerSearch: "{{ route('pos.desktop.customer.search') }}",
                customerHistory: "{{ route('pos.desktop.customer.history', ['customer' => '__ID__']) }}",
                customerCreate: "{{ route('pos.desktop.customer.create') }}",
                customerDelete: "{{ route('pos.desktop.customer.delete') }}",
                districts: "/api/get/all/districts",
                applyCoupon: "{{ route('pos.desktop.apply-coupon') }}",
                calculateTotals: "{{ route('pos.desktop.calculate-totals') }}",
                createOrder: "{{ route('pos.desktop.create-order') }}",
                editOrder: "{{ route('pos.desktop.edit-order') }}",
                preview: "{{ route('pos.desktop.preview') }}",
                print: "{{ route('pos.desktop.print', ['slug' => '__SLUG__']) }}",
                holds: "{{ route('pos.desktop.holds') }}",
                paymentMethods: "{{ route('pos.get-payment-methods') }}",
                invoiceUrlBase: "{{ route('order.invoice', ['slug' => '__SLUG__']) }}",
                targetStats: "{{ route('pos.desktop.target-stats') }}",
                productsByBarcode: "{{ route('pos.desktop.products-by-barcode') }}",
                customerSource: "{{ route('pos.desktop.customer-source') }}",
                deliveryMethods: "{{ route('pos.desktop.delivery-methods') }}",
                outlets: "{{ route('pos.desktop.outlets') }}",
                courierMethods: "{{ route('pos.desktop.courier-methods') }}",
                quotationPosData: "{{ route('QuotationPosData', ['id' => '__ID__']) }}",
                extraChargeTypes: "{{ route('pos.desktop.extra-charge-types') }}",
                extraChargeTypeCreate: "{{ route('pos.desktop.extra-charge-types.store') }}",
            },
            warehouses: @json($warehouses ?? []),
            sales_users: @json($commissionSalesUsers ?? []),
            affiliates: @json($commissionAffiliates ?? []),
            image_url: "{{ env('IMAGE_URL') }}",
            features: {
                extraChargeLines: true,
                delivery: true,
                advance: true,
                hold: true,
                quotation: true,
            },
            defaults: {
                customerId: 1,
                warehouseId: null,
                priceType: 'product_price',
            },
            edit: null,
            quotation: null,
        };

        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('pos-v3-active', 'lg_hide_menu');
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/vue@3.5.13/dist/vue.global.prod.js" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/vendor/piniaVue3Shim.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/pinia@2.3.1/dist/pinia.iife.prod.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="/assets/plugins/select2/select2.min.js" defer></script>

    <script src="{{ versioned_asset('assets/js/pos/v3/utils/formatMoney.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/cloneDeep.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/debounce.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/variantHelpers.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/cartPricing.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/cartItemBuilder.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/normalizeExtraChargeLines.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/deliveryCharge.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/alerts.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/toast.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/orderPayloadBuilder.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/draftStorage.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/urlContext.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/orderStateSerializer.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/keyboardShortcuts.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/posSettingsStorage.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/customerFormHelpers.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/configStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/settingsStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/orderStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/uiStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/searchStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/totalsStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/cartStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/customerStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/paymentStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/draftStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/holdStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/targetStatsStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/categoryStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/stores/deliveryOptionsStore.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/utils/api.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/cart/PosV3CartRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/cart/PosV3CartTable.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/search/PosV3ProductItem.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/search/PosV3ProductResults.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/search/PosV3ProductSearch.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/search/PosV3BarcodeSearch.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3DiscountRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3CouponRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3ExtraChargeManage.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3ExtraChargeRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3DeliveryRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3RoundOffRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3GrandTotal.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/totals/PosV3TotalsCard.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerBar.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerList.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerForm.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerView.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerHistoryModal.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/customer/PosV3CustomerModal.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/payment/PosV3AdvanceRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/payment/PosV3PaymentRow.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/payment/PosV3PaidDueSummary.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/payment/PosV3CashExchange.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/payment/PosV3PaymentSection.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/hold/PosV3HoldActions.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/hold/PosV3HoldListModal.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/header/PosV3TargetStats.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/header/PosV3Clock.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/categories/PosV3CategorySidebar.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/order/PosV3DeliveryInfoPanel.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/order/PosV3OrderActions.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/settings/PosV3SettingsModal.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/layout/PosV3Header.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/layout/PosV3LeftPanel.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/layout/PosV3RightPanel.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/components/layout/PosV3App.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/bootstrap.js') }}" defer></script>
    <script src="{{ versioned_asset('assets/js/pos/v3/app.js') }}" defer></script>
@endsection

@section('page_title')
    POS Desktop v3
@endsection

@section('page_heading')
    POS Desktop v3
@endsection

@section('content')
    <div id="pos-v3-app" class="pos-v3-page"></div>
@endsection
