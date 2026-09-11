(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3HoldActions = {
        name: 'PosV3HoldActions',
        template: `
            <div class="pos-v3-hold-actions" :class="{ 'is-loading': isBusy }">
                <div class="pos-v3-hold-actions__row">
                    <button type="button" class="pos-v3-btn pos-v3-btn--ghost" :disabled="!canAct || isBusy" @click="saveDraft">
                        Save Draft
                    </button>
                    <button type="button" class="pos-v3-btn pos-v3-btn--ghost" :disabled="!canAct || isBusy" @click="holdOrder">
                        {{ holdLabel }}
                    </button>
                    <button type="button" class="pos-v3-btn pos-v3-btn--ghost" :disabled="isBusy" @click="openHoldList">
                        Hold List
                    </button>
                    <button type="button" class="pos-v3-btn pos-v3-btn--danger" :disabled="!canAct || isBusy" @click="cancelOrder">
                        Cancel
                    </button>
                </div>
                <div class="pos-v3-hold-actions__hint" v-if="hasSavedDraft">
                    <span>Saved draft available.</span>
                    <button type="button" class="pos-v3-hold-actions__link" @click="restoreDraft">Restore</button>
                </div>
            </div>
        `,
        computed: {
            configStore: function () {
                return global.PosV3.useConfigStore();
            },
            cartStore: function () {
                return global.PosV3.useCartStore();
            },
            draftStore: function () {
                return global.PosV3.useDraftStore();
            },
            holdStore: function () {
                return global.PosV3.useHoldStore();
            },
            orderStore: function () {
                return global.PosV3.useOrderStore();
            },
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            canAct: function () {
                return this.cartStore.items.length > 0;
            },
            hasSavedDraft: function () {
                return this.draftStore.hasSavedDraft;
            },
            holdEnabled: function () {
                return !!this.configStore.features.hold;
            },
            isHolding: function () {
                return this.uiStore.loading.hold;
            },
            isBusy: function () {
                return this.uiStore.loading.hold || this.uiStore.loading.holdList;
            },
            holdLabel: function () {
                return this.isHolding ? 'Holding...' : 'Hold';
            },
        },
        methods: {
            saveDraft: function () {
                this.draftStore.saveDraft();
            },
            restoreDraft: function () {
                this.draftStore.restoreDraft();
            },
            holdOrder: function () {
                if (!this.holdEnabled) {
                    global.PosV3.alerts.show('Hold is not enabled.', 'info');
                    return;
                }

                this.holdStore.holdCurrentOrder();
            },
            openHoldList: function () {
                if (!this.holdEnabled) {
                    global.PosV3.alerts.show('Hold is not enabled.', 'info');
                    return;
                }

                this.holdStore.openHoldList();
            },
            cancelOrder: function () {
                this.orderStore.cancelOrder();
            },
        },
    };
})(window);
