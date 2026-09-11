(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3HoldListModal = {
        name: 'PosV3HoldListModal',
        template: `
            <div v-if="isOpen" class="pos-v3-hold-modal" @click.self="close">
                <div class="pos-v3-hold-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pos-v3-hold-modal-title">
                    <div class="pos-v3-hold-modal__header">
                        <h2 id="pos-v3-hold-modal-title" class="pos-v3-hold-modal__title">Held Orders</h2>
                        <button type="button" class="pos-v3-hold-modal__close" @click="close" aria-label="Close">&times;</button>
                    </div>

                    <div class="pos-v3-hold-modal__body" :class="{ 'is-loading': isLoading }">
                        <table class="pos-v3-hold-modal__table" v-if="holds.length">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Created</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="hold in holds" :key="hold.id">
                                    <td>#{{ hold.id }}</td>
                                    <td>{{ hold.items_count || 0 }}</td>
                                    <td>{{ formatMoney(hold.grand_total || 0) }}</td>
                                    <td>{{ hold.created_at || '—' }}</td>
                                    <td>
                                        <button type="button" class="pos-v3-btn pos-v3-btn--accent pos-v3-btn--sm" :disabled="isRestoring" @click="restoreHold(hold.id)">
                                            Restore
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="pos-v3-hold-modal__empty">
                            {{ isLoading ? 'Loading held orders...' : 'No held orders found.' }}
                        </div>
                    </div>

                    <div class="pos-v3-hold-modal__footer">
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost" :disabled="isLoading" @click="refresh">
                            Refresh
                        </button>
                        <button type="button" class="pos-v3-btn pos-v3-btn--ghost" @click="close">Close</button>
                    </div>
                </div>
            </div>
        `,
        computed: {
            holdStore: function () {
                return global.PosV3.useHoldStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            isOpen: function () {
                return this.uiStore.holdModalOpen;
            },
            isLoading: function () {
                return this.uiStore.loading.holdList;
            },
            isRestoring: function () {
                return this.uiStore.loading.hold;
            },
            holds: function () {
                return this.holdStore.holds;
            },
        },
        methods: {
            formatMoney: function (value) {
                return global.PosV3.formatMoney(value);
            },
            close: function () {
                this.uiStore.setHoldModalOpen(false);
            },
            refresh: function () {
                this.holdStore.fetchHolds();
            },
            restoreHold: function (holdId) {
                this.holdStore.restoreHold(holdId);
            },
        },
    };
})(window);
