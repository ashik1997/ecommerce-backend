(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    global.PosV3.urlContext = {
        read: function read() {
            const search = new URLSearchParams(global.location.search || '');

            return {
                orderId: Number(search.get('order_id') || search.get('edit_order_id') || 0) || null,
                quotationId: Number(search.get('quotation_id') || 0) || null,
            };
        },
        clearOrderParams: function clearOrderParams() {
            const url = new URL(global.location.href);

            url.searchParams.delete('order_id');
            url.searchParams.delete('edit_order_id');
            global.history.replaceState({}, '', url.toString());
        },
        clearQuotationParams: function clearQuotationParams() {
            const url = new URL(global.location.href);

            url.searchParams.delete('quotation_id');
            global.history.replaceState({}, '', url.toString());
        },
        clearAll: function clearAll() {
            this.clearOrderParams();
            this.clearQuotationParams();
        },
    };
})(window);
