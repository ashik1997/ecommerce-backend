(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function totalPurchasePrice(cart) {
        return (cart || []).reduce(function (sum, item) {
            const price = Number(item.purchase_price || 0);
            const qty = Number(item.qty || 0);

            return sum + (price * qty);
        }, 0);
    }

    function buildTotalsPayload(totalsStore, paymentStore) {
        const paymentTotal = paymentStore.paymentTotal;
        const grandTotal = Number(totalsStore.grandTotal || 0);

        return Object.assign({}, totalsStore.totalsPayload, {
            paid: paymentTotal,
            due: Math.max(0, grandTotal - paymentTotal),
            total_purchase_price: totalPurchasePrice(global.PosV3.useCartStore().items),
        });
    }

    function buildPaymentsPayload(paymentStore) {
        return paymentStore.methods
            .filter(function (method) {
                return method.selected && Number(method.amount || 0) > 0;
            })
            .map(function (method) {
                return {
                    method: method.id,
                    amount: method.amount,
                    payment_type_id: method.payment_type_id || null,
                };
            });
    }

    function buildDeliveryInfo(orderStore, totalsStore) {
        const deliveryInfo = global.PosV3.cloneDeep(orderStore.deliveryInfo || {});

        deliveryInfo.delivery_charge_type = totalsStore.deliveryChargeType || deliveryInfo.delivery_charge_type || '';

        if (orderStore.note) {
            deliveryInfo.order_note = orderStore.note;
        }

        return deliveryInfo;
    }

    global.PosV3.orderPayloadBuilder = {
        buildSubmitPayload: function buildSubmitPayload(options) {
            const opts = options || {};
            const orderStore = global.PosV3.useOrderStore();
            const cartStore = global.PosV3.useCartStore();
            const customerStore = global.PosV3.useCustomerStore();
            const totalsStore = global.PosV3.useTotalsStore();
            const paymentStore = global.PosV3.usePaymentStore();
            const isEdit = !!opts.isEdit;

            return {
                action: isEdit ? 'update' : 'create',
                order_id: isEdit ? orderStore.orderId : null,
                quotation_id: orderStore.quotationId || null,
                cart: global.PosV3.cloneDeep(cartStore.items),
                totals: buildTotalsPayload(totalsStore, paymentStore),
                customer: global.PosV3.cloneDeep(customerStore.selectedCustomer),
                payments: buildPaymentsPayload(paymentStore),
                use_advance: paymentStore.useAdvance,
                advance_amount: paymentStore.useAdvance ? paymentStore.advanceAmount : 0,
                order_note: orderStore.note || '',
                order_source: orderStore.quotationId ? 'quotation' : 'pos',
                delivery_info: buildDeliveryInfo(orderStore, totalsStore),
                order_status: orderStore.orderStatus,
                salesman_id: orderStore.salesmanId || null,
                affiliate_code: orderStore.affiliateCode || null,
                sms_send_to_customer: orderStore.smsSendToCustomer,
            };
        },
        buildPreviewPayload: function buildPreviewPayload() {
            const orderStore = global.PosV3.useOrderStore();
            const cartStore = global.PosV3.useCartStore();
            const customerStore = global.PosV3.useCustomerStore();
            const totalsStore = global.PosV3.useTotalsStore();
            const paymentStore = global.PosV3.usePaymentStore();

            return {
                cart: global.PosV3.cloneDeep(cartStore.items),
                totals: buildTotalsPayload(totalsStore, paymentStore),
                customer: global.PosV3.cloneDeep(customerStore.selectedCustomer),
                order_note: orderStore.note || '',
            };
        },
    };
})(window);
