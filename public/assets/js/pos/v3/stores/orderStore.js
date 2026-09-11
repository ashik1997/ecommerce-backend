(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function defaultDeliveryInfo() {
        return {
            delivery_method: '',
            expected_delivery_date: new Date(Date.now() + (2 * 24 * 60 * 60 * 1000)).toISOString().split('T')[0],
            order_source: 'pos',
            order_note: '',
            outlet_id: '',
            courier_method: null,
            courier_method_title: '',
            delivery_charge_type: '',
        };
    }

    function firstWarehouseId(warehouses) {
        if (!Array.isArray(warehouses) || !warehouses.length) {
            return null;
        }

        return Number(warehouses[0].id) || null;
    }

    global.PosV3.useOrderStore = Pinia.defineStore('posV3Order', {
        state: function () {
            return {
                mode: 'create',
                slug: null,
                orderId: null,
                quotationId: null,
                quotationCode: '',
                holdId: null,
                note: '',
                orderStatus: 'delivered',
                salesmanId: null,
                affiliateCode: '',
                smsSendToCustomer: true,
                deliveryInfo: defaultDeliveryInfo(),
                selectedWarehouseId: null,
                selectedProductPriceType: 'product_price',
            };
        },
        getters: {
            modeLabel: function (state) {
                if (state.mode === 'edit' && state.orderId) {
                    return 'edit #' + state.orderId;
                }

                if (state.quotationId) {
                    return state.quotationCode
                        ? 'quotation ' + state.quotationCode
                        : 'quotation #' + state.quotationId;
                }

                return state.mode || 'create';
            },
            isEditMode: function (state) {
                return state.mode === 'edit' && !!state.orderId;
            },
            isQuotationMode: function (state) {
                return !!state.quotationId;
            },
        },
        actions: {
            initFromConfig: function () {
                const configStore = global.PosV3.useConfigStore();
                const defaults = configStore.defaults || {};

                this.selectedWarehouseId = defaults.warehouseId || firstWarehouseId(configStore.warehouses);
                this.selectedProductPriceType = defaults.priceType || 'product_price';

                if (configStore.edit) {
                    this.mode = 'edit';
                    this.orderId = configStore.edit.id || null;
                    this.slug = configStore.edit.slug || null;
                    return;
                }

                if (configStore.quotation) {
                    this.mode = 'quotation';
                    this.quotationId = configStore.quotation.id || null;
                    this.quotationCode = configStore.quotation.code || '';
                }
            },
            initFromUrlContext: function () {
                const configStore = global.PosV3.useConfigStore();
                const urlContext = global.PosV3.urlContext.read();
                let orderId = urlContext.orderId;
                let quotationId = urlContext.quotationId;

                if (!orderId && configStore.edit && configStore.edit.id) {
                    orderId = Number(configStore.edit.id) || null;
                }

                if (!quotationId && configStore.quotation && configStore.quotation.id) {
                    quotationId = Number(configStore.quotation.id) || null;
                }

                if (orderId && configStore.routes.editOrder) {
                    return this.loadOrderForEdit(orderId);
                }

                if (quotationId && configStore.routes.quotationPosData) {
                    return this.loadQuotationForOrder(quotationId);
                }

                return Promise.resolve(false);
            },
            loadOrderForEdit: function (orderId) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.editOrder;

                if (!route || !orderId) {
                    return Promise.resolve(false);
                }

                uiStore.setLoading('orderLoad', true);

                return global.PosV3.api.post(route, {
                    action: 'load',
                    order_id: orderId,
                }).then(function (response) {
                    if (!(response.data && response.data.success && response.data.data)) {
                        global.PosV3.alerts.show(
                            (response.data && response.data.message) || 'Failed to load order for edit',
                            'error'
                        );
                        return false;
                    }

                    global.PosV3.orderStateSerializer.applyEditPayload(response.data.data);
                    return true;
                }).catch(function (error) {
                    const message = error.response && error.response.data && error.response.data.message
                        ? error.response.data.message
                        : (error.message || 'Unknown error');

                    global.PosV3.alerts.show('Failed to load order for edit', 'error', message);
                    return false;
                }).finally(function () {
                    uiStore.setLoading('orderLoad', false);
                });
            },
            loadQuotationForOrder: function (quotationId) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const routeTemplate = configStore.routes.quotationPosData;

                if (!routeTemplate || !quotationId) {
                    return Promise.resolve(false);
                }

                if (!configStore.features.quotation) {
                    return Promise.resolve(false);
                }

                const url = routeTemplate.replace('__ID__', String(quotationId));

                uiStore.setLoading('orderLoad', true);

                return global.PosV3.api.get(url)
                    .then(function (response) {
                        if (!(response.data && response.data.success && response.data.data)) {
                            global.PosV3.alerts.show(
                                (response.data && response.data.message) || 'Failed to load quotation',
                                'error'
                            );
                            return false;
                        }

                        const data = response.data.data;
                        global.PosV3.orderStateSerializer.applyQuotationPayload(data);
                        global.PosV3.alerts.show(
                            'Quotation ' + (data.quotation_code || quotationId) + ' loaded. Review and submit to create order.',
                            'success'
                        );
                        return true;
                    })
                    .catch(function (error) {
                        const message = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : (error.message || 'Unknown error');

                        global.PosV3.alerts.show('Failed to load quotation', 'error', message);
                        return false;
                    })
                    .finally(function () {
                        uiStore.setLoading('orderLoad', false);
                    });
            },
            setWarehouse: function (warehouseId) {
                const configStore = global.PosV3.useConfigStore();
                this.selectedWarehouseId = warehouseId || firstWarehouseId(configStore.warehouses);
                global.PosV3.useCartStore().clear();
                global.PosV3.useTotalsStore().reset();

                const query = (global.PosV3.useSearchStore().query || '').trim();
                if (query) {
                    global.PosV3.useSearchStore().fetchProducts();
                }
            },
            setPriceType: function (priceType) {
                this.selectedProductPriceType = priceType || 'product_price';
                global.PosV3.useCartStore().applyPriceTypeToAll();
                global.PosV3.useTotalsStore().recalc();
            },
            setNote: function (value) {
                this.note = value || '';
            },
            setOrderStatus: function (value) {
                this.orderStatus = value || 'delivered';
            },
            setSmsSendToCustomer: function (value) {
                this.smsSendToCustomer = !!value;
            },
            resetDeliveryInfo: function () {
                this.deliveryInfo = defaultDeliveryInfo();
            },
            setDeliveryInfoField: function (key, value) {
                if (!this.deliveryInfo || !key) {
                    return;
                }

                this.deliveryInfo[key] = value;
            },
            setCourierMethod: function (courier) {
                if (!courier) {
                    this.deliveryInfo.courier_method = null;
                    this.deliveryInfo.courier_method_title = '';
                    return;
                }

                this.deliveryInfo.courier_method = courier.id;
                this.deliveryInfo.courier_method_title = courier.title || '';
            },
            previewOrder: function () {
                const configStore = global.PosV3.useConfigStore();
                const cartStore = global.PosV3.useCartStore();
                const route = configStore.routes.preview;

                if (!route) {
                    return Promise.resolve();
                }

                if (!cartStore.items.length) {
                    global.PosV3.alerts.show('Cart is empty', 'warning');
                    return Promise.resolve();
                }

                if (this.slug) {
                    return global.PosV3.api.post(route, { order_slug: this.slug })
                        .then(function (response) {
                            global.PosV3.useOrderStore().openPreviewWindow(response);
                        })
                        .catch(function () {
                            global.PosV3.alerts.show('Preview failed', 'error');
                        });
                }

                global.PosV3.useTotalsStore().recalc();

                return global.PosV3.api.post(route, global.PosV3.orderPayloadBuilder.buildPreviewPayload())
                    .then(function (response) {
                        global.PosV3.useOrderStore().openPreviewWindow(response);
                    })
                    .catch(function () {
                        global.PosV3.alerts.show('Preview failed', 'error');
                    });
            },
            openPreviewWindow: function (response) {
                const html = response.data && response.data.data ? response.data.data.html : null;

                if (!html) {
                    global.PosV3.alerts.show('Preview is empty', 'warning');
                    return;
                }

                const previewWindow = global.open('', '_blank', 'width=900,height=700');

                if (!previewWindow) {
                    global.PosV3.alerts.show('Popup blocked. Allow popups to preview.', 'warning');
                    return;
                }

                previewWindow.document.write(html);
            },
            submitOrder: function () {
                const cartStore = global.PosV3.useCartStore();
                const customerStore = global.PosV3.useCustomerStore();
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const totalsStore = global.PosV3.useTotalsStore();
                const isEdit = this.isEditMode;
                const route = isEdit ? configStore.routes.editOrder : configStore.routes.createOrder;

                if (!route) {
                    return Promise.resolve();
                }

                if (!cartStore.items.length) {
                    global.PosV3.alerts.show('Cart is empty', 'warning');
                    return Promise.resolve();
                }

                if (!customerStore.selectedCustomer) {
                    global.PosV3.alerts.show('Select or create a customer before submitting.', 'warning');
                    return Promise.resolve();
                }

                totalsStore.recalc();
                uiStore.setLoading('submit', true);

                const payload = global.PosV3.orderPayloadBuilder.buildSubmitPayload({ isEdit: isEdit });

                return global.PosV3.api.post(route, payload)
                    .then(function (response) {
                        if (!response.data || !response.data.success) {
                            global.PosV3.alerts.show(
                                (response.data && response.data.message) || (isEdit ? 'Order update failed' : 'Order creation failed'),
                                'error'
                            );
                            return;
                        }

                        const data = response.data.data || {};
                        const wasEdit = isEdit;
                        const hadQuotation = !!this.quotationId;

                        if (data.order_id) {
                            this.orderId = data.order_id;
                        }

                        if (data.order_slug) {
                            this.slug = data.order_slug;
                        }

                        if (data.print_url) {
                            global.open(data.print_url, '_blank');
                        }

                        this.clearAfterSubmit();

                        if (wasEdit) {
                            this.mode = 'create';
                            this.orderId = null;
                            this.slug = null;
                            global.PosV3.urlContext.clearOrderParams();
                        }

                        if (hadQuotation) {
                            this.quotationId = null;
                            this.quotationCode = '';
                            global.PosV3.urlContext.clearQuotationParams();
                        }

                        global.PosV3.alerts.show(
                            response.data.message || (wasEdit ? 'Order updated successfully.' : 'Order created successfully.'),
                            'success'
                        );
                    }.bind(this))
                    .catch(function (error) {
                        const message = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : (error.message || 'Unknown error');

                        global.PosV3.alerts.show(isEdit ? 'Order update failed' : 'Order creation failed', 'error', message);
                    })
                    .finally(function () {
                        uiStore.setLoading('submit', false);
                    });
            },
            clearAfterSubmit: function () {
                global.PosV3.useCartStore().clear();
                global.PosV3.useTotalsStore().reset();
                global.PosV3.usePaymentStore().resetAfterOrder();
                global.PosV3.useCustomerStore().clearSelection();
                global.PosV3.useDraftStore().clearDraft();
                this.note = '';
                this.holdId = null;
                this.resetDeliveryInfo();
            },
            clearSession: function () {
                this.clearAfterSubmit();
                this.mode = 'create';
                this.orderId = null;
                this.slug = null;
                this.quotationId = null;
                this.quotationCode = '';
                global.PosV3.urlContext.clearAll();
            },
            cancelOrder: function () {
                const message = this.isEditMode || this.isQuotationMode
                    ? 'Clear the loaded order and reset the POS?'
                    : 'Clear the cart and reset this order?';

                return global.PosV3.alerts.confirm(
                    'Cancel order?',
                    message
                ).then(function (confirmed) {
                    if (!confirmed) {
                        return false;
                    }

                    global.PosV3.useOrderStore().clearSession();
                    return true;
                });
            },
            reset: function () {
                this.mode = 'create';
                this.slug = null;
                this.orderId = null;
                this.quotationId = null;
                this.quotationCode = '';
                this.holdId = null;
                this.note = '';
                this.orderStatus = 'delivered';
                this.smsSendToCustomer = true;
                this.resetDeliveryInfo();
                this.initFromConfig();
            },
        },
    });
})(window);
