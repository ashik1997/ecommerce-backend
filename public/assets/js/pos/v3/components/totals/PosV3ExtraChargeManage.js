(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3ExtraChargeManage = {
        name: 'PosV3ExtraChargeManage',
        data: function () {
            return {
                showModal: false,
                localLines: [],
                newChargeTitle: '',
                newChargeAmount: 0,
                savingType: false,
                selectInitialized: false,
            };
        },
        template: `
            <span class="pos-v3-extra-charge-inline">
                <label class="pos-v3-extra-charge-check" title="Use itemized charge lines">
                    <input type="checkbox" :checked="useExtraChargeLines" @change="toggleEnabled($event.target.checked)">
                    <span v-if="!useExtraChargeLines">Add types</span>
                </label>
                <button
                    v-if="useExtraChargeLines"
                    type="button"
                    class="pos-v3-extra-charge-manage-btn"
                    title="Manage charge lines"
                    @click="openModal">
                    <i class="fas fa-list-ul"></i>
                </button>

                <div v-if="showModal" class="pos-v3-extra-charge-modal-backdrop" @click.self="closeModal">
                    <div class="pos-v3-extra-charge-modal" id="pos-v3-extra-charge-modal">
                        <div class="pos-v3-extra-charge-modal-header">
                            <h5>Extra Charge Lines</h5>
                            <button type="button" class="pos-v3-extra-charge-modal-close" @click="closeModal">&times;</button>
                        </div>

                        <div class="pos-v3-extra-charge-modal-body">
                            <label class="pos-v3-extra-charge-modal-label">Search charge type</label>
                            <select ref="chargeSelect" style="width: 100%;"></select>

                            <div class="pos-v3-extra-charge-modal-table-wrap">
                                <table class="pos-v3-extra-charge-modal-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 55%;">Charge Title</th>
                                            <th style="width: 30%;">Amount</th>
                                            <th style="width: 15%;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(line, index) in localLines" :key="line.temp_id || index">
                                            <td>
                                                <input type="text" class="form-control form-control-sm" v-model="line.title" @input="emitLocalLines">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="0.01" class="form-control form-control-sm text-right" v-model.number="line.amount" @input="emitLocalLines">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(index)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!localLines.length">
                                            <td colspan="3" class="text-center text-muted">No extra charge lines added yet.</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="text-right">Total</th>
                                            <th class="text-right">{{ formatMoney(totalAmount) }}</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="pos-v3-extra-charge-modal-create">
                                <div class="pos-v3-extra-charge-modal-create-title">Create and add new charge type</div>
                                <div class="pos-v3-extra-charge-create-row">
                                    <input type="text" placeholder="Charge title" v-model="newChargeTitle">
                                    <input type="number" min="0" step="0.01" class="amount" placeholder="Amount" v-model.number="newChargeAmount">
                                    <button type="button" :disabled="savingType" @click="createChargeType">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pos-v3-extra-charge-modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" @click="closeModal">Done</button>
                        </div>
                    </div>
                </div>
            </span>
        `,
        computed: {
            totalsStore: function () {
                return global.PosV3.useTotalsStore();
            },
            configStore: function () {
                return global.PosV3.useConfigStore();
            },
            useExtraChargeLines: function () {
                return this.totalsStore.useExtraChargeLines;
            },
            totalAmount: function () {
                return this.localLines.reduce(function (sum, line) {
                    return sum + Math.max(0, Number(line.amount || 0));
                }, 0);
            },
        },
        watch: {
            'totalsStore.extraChargeLines': {
                deep: true,
                immediate: true,
                handler: function (value) {
                    this.localLines = this.normalizeLines(value);
                },
            },
        },
        beforeUnmount: function () {
            this.destroySelect2();
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            normalizeLines: function (lines) {
                return global.PosV3.normalizeExtraChargeLines(lines);
            },
            toggleEnabled: function (checked) {
                this.totalsStore.setUseExtraChargeLines(!!checked);
                this.emitLocalLines();

                if (checked) {
                    this.openModal();
                }
            },
            openModal: function () {
                this.showModal = true;
                this.localLines = this.normalizeLines(this.totalsStore.extraChargeLines);
                this.$nextTick(this.initSelect2);
            },
            closeModal: function () {
                this.showModal = false;
                this.destroySelect2();
                this.emitLocalLines();
            },
            initSelect2: function () {
                const routes = this.configStore.routes || {};

                if (this.selectInitialized || !this.$refs.chargeSelect || typeof $ === 'undefined' || !$.fn.select2 || !routes.extraChargeTypes) {
                    return;
                }

                const vm = this;

                $(this.$refs.chargeSelect).select2({
                    placeholder: 'Search charge type',
                    allowClear: true,
                    dropdownParent: $('#pos-v3-extra-charge-modal'),
                    ajax: {
                        url: routes.extraChargeTypes,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term || '',
                                page: params.page || 1,
                            };
                        },
                        processResults: function (response) {
                            const items = response && response.data ? response.data : [];

                            return {
                                results: items.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.title || item.text,
                                        title: item.title || item.text,
                                        default_amount: Number(item.default_amount || 0),
                                    };
                                }),
                                pagination: response.pagination || { more: false },
                            };
                        },
                    },
                }).on('select2:select', function (event) {
                    vm.addSelectedType(event.params.data);
                    $(vm.$refs.chargeSelect).val(null).trigger('change');
                });

                this.selectInitialized = true;
            },
            destroySelect2: function () {
                if (this.selectInitialized && this.$refs.chargeSelect && typeof $ !== 'undefined' && $.fn.select2) {
                    $(this.$refs.chargeSelect).off('select2:select').select2('destroy');
                }

                this.selectInitialized = false;
            },
            addSelectedType: function (type) {
                this.localLines.push({
                    temp_id: 'charge-' + Date.now() + '-' + Math.random().toString(36).slice(2),
                    charge_type_id: type.id || null,
                    title: type.title || type.text || 'Extra Charge',
                    amount: Math.max(0, Number(type.default_amount || 0)),
                });
                this.emitLocalLines();
            },
            removeLine: function (index) {
                this.localLines.splice(index, 1);
                this.emitLocalLines();
            },
            emitLocalLines: function () {
                this.totalsStore.setExtraChargeLines(this.normalizeLines(this.localLines));
            },
            createChargeType: function () {
                const routes = this.configStore.routes || {};
                const title = (this.newChargeTitle || '').trim();
                const amount = Math.max(0, Number(this.newChargeAmount || 0));

                if (!routes.extraChargeTypeCreate || !title) {
                    return;
                }

                this.savingType = true;

                global.PosV3.api.post(routes.extraChargeTypeCreate, {
                    title: title,
                    default_amount: amount,
                }).then(function (response) {
                    if (response.data && response.data.success && response.data.data) {
                        this.addSelectedType(response.data.data);
                        this.newChargeTitle = '';
                        this.newChargeAmount = 0;
                    }
                }.bind(this)).finally(function () {
                    this.savingType = false;
                }.bind(this));
            },
        },
    };
})(window);
