(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.orderStateSerializer = {
        serialize: function serialize() {
            const cartStore = global.PosV3.useCartStore();
            const totalsStore = global.PosV3.useTotalsStore();
            const customerStore = global.PosV3.useCustomerStore();
            const orderStore = global.PosV3.useOrderStore();

            return {
                timestamp: new Date().toISOString(),
                cart: global.PosV3.cloneDeep(cartStore.items),
                totals: global.PosV3.cloneDeep(totalsStore.totalsPayload),
                customer: global.PosV3.cloneDeep(customerStore.selectedCustomer),
                coupon: global.PosV3.cloneDeep(totalsStore.coupon),
                extraChargeLines: global.PosV3.normalizeExtraChargeLines(totalsStore.extraChargeLines),
                useExtraChargeLines: totalsStore.useExtraChargeLines,
                extra_charge: totalsStore.extraCharge,
                delivery_charge: totalsStore.deliveryCharge,
                delivery_charge_type: totalsStore.deliveryChargeType,
                round_off: totalsStore.roundOff,
                selectedWarehouseId: orderStore.selectedWarehouseId,
                selectedProductPriceType: orderStore.selectedProductPriceType,
                order_note: orderStore.note || '',
                order_status: orderStore.orderStatus,
                salesman_id: orderStore.salesmanId || null,
                affiliate_code: orderStore.affiliateCode || '',
                sms_send_to_customer: orderStore.smsSendToCustomer,
            };
        },
        apply: function apply(data) {
            if (!data) {
                return;
            }

            const cartStore = global.PosV3.useCartStore();
            const totalsStore = global.PosV3.useTotalsStore();
            const customerStore = global.PosV3.useCustomerStore();
            const orderStore = global.PosV3.useOrderStore();
            const paymentStore = global.PosV3.usePaymentStore();

            if (data.selectedWarehouseId !== undefined && data.selectedWarehouseId !== null) {
                orderStore.selectedWarehouseId = data.selectedWarehouseId;
            }

            if (data.selectedProductPriceType) {
                orderStore.selectedProductPriceType = data.selectedProductPriceType;
            }

            cartStore.setItems(data.cart || [], { skipRecalc: true });

            if (data.totals) {
                totalsStore.applySnapshot(data.totals);
            }

            if (data.coupon && (!data.totals || !data.totals.coupon)) {
                totalsStore.coupon = Object.assign({}, totalsStore.coupon, global.PosV3.cloneDeep(data.coupon));
            }

            if (Array.isArray(data.extraChargeLines) && (!data.totals || !data.totals.extra_charge_lines)) {
                totalsStore.extraChargeLines = global.PosV3.normalizeExtraChargeLines(data.extraChargeLines);
                totalsStore.useExtraChargeLines = !!data.useExtraChargeLines || totalsStore.extraChargeLines.length > 0;
            }

            if (!data.totals && data.delivery_charge_type) {
                totalsStore.deliveryChargeType = data.delivery_charge_type;
            }

            if (data.customer) {
                customerStore.selectedCustomer = global.PosV3.cloneDeep(data.customer);
            }

            orderStore.note = data.order_note || data.note || '';
            orderStore.orderStatus = data.order_status || orderStore.orderStatus;
            orderStore.salesmanId = data.salesman_id || null;
            orderStore.affiliateCode = data.affiliate_code || '';
            orderStore.smsSendToCustomer = data.sms_send_to_customer !== undefined
                ? !!data.sms_send_to_customer
                : orderStore.smsSendToCustomer;

            paymentStore.resetAfterOrder();
            totalsStore.recalc();
        },
        normalizeCustomer: function normalizeCustomer(customer) {
            if (!customer) {
                return customer;
            }

            const normalized = global.PosV3.cloneDeep(customer);

            if (normalized.available_advance !== undefined && normalized.advance === undefined) {
                normalized.advance = Number(normalized.available_advance) || 0;
            }

            return normalized;
        },
        applyEditPayload: function applyEditPayload(data) {
            if (!data) {
                return;
            }

            const orderStore = global.PosV3.useOrderStore();
            const customerStore = global.PosV3.useCustomerStore();
            const paymentStore = global.PosV3.usePaymentStore();
            const totalsStore = global.PosV3.useTotalsStore();

            orderStore.mode = 'edit';
            orderStore.orderId = data.order_id || null;
            orderStore.slug = data.order_slug || null;
            orderStore.quotationId = null;
            orderStore.quotationCode = '';

            this.apply({
                cart: data.cart,
                totals: data.totals,
                customer: this.normalizeCustomer(data.customer),
                order_status: data.order_status,
                salesman_id: data.salesman_id || null,
                affiliate_code: data.affiliate_code || '',
                selectedWarehouseId: data.warehouse_id,
            });

            if (data.delivery_info) {
                orderStore.deliveryInfo = Object.assign(
                    {},
                    orderStore.deliveryInfo,
                    global.PosV3.cloneDeep(data.delivery_info)
                );
            }

            if (data.delivery_info && data.delivery_info.delivery_charge_type) {
                totalsStore.deliveryChargeType = data.delivery_info.delivery_charge_type;
            }

            paymentStore.populateFromLines(data.payments || []);
            paymentStore.useAdvance = !!data.use_advance;
            paymentStore.advanceAmount = Number(data.advance_amount || 0);

            if (data.customer && data.customer.address && customerStore.selectedCustomer) {
                customerStore.selectedCustomer.address = data.customer.address;
            }

            totalsStore.recalc();
            global.PosV3.useDraftStore().clearDraft();
        },
        applyQuotationPayload: function applyQuotationPayload(data) {
            if (!data) {
                return;
            }

            const orderStore = global.PosV3.useOrderStore();
            const paymentStore = global.PosV3.usePaymentStore();
            const totalsStore = global.PosV3.useTotalsStore();

            orderStore.mode = 'create';
            orderStore.orderId = null;
            orderStore.slug = null;
            orderStore.quotationId = data.quotation_id || null;
            orderStore.quotationCode = data.quotation_code || '';

            this.apply({
                cart: data.cart,
                totals: data.totals,
                customer: this.normalizeCustomer(data.customer),
                order_status: data.order_status,
                salesman_id: data.salesman_id || null,
                affiliate_code: data.affiliate_code || '',
                order_note: data.delivery_info && data.delivery_info.order_note,
                selectedWarehouseId: data.warehouse_id,
            });

            if (data.delivery_info) {
                orderStore.deliveryInfo = Object.assign(
                    {},
                    orderStore.deliveryInfo,
                    global.PosV3.cloneDeep(data.delivery_info)
                );
            }

            if (data.delivery_info && data.delivery_info.delivery_charge_type) {
                totalsStore.deliveryChargeType = data.delivery_info.delivery_charge_type;
            }

            paymentStore.resetAfterOrder();
            totalsStore.recalc();
            global.PosV3.useDraftStore().clearDraft();
        },
        buildHoldPayload: function buildHoldPayload() {
            const cartStore = global.PosV3.useCartStore();
            const totalsStore = global.PosV3.useTotalsStore();
            const customerStore = global.PosV3.useCustomerStore();
            const orderStore = global.PosV3.useOrderStore();

            totalsStore.recalc();

            return {
                cart: global.PosV3.cloneDeep(cartStore.items),
                totals: global.PosV3.cloneDeep(totalsStore.totalsPayload),
                customer: global.PosV3.cloneDeep(customerStore.selectedCustomer),
                order_note: orderStore.note || '',
            };
        },
    };
})(window);
