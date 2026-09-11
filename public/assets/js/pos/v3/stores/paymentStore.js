(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function defaultPaymentMethods() {
        return [
            {
                id: 'cash',
                payment_type_id: null,
                title: 'Cash',
                account_id: null,
                account_name: null,
                selected: true,
                amount: 0,
            },
        ];
    }

    function sortPaymentMethods(methods) {
        return methods.slice().sort(function (a, b) {
            const aIsCash = /^cash$/i.test(String(a.title || '').trim());
            const bIsCash = /^cash$/i.test(String(b.title || '').trim());

            if (aIsCash && !bIsCash) {
                return -1;
            }

            if (!aIsCash && bIsCash) {
                return 1;
            }

            if (aIsCash && bIsCash) {
                return 0;
            }

            return String(a.title || '').localeCompare(String(b.title || ''), undefined, { sensitivity: 'base' });
        });
    }

    global.PosV3.usePaymentStore = Pinia.defineStore('posV3Payment', {
        state: function () {
            return {
                methods: defaultPaymentMethods(),
                useAdvance: false,
                advanceAmount: 0,
                cashReceived: 0,
            };
        },
        getters: {
            paymentTotal: function (state) {
                const base = state.methods.reduce(function (sum, method) {
                    if (!method.selected) {
                        return sum;
                    }

                    return sum + Number(method.amount || 0);
                }, 0);

                const advance = state.useAdvance ? Number(state.advanceAmount || 0) : 0;

                return base + advance;
            },
            dueAmount: function () {
                const totalsStore = global.PosV3.useTotalsStore();

                return Math.max(0, Number(totalsStore.grandTotal || 0) - this.paymentTotal);
            },
            availableAdvance: function () {
                const customerStore = global.PosV3.useCustomerStore();
                const customer = customerStore.selectedCustomer;

                if (!customer) {
                    return 0;
                }

                return Number(customer.available_advance || customer.advance || 0);
            },
            showAdvance: function () {
                const customerStore = global.PosV3.useCustomerStore();
                const configStore = global.PosV3.useConfigStore();

                return !!configStore.features.advance
                    && !!customerStore.selectedCustomer
                    && Number(customerStore.selectedCustomer.id) !== 1
                    && this.availableAdvance > 0;
            },
            totalCashPayment: function (state) {
                const cashMethod = state.methods.find(function (method) {
                    return /^cash$/i.test(String(method.title || '').trim())
                        || String(method.id || '').toLowerCase() === 'cash';
                });

                return cashMethod ? Number(cashMethod.amount || 0) : 0;
            },
            showCashExchange: function () {
                return this.totalCashPayment > 0;
            },
            exchangeAmount: function (state) {
                const diff = Number(state.cashReceived || 0) - this.totalCashPayment;

                return Math.max(0, diff);
            },
        },
        actions: {
            loadMethods: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.paymentMethods;

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('payments', true);

                return global.PosV3.api.get(route)
                    .then(function (response) {
                        const rows = response.data && response.data.success && response.data.data
                            ? response.data.data
                            : [];

                        if (!rows.length) {
                            return;
                        }

                        this.methods = sortPaymentMethods(rows.map(function (method) {
                            return {
                                id: method.id,
                                payment_type_id: method.payment_type_id,
                                title: method.title,
                                account_id: method.account_id,
                                account_name: method.account_name,
                                selected: true,
                                amount: 0,
                            };
                        }));
                    }.bind(this))
                    .catch(function () {
                        this.methods = defaultPaymentMethods();
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('payments', false);
                    });
            },
            resetAdvance: function () {
                this.useAdvance = false;
                this.advanceAmount = 0;
            },
            resetAfterOrder: function () {
                this.resetAdvance();
                this.cashReceived = 0;

                this.methods.forEach(function (method) {
                    method.amount = 0;
                });
            },
            setCashReceived: function (value) {
                this.cashReceived = Math.max(0, Number(value) || 0);
            },
            populateFromLines: function (lines) {
                const payments = Array.isArray(lines) ? lines : [];

                this.methods.forEach(function (method) {
                    const existing = payments.find(function (payment) {
                        return String(payment.method) === String(method.id);
                    });

                    method.amount = existing ? Number(existing.amount || 0) : 0;
                });
            },
            getPaymentMaxAmount: function (method) {
                const otherPayments = this.methods.reduce(function (sum, row) {
                    if (row.id !== method.id && row.selected) {
                        return sum + (parseFloat(row.amount) || 0);
                    }

                    return sum;
                }, 0);

                const advance = this.useAdvance ? (Number(this.advanceAmount) || 0) : 0;
                const totalsStore = global.PosV3.useTotalsStore();
                const remaining = Number(totalsStore.grandTotal || 0) - otherPayments - advance;

                return Math.max(0, remaining);
            },
            updatePaymentValue: function (event, method) {
                let value = parseFloat(event.target.value) || 0;
                const maxAmount = this.getPaymentMaxAmount(method);

                if (value > maxAmount) {
                    value = maxAmount;
                    event.target.value = value;
                }

                value = Math.max(0, value);
                method.amount = value;

                const otherPayments = this.methods.reduce(function (sum, row) {
                    if (row.id !== method.id && row.selected) {
                        return sum + (parseFloat(row.amount) || 0);
                    }

                    return sum;
                }, 0);

                const advance = this.useAdvance ? (Number(this.advanceAmount) || 0) : 0;
                const totalsStore = global.PosV3.useTotalsStore();
                const totalPaid = otherPayments + method.amount + advance;

                if (totalPaid > totalsStore.grandTotal) {
                    const excess = totalPaid - totalsStore.grandTotal;
                    method.amount = Math.max(0, method.amount - excess);
                    event.target.value = method.amount;
                }
            },
            incrementPaymentValue: function (event, method, step) {
                const current = parseFloat(method.amount) || 0;
                const maxAmount = this.getPaymentMaxAmount(method);
                const delta = typeof step === 'number' ? step : 1;
                const newValue = Math.min(maxAmount, current + delta);

                method.amount = newValue;
                event.target.value = newValue;
            },
            decrementPaymentValue: function (event, method, step) {
                const current = parseFloat(method.amount) || 0;
                const delta = typeof step === 'number' ? step : 1;
                const newValue = Math.max(0, current - delta);

                method.amount = newValue;
                event.target.value = newValue;
            },
            onPaymentFocus: function (event, method) {
                const dueAmount = this.getPaymentMaxAmount(method);

                method.amount = dueAmount;
                event.target.value = dueAmount;

                if (typeof event.target.select === 'function') {
                    event.target.select();
                }
            },
            onAdvanceCheckboxChange: function () {
                if (this.useAdvance) {
                    const basePayments = this.methods.reduce(function (sum, method) {
                        return sum + (method.selected ? Number(method.amount || 0) : 0);
                    }, 0);
                    const totalsStore = global.PosV3.useTotalsStore();
                    const dueAmount = Number(totalsStore.grandTotal || 0) - basePayments;
                    const availableAdvance = this.availableAdvance;

                    if (dueAmount > 0 && availableAdvance > 0) {
                        this.advanceAmount = Math.min(dueAmount, availableAdvance);
                    } else {
                        this.advanceAmount = 0;
                    }
                } else {
                    this.advanceAmount = 0;
                }
            },
            updateAdvanceAmount: function (event) {
                let value = parseFloat(event.target.value) || 0;
                const availableAdvance = this.availableAdvance;

                if (value > availableAdvance) {
                    value = availableAdvance;
                    event.target.value = value;
                }

                this.advanceAmount = Math.max(0, value);
            },
            onAdvanceFocus: function (event) {
                const basePayments = this.methods.reduce(function (sum, method) {
                    return sum + (method.selected ? Number(method.amount || 0) : 0);
                }, 0);
                const totalsStore = global.PosV3.useTotalsStore();
                const dueAmount = Number(totalsStore.grandTotal || 0) - basePayments;
                const availableAdvance = this.availableAdvance;

                if (dueAmount > 0 && availableAdvance > 0) {
                    this.advanceAmount = Math.min(dueAmount, availableAdvance);
                } else if (availableAdvance > 0) {
                    this.advanceAmount = availableAdvance;
                } else {
                    this.advanceAmount = 0;
                }

                event.target.value = this.advanceAmount;

                if (typeof event.target.select === 'function') {
                    event.target.select();
                }
            },
        },
    });
})(window);
