(function (global) {
    'use strict';

    global.PosV3 = global.PosV3 || {};
    global.PosV3.components = global.PosV3.components || {};

    global.PosV3.components.PosV3SettingsModal = {
        name: 'PosV3SettingsModal',
        template: `
            <div v-if="isOpen" class="pos-v3-settings-modal" @click.self="close">
                <div class="pos-v3-settings-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pos-v3-settings-title">
                    <div class="pos-v3-settings-modal__header">
                        <h2 id="pos-v3-settings-title" class="pos-v3-settings-modal__title">POS Settings</h2>
                        <button type="button" class="pos-v3-settings-modal__close" @click="close" aria-label="Close">&times;</button>
                    </div>

                    <div class="pos-v3-settings-modal__body">
                        <section class="pos-v3-settings-section">
                            <h3 class="pos-v3-settings-section__title">UI Control</h3>

                            <label class="pos-v3-settings-row">
                                <span class="pos-v3-settings-row__text">
                                    <strong>Hide product list after select</strong>
                                    <small>When off, category/search results stay open so you can add multiple products. Press Esc or Close to dismiss.</small>
                                </span>
                                <span class="pos-v3-switch">
                                    <input
                                        type="checkbox"
                                        class="pos-v3-switch__input"
                                        :checked="hideProductListAfterSelect"
                                        @change="onHideAfterSelectChange($event.target.checked)">
                                    <span class="pos-v3-switch__track" aria-hidden="true"></span>
                                </span>
                            </label>
                        </section>
                    </div>
                </div>
            </div>
        `,
        computed: {
            uiStore: function () {
                return global.PosV3.useUiStore();
            },
            settingsStore: function () {
                return global.PosV3.useSettingsStore();
            },
            isOpen: function () {
                return this.uiStore.settingsModalOpen;
            },
            hideProductListAfterSelect: function () {
                return this.settingsStore.shouldHideProductListAfterSelect;
            },
        },
        methods: {
            close: function () {
                this.uiStore.setSettingsModalOpen(false);
            },
            onHideAfterSelectChange: function (value) {
                this.settingsStore.setHideProductListAfterSelect(value);
            },
        },
    };
})(window);
