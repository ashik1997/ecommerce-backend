(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};

    function emptyAddress() {
        return {
            full_name: '',
            phone: '',
            address: '',
            district: null,
        };
    }

    function emptyContact() {
        return {
            name: '',
            designation: '',
            phone: '',
            email: '',
            department: '',
            is_primary: true,
            note: '',
        };
    }

    function emptyFormCustomer() {
        return {
            id: null,
            name: '',
            phone: '',
            email: '',
            address: '',
            image: null,
            customer_type: 'person',
            company_name: '',
            trade_name: '',
            bin_no: '',
            tin_no: '',
            credit_limit: 0,
            payment_terms_days: null,
            allow_due: true,
            customer_source_type_id: '',
        };
    }

    function parseAddressList(raw) {
        if (!raw) {
            return [emptyAddress()];
        }

        if (Array.isArray(raw)) {
            return raw.length ? raw : [emptyAddress()];
        }

        try {
            const parsed = JSON.parse(raw);
            return Array.isArray(parsed) && parsed.length ? parsed : [emptyAddress()];
        } catch (error) {
            return [emptyAddress()];
        }
    }

    function mapAddressForForm(addr, districts) {
        const addressObj = {
            full_name: addr.full_name || '',
            phone: addr.phone || '',
            address: addr.address || '',
            district: null,
        };

        if (addr.division_id && addr.district_id && districts && districts.length) {
            const found = districts.find(function (district) {
                return district.division_id == addr.division_id && district.district_id == addr.district_id;
            });

            if (found) {
                addressObj.district = found;
            }
        }

        return addressObj;
    }

    function mapAddressesForForm(raw, districts) {
        const list = parseAddressList(raw);
        const mapped = list.map(function (addr) {
            return mapAddressForForm(addr, districts);
        });

        return mapped.length ? mapped : [emptyAddress()];
    }

    function populateFormFromCustomer(customer, districts) {
        const formCustomer = Object.assign(emptyFormCustomer(), {
            id: customer.id || null,
            name: customer.name || '',
            phone: customer.phone || customer.mobile || '',
            email: customer.email || '',
            address: customer.address || '',
            image: customer.image || null,
            customer_type: customer.customer_type || 'person',
            company_name: customer.company_name || '',
            trade_name: customer.trade_name || '',
            bin_no: customer.bin_no || '',
            tin_no: customer.tin_no || '',
            credit_limit: customer.credit_limit || 0,
            payment_terms_days: customer.payment_terms_days || null,
            allow_due: customer.allow_due !== false,
            customer_source_type_id: customer.customer_source_type_id || '',
        });

        const contactPersons = customer.contact_persons && customer.contact_persons.length
            ? customer.contact_persons.map(function (contact) {
                return Object.assign(emptyContact(), contact, {
                    is_primary: !!contact.is_primary,
                });
            })
            : [emptyContact()];

        return {
            formCustomer: formCustomer,
            billingAddresses: mapAddressesForForm(customer.billing_address, districts),
            shippingAddresses: mapAddressesForForm(customer.shipping_address, districts),
            contactPersons: contactPersons,
        };
    }

    function buildSavePayload(formCustomer, billingAddresses, shippingAddresses, contactPersons, saveAsUser, password) {
        const payload = {
            id: formCustomer.id || null,
            name: formCustomer.name,
            mobile: formCustomer.phone,
            email: formCustomer.email || null,
            address: formCustomer.address || null,
            save_as_user: saveAsUser ? 1 : 0,
            password: saveAsUser ? password : null,
            image: formCustomer.image || null,
            customer_source_type_id: formCustomer.customer_source_type_id || null,
            customer_type: formCustomer.customer_type || 'person',
            company_name: formCustomer.company_name || null,
            trade_name: formCustomer.trade_name || null,
            bin_no: formCustomer.bin_no || null,
            tin_no: formCustomer.tin_no || null,
            credit_limit: formCustomer.credit_limit || 0,
            payment_terms_days: formCustomer.payment_terms_days || null,
            allow_due: formCustomer.allow_due ? 1 : 0,
        };

        const billing = (billingAddresses || [])
            .filter(function (addr) {
                return addr.full_name || addr.address;
            })
            .map(function (addr) {
                const addressData = {
                    full_name: addr.full_name || null,
                    phone: addr.phone || null,
                    address: addr.address || null,
                };

                if (addr.district && addr.district.district_id && addr.district.division_id) {
                    addressData.district_id = addr.district.district_id;
                    addressData.division_id = addr.district.division_id;
                }

                return addressData;
            });

        if (billing.length) {
            payload.billing_address = billing;
        }

        const shipping = (shippingAddresses || [])
            .filter(function (addr) {
                return addr.full_name || addr.address;
            })
            .map(function (addr) {
                const addressData = {
                    full_name: addr.full_name || null,
                    phone: addr.phone || null,
                    address: addr.address || null,
                };

                if (addr.district && addr.district.district_id && addr.district.division_id) {
                    addressData.district_id = addr.district.district_id;
                    addressData.division_id = addr.district.division_id;
                }

                return addressData;
            });

        if (shipping.length) {
            payload.shipping_address = shipping;
        }

        if (payload.customer_type === 'company') {
            payload.contact_persons = (contactPersons || [])
                .filter(function (contact) {
                    return contact.name || contact.phone || contact.email;
                })
                .map(function (contact) {
                    return {
                        id: contact.id || null,
                        name: contact.name || null,
                        designation: contact.designation || null,
                        phone: contact.phone || null,
                        email: contact.email || null,
                        department: contact.department || null,
                        is_primary: contact.is_primary ? 1 : 0,
                        note: contact.note || null,
                    };
                });
        } else {
            payload.contact_persons = [];
        }

        return payload;
    }

    global.PosV3.customerFormHelpers = {
        emptyAddress: emptyAddress,
        emptyContact: emptyContact,
        emptyFormCustomer: emptyFormCustomer,
        mapAddressesForForm: mapAddressesForForm,
        populateFormFromCustomer: populateFormFromCustomer,
        buildSavePayload: buildSavePayload,
    };
})(window);
