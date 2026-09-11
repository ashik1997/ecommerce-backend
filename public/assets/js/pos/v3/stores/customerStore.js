(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function defaultWalkInCustomer() {
        return {
            id: 1,
            name: 'walking customer',
            phone: '',
            image: null,
            advance: 0,
            available_advance: 0,
            due_amount: 0,
            order_count: 0,
        };
    }

    function validatePhone(phone) {
        if (!phone) {
            return false;
        }

        phone = phone.toString().replace(/\D/g, '');

        if (phone.length === 11 && phone.startsWith('01')) {
            return true;
        }

        if (phone.length === 13 && phone.startsWith('8801')) {
            return true;
        }

        return false;
    }

    function getFormHelpers() {
        return global.PosV3.customerFormHelpers || {};
    }

    global.PosV3.useCustomerStore = Pinia.defineStore('posV3Customer', {
        state: function () {
            const helpers = getFormHelpers();

            return {
                selectedCustomer: defaultWalkInCustomer(),
                searchQuery: '',
                customers: [],
                page: 1,
                lastPage: 1,
                total: 0,
                isNewCustomer: false,
                isInvalidPhone: false,
                modalMode: 'list',
                formCustomer: helpers.emptyFormCustomer ? helpers.emptyFormCustomer() : {},
                viewCustomer: null,
                historyCustomer: null,
                history: null,
                historyActiveTab: 'orders',
                districts: [],
                billingAddresses: helpers.emptyAddress ? [helpers.emptyAddress()] : [],
                shippingAddresses: helpers.emptyAddress ? [helpers.emptyAddress()] : [],
                contactPersons: helpers.emptyContact ? [helpers.emptyContact()] : [],
                saveAsUser: false,
                password: '',
            };
        },
        getters: {
            isWalkIn: function (state) {
                return Number(state.selectedCustomer && state.selectedCustomer.id) === 1;
            },
            displayName: function (state) {
                if (!state.selectedCustomer) {
                    return 'No customer';
                }

                return state.selectedCustomer.name || state.selectedCustomer.phone || 'Customer #' + state.selectedCustomer.id;
            },
            customerPhone: function (state) {
                return state.selectedCustomer && state.selectedCustomer.phone
                    ? state.selectedCustomer.phone
                    : '';
            },
            dueAmount: function (state) {
                return state.selectedCustomer && state.selectedCustomer.due_amount
                    ? Number(state.selectedCustomer.due_amount)
                    : 0;
            },
            availableAdvance: function (state) {
                const customer = state.selectedCustomer;

                if (!customer) {
                    return 0;
                }

                return Number(customer.available_advance || customer.advance || 0);
            },
            orderCount: function (state) {
                return state.selectedCustomer && state.selectedCustomer.order_count
                    ? Number(state.selectedCustomer.order_count)
                    : 0;
            },
            imageUrl: function () {
                const configStore = global.PosV3.useConfigStore();
                const image = this.selectedCustomer && this.selectedCustomer.image;

                if (!image || !configStore.imageUrl) {
                    return '';
                }

                return String(configStore.imageUrl).replace(/\/$/, '') + '/' + String(image).replace(/^\//, '');
            },
            modalTitle: function (state) {
                if (state.modalMode === 'add') {
                    return 'Add Customer';
                }

                if (state.modalMode === 'edit') {
                    return 'Edit Customer';
                }

                if (state.modalMode === 'view') {
                    return 'Customer Profile';
                }

                return 'Customer Management';
            },
        },
        actions: {
            loadDefaultCustomer: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.customerSearch;
                const customerId = configStore.defaults.customerId || 1;

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('customers', true);

                return global.PosV3.api.get(route, {
                    customer_id: customerId,
                    first: true,
                })
                    .then(function (response) {
                        const customer = response.data && response.data.data;

                        if (customer && customer.id) {
                            this.selectCustomer(customer, { keepModalOpen: true });
                        }
                    }.bind(this))
                    .catch(function () {
                        this.resetToWalkingCustomer();
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('customers', false);
                    });
            },
            searchCustomers: function (page) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.customerSearch;

                if (!route) {
                    return Promise.resolve();
                }

                if (typeof page === 'number') {
                    this.page = page;
                }

                uiStore.setLoading('customers', true);

                return global.PosV3.api.get(route, {
                    q: this.searchQuery,
                    page: this.page,
                    per_page: 10,
                })
                    .then(function (response) {
                        const payload = response.data && response.data.data;

                        if (!payload) {
                            this.customers = [];
                            return;
                        }

                        if (Array.isArray(payload)) {
                            this.customers = payload;
                            this.lastPage = 1;
                            this.total = payload.length;
                            return;
                        }

                        this.customers = payload.data || [];
                        this.page = Number(payload.current_page || 1);
                        this.lastPage = Number(payload.last_page || 1);
                        this.total = Number(payload.total || this.customers.length);
                    }.bind(this))
                    .catch(function () {
                        this.customers = [];
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('customers', false);
                    });
            },
            setSearchQuery: function (value) {
                this.searchQuery = value || '';
                this.page = 1;
            },
            setCustomerPhone: function (value) {
                if (!this.selectedCustomer) {
                    this.selectedCustomer = defaultWalkInCustomer();
                }

                this.selectedCustomer.phone = value || '';
                this.isInvalidPhone = false;
            },
            validatePhoneOnType: function () {
                if (!this.selectedCustomer || this.selectedCustomer.phone == null) {
                    return;
                }

                const raw = String(this.selectedCustomer.phone);
                const digits = raw.replace(/\D/g, '');
                const maxLen = 11;

                if (digits.length > maxLen) {
                    this.selectedCustomer.phone = digits.slice(0, maxLen);
                } else if (digits !== raw) {
                    this.selectedCustomer.phone = digits;
                }

                this.isInvalidPhone = false;
            },
            searchCustomerByPhone: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.customerSearch;
                const phone = this.selectedCustomer && this.selectedCustomer.phone;

                this.isInvalidPhone = false;

                if (!validatePhone(phone)) {
                    this.isInvalidPhone = true;
                    return Promise.resolve();
                }

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('customers', true);

                return global.PosV3.api.get(route, { phone: phone })
                    .then(function (response) {
                        const customer = response.data && response.data.data;

                        if (!customer || !customer.id) {
                            this.isNewCustomer = true;
                            this.selectedCustomer = {
                                id: null,
                                name: '',
                                phone: phone,
                                image: null,
                                advance: 0,
                                available_advance: 0,
                                due_amount: 0,
                                order_count: 0,
                            };
                            return;
                        }

                        this.selectCustomer(customer, { keepModalOpen: true });
                    }.bind(this))
                    .catch(function () {
                        global.PosV3.alerts.show('Failed to search customer', 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('customers', false);
                    });
            },
            saveNewCustomer: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.customerCreate;
                const name = this.selectedCustomer && this.selectedCustomer.name;
                const phone = this.selectedCustomer && this.selectedCustomer.phone;

                if (!name || !phone) {
                    global.PosV3.alerts.show('Name and phone are required', 'warning');
                    return Promise.resolve();
                }

                if (!validatePhone(phone)) {
                    this.isInvalidPhone = true;
                    return Promise.resolve();
                }

                if (!route) {
                    return Promise.resolve();
                }

                uiStore.setLoading('customers', true);

                return global.PosV3.api.post(route, {
                    name: name,
                    mobile: phone,
                })
                    .then(function (response) {
                        const customer = response.data && response.data.data;

                        if (response.data && response.data.success && customer && customer.id) {
                            this.selectCustomer(customer, { keepModalOpen: true });
                            global.PosV3.alerts.show('Customer saved', 'success');
                        }
                    }.bind(this))
                    .catch(function (error) {
                        const message = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : 'Failed to save customer';

                        global.PosV3.alerts.show(message, 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('customers', false);
                    });
            },
            selectCustomer: function (customer, options) {
                options = options || {};

                if (!customer || !customer.id) {
                    return;
                }

                const paymentStore = global.PosV3.usePaymentStore();
                const previousId = this.selectedCustomer && this.selectedCustomer.id;

                this.selectedCustomer = customer;
                this.isNewCustomer = false;
                this.isInvalidPhone = false;

                if (Number(previousId) !== Number(customer.id)) {
                    paymentStore.resetAdvance();
                    this.history = null;
                    this.historyCustomer = null;
                }

                if (!options.keepModalOpen) {
                    this.closeCustomerModal();
                }
            },
            clearSelection: function () {
                this.resetToWalkingCustomer();
                this.closeCustomerModal();
            },
            resetToWalkingCustomer: function () {
                this.isNewCustomer = false;
                this.isInvalidPhone = false;
                this.selectedCustomer = defaultWalkInCustomer();
                this.history = null;
                this.historyCustomer = null;
                global.PosV3.usePaymentStore().resetAdvance();
            },
            prepareCustomerModal: function () {
                return Promise.all([
                    this.searchCustomers(1),
                    this.loadDistricts(),
                ]);
            },
            closeCustomerModal: function () {
                global.PosV3.useUiStore().setCustomerModalOpen(false);
                this.resetModalState();
            },
            resetModalState: function () {
                const helpers = getFormHelpers();

                this.modalMode = 'list';
                this.viewCustomer = null;
                this.formCustomer = helpers.emptyFormCustomer ? helpers.emptyFormCustomer() : {};
                this.billingAddresses = helpers.emptyAddress ? [helpers.emptyAddress()] : [];
                this.shippingAddresses = helpers.emptyAddress ? [helpers.emptyAddress()] : [];
                this.contactPersons = helpers.emptyContact ? [helpers.emptyContact()] : [];
                this.saveAsUser = false;
                this.password = '';
            },
            setModalMode: function (mode) {
                const helpers = getFormHelpers();

                this.modalMode = mode;

                if (mode === 'add') {
                    this.formCustomer = helpers.emptyFormCustomer ? helpers.emptyFormCustomer() : {};
                    this.billingAddresses = helpers.emptyAddress ? [helpers.emptyAddress()] : [];
                    this.shippingAddresses = helpers.emptyAddress ? [helpers.emptyAddress()] : [];
                    this.contactPersons = helpers.emptyContact ? [helpers.emptyContact()] : [];
                    this.saveAsUser = false;
                    this.password = '';
                    this.viewCustomer = null;
                    return;
                }

                if (mode === 'list') {
                    this.viewCustomer = null;
                    this.searchQuery = '';
                    this.searchCustomers(1);
                }
            },
            loadDistricts: function () {
                if (this.districts.length) {
                    return Promise.resolve(this.districts);
                }

                const configStore = global.PosV3.useConfigStore();
                const route = configStore.routes.districts || '/api/get/all/districts';

                return global.PosV3.api.get(route)
                    .then(function (response) {
                        this.districts = response.data && response.data.data ? response.data.data : [];
                        return this.districts;
                    }.bind(this))
                    .catch(function () {
                        this.districts = [];
                        return [];
                    }.bind(this));
            },
            fetchCustomerDetail: function (customerId) {
                const configStore = global.PosV3.useConfigStore();
                const route = configStore.routes.customerSearch;

                if (!route || !customerId) {
                    return Promise.reject(new Error('Customer not found'));
                }

                return global.PosV3.api.get(route, {
                    customer_id: customerId,
                    first: true,
                }).then(function (response) {
                    if (response.data && response.data.success && response.data.data) {
                        return response.data.data;
                    }

                    throw new Error('Customer not found');
                });
            },
            applyFormFromCustomer: function (customer) {
                const helpers = getFormHelpers();

                if (!helpers.populateFormFromCustomer) {
                    return;
                }

                const populated = helpers.populateFormFromCustomer(customer, this.districts);

                this.formCustomer = populated.formCustomer;
                this.billingAddresses = populated.billingAddresses;
                this.shippingAddresses = populated.shippingAddresses;
                this.contactPersons = populated.contactPersons;
                this.saveAsUser = false;
                this.password = '';
            },
            openAddCustomer: function () {
                this.setModalMode('add');
            },
            openViewCustomer: function (customer) {
                const uiStore = global.PosV3.useUiStore();
                const customerId = customer && customer.id;

                if (!customerId) {
                    return Promise.resolve();
                }

                this.modalMode = 'view';
                this.viewCustomer = null;
                uiStore.setLoading('customerDetail', true);

                return this.loadDistricts()
                    .then(function () {
                        return this.fetchCustomerDetail(customerId);
                    }.bind(this))
                    .then(function (detail) {
                        this.viewCustomer = detail;
                    }.bind(this))
                    .catch(function () {
                        global.PosV3.alerts.show('Failed to load customer details', 'error');
                        this.setModalMode('list');
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('customerDetail', false);
                    });
            },
            openEditCustomer: function (customer) {
                const uiStore = global.PosV3.useUiStore();
                const customerId = customer && customer.id;
                const useDetail = customer && (customer.billing_address || customer.contact_persons);

                this.modalMode = 'edit';
                this.viewCustomer = null;

                if (useDetail) {
                    return this.loadDistricts().then(function () {
                        this.applyFormFromCustomer(customer);
                    }.bind(this));
                }

                if (!customerId) {
                    return Promise.resolve();
                }

                uiStore.setLoading('customerDetail', true);

                return this.loadDistricts()
                    .then(function () {
                        return this.fetchCustomerDetail(customerId);
                    }.bind(this))
                    .then(function (detail) {
                        this.applyFormFromCustomer(detail);
                    }.bind(this))
                    .catch(function () {
                        global.PosV3.alerts.show('Failed to load customer details', 'error');
                        this.setModalMode('list');
                    }.bind(this))
                    .finally(function () {
                        uiStore.setLoading('customerDetail', false);
                    });
            },
            openEditSelectedCustomer: function () {
                const uiStore = global.PosV3.useUiStore();
                const customer = this.selectedCustomer;

                if (!customer || Number(customer.id) === 1) {
                    return Promise.resolve();
                }

                this.modalMode = 'edit';
                this.viewCustomer = null;
                uiStore.setCustomerModalOpen(true);

                return this.loadDistricts().then(function () {
                    return this.openEditCustomer(customer);
                }.bind(this));
            },
            openCustomerHistory: function () {
                const uiStore = global.PosV3.useUiStore();
                const customer = this.selectedCustomer;

                if (!customer || !customer.id || Number(customer.id) === 1) {
                    return Promise.resolve();
                }

                this.historyActiveTab = 'orders';
                uiStore.setCustomerHistoryModalOpen(true);

                if (this.history && this.historyCustomer && Number(this.historyCustomer.id) === Number(customer.id)) {
                    return Promise.resolve(this.history);
                }

                return this.fetchCustomerHistory(customer.id, {});
            },
            closeCustomerHistory: function () {
                global.PosV3.useUiStore().setCustomerHistoryModalOpen(false);
            },
            setHistoryActiveTab: function (tab) {
                this.historyActiveTab = tab || 'orders';
            },
            fetchCustomerHistory: function (customerId, options) {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const routeTemplate = configStore.routes.customerHistory;
                const params = options || {};

                if (!routeTemplate || !customerId) {
                    return Promise.resolve();
                }

                const route = routeTemplate.replace('__ID__', customerId);

                uiStore.setLoading('customerHistory', true);

                return global.PosV3.api.get(route, params)
                    .then(function (response) {
                        if (!response.data || !response.data.success) {
                            return;
                        }

                        this.history = response.data.data || null;
                        this.historyCustomer = this.history && this.history.customer
                            ? this.history.customer
                            : { id: customerId };
                    }.bind(this))
                    .catch(function () {
                        global.PosV3.alerts.show('Failed to load customer history', 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('customerHistory', false);
                    });
            },
            saveCustomerFull: function () {
                const configStore = global.PosV3.useConfigStore();
                const uiStore = global.PosV3.useUiStore();
                const route = configStore.routes.customerCreate;
                const helpers = getFormHelpers();

                if (!this.formCustomer.name || !this.formCustomer.phone) {
                    global.PosV3.alerts.show('Name and Phone are required', 'warning');
                    return Promise.resolve();
                }

                if (this.saveAsUser && !this.password) {
                    global.PosV3.alerts.show('Password is required when saving as user', 'warning');
                    return Promise.resolve();
                }

                if (!route || !helpers.buildSavePayload) {
                    return Promise.resolve();
                }

                const payload = helpers.buildSavePayload(
                    this.formCustomer,
                    this.billingAddresses,
                    this.shippingAddresses,
                    this.contactPersons,
                    this.saveAsUser,
                    this.password
                );

                uiStore.setLoading('customerSave', true);

                return global.PosV3.api.post(route, payload)
                    .then(function (response) {
                        const customer = response.data && response.data.data;

                        if (!response.data || !response.data.success || !customer) {
                            return;
                        }

                        if (this.modalMode === 'add') {
                            this.customers.unshift(customer);
                        } else {
                            const index = this.customers.findIndex(function (item) {
                                return Number(item.id) === Number(customer.id);
                            });

                            if (index !== -1) {
                                this.customers.splice(index, 1, customer);
                            }
                        }

                        if (this.selectedCustomer && Number(this.selectedCustomer.id) === Number(customer.id)) {
                            this.selectedCustomer = customer;
                        }

                        this.setModalMode('list');
                        global.PosV3.alerts.show(response.data.message || 'Customer saved successfully', 'success');
                    }.bind(this))
                    .catch(function (error) {
                        const message = error.response && error.response.data && error.response.data.message
                            ? error.response.data.message
                            : 'Failed to save customer';

                        global.PosV3.alerts.show(message, 'error');
                    })
                    .finally(function () {
                        uiStore.setLoading('customerSave', false);
                    });
            },
            deleteCustomer: function (customer) {
                const configStore = global.PosV3.useConfigStore();
                const route = configStore.routes.customerDelete;

                if (!customer || !customer.id || !route) {
                    return Promise.resolve();
                }

                const label = customer.name || 'this customer';

                return global.PosV3.alerts.confirm('Delete Customer?', 'Are you sure you want to delete ' + label + '? This action cannot be undone.', 'warning')
                    .then(function (confirmed) {
                        if (!confirmed) {
                            return;
                        }

                        return global.PosV3.api.post(route, { id: customer.id })
                            .then(function (response) {
                                if (!response.data || !response.data.success) {
                                    return;
                                }

                                this.customers = this.customers.filter(function (item) {
                                    return Number(item.id) !== Number(customer.id);
                                });

                                if (this.selectedCustomer && Number(this.selectedCustomer.id) === Number(customer.id)) {
                                    this.resetToWalkingCustomer();
                                }

                                global.PosV3.alerts.show(response.data.message || 'Customer has been deleted.', 'success');
                            }.bind(this))
                            .catch(function (error) {
                                const message = error.response && error.response.data && error.response.data.message
                                    ? error.response.data.message
                                    : 'Failed to delete customer';

                                global.PosV3.alerts.show(message, 'error');
                            });
                    }.bind(this));
            },
            addBillingAddress: function () {
                const helpers = getFormHelpers();
                this.billingAddresses.push(helpers.emptyAddress ? helpers.emptyAddress() : {});
            },
            removeBillingAddress: function (index) {
                this.billingAddresses.splice(index, 1);

                if (!this.billingAddresses.length) {
                    this.addBillingAddress();
                }
            },
            addShippingAddress: function () {
                const helpers = getFormHelpers();
                this.shippingAddresses.push(helpers.emptyAddress ? helpers.emptyAddress() : {});
            },
            removeShippingAddress: function (index) {
                this.shippingAddresses.splice(index, 1);

                if (!this.shippingAddresses.length) {
                    this.addShippingAddress();
                }
            },
            addContactPerson: function () {
                const helpers = getFormHelpers();
                const contact = helpers.emptyContact ? helpers.emptyContact() : {};

                contact.is_primary = this.contactPersons.length === 0;
                this.contactPersons.push(contact);
            },
            removeContactPerson: function (index) {
                this.contactPersons.splice(index, 1);

                if (!this.contactPersons.length) {
                    this.addContactPerson();
                } else if (!this.contactPersons.some(function (contact) { return contact.is_primary; })) {
                    this.contactPersons[0].is_primary = true;
                }
            },
            setPrimaryContact: function (index) {
                if (!this.contactPersons[index].is_primary) {
                    if (!this.contactPersons.some(function (contact) { return contact.is_primary; })) {
                        this.contactPersons[index].is_primary = true;
                    }
                    return;
                }

                this.contactPersons.forEach(function (contact, contactIndex) {
                    contact.is_primary = contactIndex === index;
                });
            },
        },
    });
})(window);
