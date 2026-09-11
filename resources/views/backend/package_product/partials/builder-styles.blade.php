<style>
    /* ============================================================
       PACKAGE BUILDER — Full UX Overhaul
       Prefix: .package_management
       Brand: Teal (#0d9488)
       Breakpoints: sm<768 md<992 lg<1200 xl>=1200
       ============================================================ */

    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .package_management {
        --pm-teal: #0d9488;
        --pm-teal-light: #14b8a6;
        --pm-teal-dark: #0f766e;
        --pm-teal-50: #f0fdfa;
        --pm-teal-100: #ccfbf1;
        --pm-teal-200: #99f6e4;
        --pm-teal-300: #5eead4;

        --pm-surface: #f8fafc;
        --pm-card: #ffffff;
        --pm-muted: #f1f5f9;
        --pm-border: #e2e8f0;
        --pm-border-2: #cbd5e1;

        --pm-text: #0f172a;
        --pm-text-2: #475569;
        --pm-text-3: #94a3b8;

        --pm-radius: 8px;
        --pm-radius-lg: 12px;

        --pm-shadow: 0 1px 3px rgba(0, 0, 0, .08), 0 1px 2px rgba(0, 0, 0, .04);
        --pm-shadow-md: 0 4px 12px rgba(0, 0, 0, .08);

        --pm-font: 'Inter', -apple-system, sans-serif;
        --pm-ease: cubic-bezier(.4, 0, .2, 1);

        font-family: var(--pm-font);
        color: var(--pm-text);
    }

    .package_management * {
        box-sizing: border-box;
    }

    .package_management [v-cloak] {
        display: none !important;
    }

    /* ============================================================
       DRAFT BANNER
       ============================================================ */
    .package_management .pm-draft-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        border-radius: var(--pm-radius);
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: .84rem;
        flex-wrap: wrap;
    }

    .package_management .pm-draft-banner strong {
        color: #92400e;
    }

    .package_management .pm-draft-banner span {
        color: #78350f;
        font-size: .78rem;
    }

    .package_management .pm-draft-banner__actions {
        display: flex;
        gap: 6px;
    }

    /* ============================================================
       TAB NAVIGATION
       ============================================================ */
    .package_management .pm-tabs-wrap {
        background: var(--pm-card);
        border: 1px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        box-shadow: var(--pm-shadow);
        padding: 8px;
        margin-bottom: 16px;
    }

    .package_management .pm-tabs {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 4px;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .package_management .pm-tab-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 10px 8px;
        border: 1px solid #00000014;
        border-radius: var(--pm-radius);
        background: transparent;
        color: var(--pm-text-2);
        font-family: var(--pm-font);
        font-size: .78rem;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s var(--pm-ease);
        position: relative;
        width: 100%;
        text-align: center;
    }

    .package_management .pm-tab-btn i {
        font-size: .9rem;
        color: var(--pm-text-3);
        transition: color .15s;
    }

    .package_management .pm-tab-btn:hover:not(.active) {
        background: var(--pm-muted);
        color: var(--pm-teal);
    }

    .package_management .pm-tab-btn:hover:not(.active) i {
        color: var(--pm-teal);
    }

    .package_management .pm-tab-btn.active {
        background: var(--pm-teal);
        color: #fff;
        border-color: var(--pm-teal-dark);
        box-shadow: 0 2px 8px rgba(13, 148, 136, .28);
    }

    .package_management .pm-tab-btn.active i {
        color: rgba(255, 255, 255, .85);
    }

    .package_management .pm-tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 99px;
        font-size: .64rem;
        font-weight: 700;
        line-height: 1;
        background: rgba(255, 255, 255, .3);
        color: #fff;
        position: absolute;
        top: 5px;
        right: 5px;
    }

    .package_management .pm-tab-btn:not(.active) .pm-tab-badge {
        background: var(--pm-teal);
        color: #fff;
    }

    /* ============================================================
       TAB CARD SHELL
       ============================================================ */
    .package_management .pm-tab-card {
        background: var(--pm-card);
        border: 1px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        box-shadow: var(--pm-shadow);
        margin-bottom: 16px;
        overflow: visible;
        animation: pm-fadein .18s ease;
    }

    @keyframes pm-fadein {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .package_management .pm-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px 20px;
        background: linear-gradient(to right, var(--pm-muted), rgba(248, 250, 252, .5));
        border-bottom: 1px solid var(--pm-border);
        border-radius: var(--pm-radius-lg) var(--pm-radius-lg) 0 0;
    }

    .package_management .pm-card-header__title {
        font-size: .92rem;
        font-weight: 700;
        color: var(--pm-text);
        margin: 0;
    }

    .package_management .pm-card-header__sub {
        font-size: .76rem;
        color: var(--pm-text-3);
        margin-top: 2px;
        display: block;
    }

    .package_management .pm-card-header__meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .package_management .pm-card-body {
        padding: 20px;
    }

    /* ============================================================
       FORM ELEMENTS
       ============================================================ */
    .package_management .pm-field {
        margin-bottom: 14px;
    }

    .package_management .pm-label {
        display: block;
        font-size: .75rem;
        font-weight: 600;
        color: var(--pm-text-2);
        margin-bottom: 5px;
        letter-spacing: .02em;
    }

    .package_management .pm-label .req {
        color: #ef4444;
        margin-left: 2px;
    }

    .package_management .pm-input,
    .package_management .pm-select,
    .package_management .pm-textarea {
        display: block;
        width: 100%;
        padding: 8px 12px;
        font-family: var(--pm-font);
        font-size: .875rem;
        color: var(--pm-text);
        background: #fff;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        transition: border-color .15s, box-shadow .15s;
        line-height: 1.5;
        -webkit-appearance: none;
        appearance: none;
    }

    .package_management .pm-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 28px;
    }

    .package_management .pm-input:focus,
    .package_management .pm-select:focus,
    .package_management .pm-textarea:focus {
        outline: none;
        border-color: var(--pm-teal);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, .1);
    }

    .package_management .pm-input::placeholder,
    .package_management .pm-textarea::placeholder {
        color: var(--pm-text-3);
        font-size: .84rem;
    }

    .package_management .pm-input--lg {
        padding: 10px 14px;
        font-size: .92rem;
        font-weight: 500;
    }

    .package_management .pm-input-group {
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        overflow: hidden;
        transition: border-color .15s, box-shadow .15s;
        background: #fff;
    }

    .package_management .pm-input-group:focus-within {
        border-color: var(--pm-teal);
        box-shadow: 0 0 0 3px rgba(13, 148, 136, .1);
    }

    .package_management .pm-input-group .pm-input-addon {
        display: flex;
        align-items: center;
        padding: 0 10px;
        background: var(--pm-muted);
        color: var(--pm-text-3);
        font-size: .8rem;
        border-right: 1px solid var(--pm-border);
        white-space: nowrap;
        flex-shrink: 0;
    }

    .package_management .pm-input-group input {
        flex: 1;
        border: none;
        outline: none;
        padding: 8px 10px;
        font-family: var(--pm-font);
        font-size: .875rem;
        color: var(--pm-text);
        background: transparent;
        min-width: 0;
    }

    .package_management .pm-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .package_management .pm-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 12px;
    }

    .package_management .pm-divider {
        height: 1px;
        background: var(--pm-border);
        margin: 16px 0;
    }

    /* ============================================================
       BADGES
       ============================================================ */
    .package_management .pm-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 9px;
        border-radius: 99px;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .package_management .pm-badge--teal {
        background: var(--pm-teal-100);
        color: var(--pm-teal-dark);
    }

    .package_management .pm-badge--green {
        background: #dcfce7;
        color: #15803d;
    }

    .package_management .pm-badge--gray {
        background: var(--pm-muted);
        color: var(--pm-text-2);
    }

    /* ============================================================
       BUTTONS
       ============================================================ */
    .package_management .pm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        font-family: var(--pm-font);
        font-size: .83rem;
        font-weight: 600;
        border-radius: var(--pm-radius);
        border: 1.5px solid transparent;
        cursor: pointer;
        transition: all .15s var(--pm-ease);
        text-decoration: none;
        white-space: nowrap;
        line-height: 1;
        -webkit-appearance: none;
    }

    .package_management .pm-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
        transform: none !important;
    }

    .package_management .pm-btn--primary {
        background: linear-gradient(135deg, var(--pm-teal-light), var(--pm-teal));
        border-color: var(--pm-teal-dark);
        color: #fff;
        box-shadow: 0 1px 3px rgba(13, 148, 136, .3);
    }

    .package_management .pm-btn--primary:hover:not(:disabled) {
        background: linear-gradient(135deg, var(--pm-teal), var(--pm-teal-dark));
        box-shadow: 0 4px 12px rgba(13, 148, 136, .32);
        transform: translateY(-1px);
    }

    .package_management .pm-btn--success {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-color: #15803d;
        color: #fff;
        box-shadow: 0 1px 3px rgba(22, 163, 74, .3);
    }

    .package_management .pm-btn--success:hover:not(:disabled) {
        background: linear-gradient(135deg, #16a34a, #15803d);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(22, 163, 74, .3);
    }

    .package_management .pm-btn--ghost {
        background: #fff;
        border-color: var(--pm-border);
        color: var(--pm-text-2);
    }

    .package_management .pm-btn--ghost:hover:not(:disabled) {
        background: var(--pm-muted);
        border-color: var(--pm-border-2);
        color: var(--pm-text);
    }

    .package_management .pm-btn--danger-ghost {
        background: transparent;
        border-color: #fca5a5;
        color: #dc2626;
    }

    .package_management .pm-btn--danger-ghost:hover:not(:disabled) {
        background: #fef2f2;
        border-color: #f87171;
    }

    .package_management .pm-btn--teal-ghost {
        background: transparent;
        border-color: var(--pm-teal-200);
        color: var(--pm-teal);
    }

    .package_management .pm-btn--teal-ghost:hover:not(:disabled) {
        background: var(--pm-teal-50);
        border-color: var(--pm-teal-300);
    }

    .package_management .pm-btn--danger-solid {
        background: #ef4444;
        border-color: #dc2626;
        color: #fff;
    }

    .package_management .pm-btn--danger-solid:hover:not(:disabled) {
        background: #dc2626;
    }

    .package_management .pm-btn--lg {
        padding: 11px 28px;
        font-size: .9rem;
        border-radius: var(--pm-radius-lg);
    }

    .package_management .pm-btn--sm {
        padding: 5px 10px;
        font-size: .73rem;
        border-radius: 6px;
    }

    .package_management .pm-btn--icon {
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: var(--pm-radius);
    }

    /* ============================================================
       INFO TIP
       ============================================================ */
    .package_management .pm-tip {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        background: var(--pm-teal-50);
        border: 1px solid var(--pm-teal-200);
        border-left: 3px solid var(--pm-teal);
        border-radius: var(--pm-radius);
        padding: 10px 12px;
        font-size: .77rem;
        color: var(--pm-teal-dark);
        margin-top: 12px;
        line-height: 1.5;
    }

    .package_management .pm-tip i {
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* ============================================================
       OVERVIEW TAB
       ============================================================ */
    .package_management .pm-overview-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
        align-items: start;
    }

    /* Pricing Cards */
    .package_management .pm-pricing-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 16px;
    }

    .package_management .pm-price-card {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        padding: 14px;
        transition: box-shadow .15s;
    }

    .package_management .pm-price-card:hover {
        box-shadow: var(--pm-shadow-md);
    }

    .package_management .pm-price-card--bundle {
        border-top: 3px solid var(--pm-teal);
    }

    .package_management .pm-price-card--compare {
        border-top: 3px solid var(--pm-border-2);
    }

    .package_management .pm-price-card--savings {
        border-top: 3px solid #22c55e;
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    }

    .package_management .pm-price-card__label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: .7rem;
        font-weight: 700;
        color: var(--pm-text-3);
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 6px;
    }

    .package_management .pm-price-card__label i {
        font-size: .8rem;
    }

    .package_management .pm-price-card__value {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--pm-text);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .package_management .pm-price-card--savings .pm-price-card__value {
        color: #15803d;
    }

    .package_management .pm-price-card--compare .pm-price-card__value {
        text-decoration: line-through;
        color: var(--pm-text-3);
    }

    .package_management .pm-price-card__sub {
        font-size: .7rem;
        color: var(--pm-text-3);
        margin-top: 4px;
    }

    /* Hero Upload */
    .package_management .pm-hero-upload {
        border: 2px dashed var(--pm-border);
        border-radius: var(--pm-radius-lg);
        background: var(--pm-muted);
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
        overflow: hidden;
        position: relative;
    }

    .package_management .pm-hero-upload:hover {
        border-color: var(--pm-teal);
        background: var(--pm-teal-50);
    }

    .package_management .pm-hero-upload.has-image {
        border-style: solid;
        border-color: var(--pm-border);
    }

    .package_management .pm-hero-upload img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        display: block;
    }

    .package_management .pm-hero-upload__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        padding: 28px;
        text-align: center;
    }

    .package_management .pm-hero-upload__empty i {
        font-size: 2rem;
        color: var(--pm-text-3);
    }

    .package_management .pm-hero-upload__empty p {
        font-size: .76rem;
        color: var(--pm-text-3);
        margin: 0;
        line-height: 1.5;
    }

    .package_management .pm-img-remove {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 26px;
        height: 26px;
        background: #ef4444;
        border: none;
        border-radius: 50%;
        color: #fff;
        font-size: .65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 2;
        transition: background .15s;
    }

    .package_management .pm-img-remove:hover {
        background: #dc2626;
    }

    /* Gallery */
    .package_management .pm-gallery-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 10px;
    }

    .package_management .pm-gallery-slot {
        aspect-ratio: 1;
        border: 2px dashed var(--pm-border);
        border-radius: var(--pm-radius);
        background: var(--pm-muted);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        cursor: pointer;
        transition: all .15s;
        overflow: hidden;
        position: relative;
    }

    .package_management .pm-gallery-slot:hover {
        border-color: var(--pm-teal);
        background: var(--pm-teal-50);
    }

    .package_management .pm-gallery-slot img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .package_management .pm-gallery-slot i {
        font-size: .85rem;
        color: var(--pm-text-3);
    }

    .package_management .pm-gallery-slot span {
        font-size: .68rem;
        color: var(--pm-text-3);
    }

    /* ============================================================
       CATALOG TAB
       ============================================================ */
    .package_management .pm-catalog-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 16px;
        align-items: start;
    }

    /* Search Panel */
    .package_management .pm-search-panel {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        overflow: hidden;
        background: var(--pm-card);
        position: sticky;
        top: 16px;
    }

    .package_management .pm-search-header {
        padding: 12px;
        border-bottom: 1px solid var(--pm-border);
        background: var(--pm-muted);
    }

    .package_management .pm-search-bar {
        display: flex;
        align-items: center;
        gap: 7px;
    }

    .package_management .pm-search-input {
        display: flex;
        align-items: center;
        flex: 1;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        background: #fff;
        overflow: hidden;
        transition: border-color .15s, box-shadow .15s;
    }

    .package_management .pm-search-input:focus-within {
        border-color: var(--pm-teal);
        box-shadow: 0 0 0 2px rgba(13, 148, 136, .1);
    }

    .package_management .pm-search-input i {
        padding: 0 9px;
        color: var(--pm-text-3);
        font-size: .82rem;
        flex-shrink: 0;
    }

    .package_management .pm-search-input input {
        flex: 1;
        border: none;
        outline: none;
        font-family: var(--pm-font);
        font-size: .82rem;
        padding: 8px 8px 8px 0;
        color: var(--pm-text);
        background: transparent;
        min-width: 0;
    }

    .package_management .pm-search-input input::placeholder {
        color: var(--pm-text-3);
    }

    .package_management .pm-icon-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        background: #fff;
        color: var(--pm-text-2);
        cursor: pointer;
        transition: all .15s;
        flex-shrink: 0;
    }

    .package_management .pm-icon-btn:hover {
        border-color: var(--pm-teal);
        color: var(--pm-teal);
        background: var(--pm-teal-50);
    }

    .package_management .pm-search-hint {
        font-size: .7rem;
        color: var(--pm-text-3);
        padding: 7px 12px 0;
    }

    /* Product list */
    .package_management .pm-catalog-list {
        max-height: 440px;
        overflow-y: auto;
        padding: 8px;
        scrollbar-width: thin;
        scrollbar-color: var(--pm-teal-200) transparent;
    }

    .package_management .pm-catalog-list::-webkit-scrollbar {
        width: 4px;
    }

    .package_management .pm-catalog-list::-webkit-scrollbar-thumb {
        background: var(--pm-teal-200);
        border-radius: 99px;
    }

    .package_management .pm-product-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 10px;
        border: 1px solid transparent;
        border-radius: var(--pm-radius);
        cursor: pointer;
        transition: all .15s;
        margin-bottom: 2px;
        -webkit-user-select: none;
        user-select: none;
    }

    .package_management .pm-product-row:hover {
        background: var(--pm-teal-50);
        border-color: var(--pm-teal-200);
    }

    .package_management .pm-product-row__img {
        width: 42px;
        height: 42px;
        object-fit: cover;
        border-radius: var(--pm-radius);
        border: 1px solid var(--pm-border);
        flex-shrink: 0;
    }

    .package_management .pm-product-row__body {
        flex: 1;
        min-width: 0;
    }

    .package_management .pm-product-row__name {
        font-size: .8rem;
        font-weight: 600;
        color: var(--pm-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .package_management .pm-product-row__meta {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-top: 3px;
        flex-wrap: wrap;
    }

    .package_management .pm-product-row__price {
        font-size: .74rem;
        font-weight: 600;
        color: var(--pm-teal);
    }

    .package_management .pm-product-row__compare {
        font-size: .7rem;
        color: var(--pm-text-3);
        text-decoration: line-through;
    }

    .package_management .pm-product-row__type {
        font-size: .62rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: 1px 5px;
        border-radius: 4px;
        background: var(--pm-muted);
        color: var(--pm-text-3);
    }

    .package_management .pm-product-row__stock {
        font-size: .68rem;
        color: var(--pm-text-3);
    }

    .package_management .pm-product-row__icon {
        color: var(--pm-teal-200);
        font-size: .95rem;
        flex-shrink: 0;
        transition: all .15s;
    }

    .package_management .pm-product-row:hover .pm-product-row__icon {
        color: var(--pm-teal);
        transform: scale(1.2);
    }

    /* Empty / Loading */
    .package_management .pm-list-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 36px 16px;
        color: var(--pm-text-3);
        font-size: .8rem;
        text-align: center;
    }

    .package_management .pm-list-state i {
        font-size: 1.8rem;
        color: var(--pm-teal-200);
    }

    .package_management .pm-list-state .spinner-border {
        color: var(--pm-teal) !important;
        width: 22px;
        height: 22px;
        border-width: 2.5px;
    }

    /* Items table panel */
    .package_management .pm-items-panel {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        overflow: hidden;
        background: var(--pm-card);
    }

    .package_management .pm-items-head {
        padding: 10px 14px;
        background: var(--pm-muted);
        border-bottom: 1px solid var(--pm-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }

    .package_management .pm-items-title {
        font-size: .72rem;
        font-weight: 700;
        color: var(--pm-text-2);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .package_management .pm-items-scroll {
        max-height: 480px;
        overflow-y: auto;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: var(--pm-teal-200) transparent;
    }

    .package_management .pm-items-scroll::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .package_management .pm-items-scroll::-webkit-scrollbar-thumb {
        background: var(--pm-teal-200);
        border-radius: 99px;
    }

    .package_management .pm-items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: .81rem;
        min-width: 640px;
    }

    .package_management .pm-items-table thead th {
        padding: 9px 10px;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--pm-text-3);
        background: var(--pm-muted);
        border-bottom: 1.5px solid var(--pm-border);
        white-space: nowrap;
        text-align: left;
    }

    .package_management .pm-items-table thead th.th-right {
        text-align: right;
    }

    .package_management .pm-items-table thead th.th-center {
        text-align: center;
    }

    .package_management .pm-items-table tbody tr {
        border-bottom: 1px solid var(--pm-border);
        transition: background .12s;
    }

    .package_management .pm-items-table tbody tr:hover {
        background: var(--pm-teal-50);
    }

    .package_management .pm-items-table tbody tr:last-child {
        border-bottom: none;
    }

    .package_management .pm-items-table tbody td {
        padding: 9px 10px;
        vertical-align: middle;
        color: var(--pm-text);
    }

    .package_management .pm-items-table tfoot td {
        padding: 10px 12px;
        font-size: .82rem;
        font-weight: 700;
        background: linear-gradient(to right, var(--pm-teal-50), var(--pm-teal-100));
        color: var(--pm-teal-dark);
        border-top: 2px solid var(--pm-teal-200);
        text-align: right;
    }

    /* Table item thumbnail */
    .package_management .pm-thumb-wrap {
        position: relative;
        display: inline-block;
    }

    .package_management .pm-thumb-wrap img {
        width: 38px;
        height: 38px;
        object-fit: cover;
        border-radius: var(--pm-radius);
        border: 1px solid var(--pm-border);
        display: block;
    }

    .package_management .pm-thumb-cam {
        position: absolute;
        bottom: -3px;
        right: -3px;
        width: 16px;
        height: 16px;
        background: var(--pm-card);
        border: 1px solid var(--pm-border);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .5rem;
        color: var(--pm-text-2);
        cursor: pointer;
        transition: all .15s;
        padding: 0;
        line-height: 1;
    }

    .package_management .pm-thumb-cam:hover {
        background: var(--pm-teal);
        border-color: var(--pm-teal);
        color: #fff;
    }

    /* Inline item name edit */
    .package_management .pm-name-wrap {
        display: flex;
        align-items: center;
        gap: 4px;
        min-width: 160px;
    }

    .package_management .pm-name-text {
        font-size: .8rem;
        font-weight: 600;
        color: var(--pm-text);
    }

    .package_management .pm-name-input {
        flex: 1;
        padding: 3px 7px;
        font-size: .78rem;
        border: 1.5px solid var(--pm-teal);
        border-radius: 5px;
        outline: none;
        font-family: var(--pm-font);
    }

    .package_management .pm-name-edit-btn {
        background: none;
        border: none;
        padding: 2px;
        cursor: pointer;
        color: var(--pm-text-3);
        font-size: .72rem;
        flex-shrink: 0;
        transition: color .12s;
    }

    .package_management .pm-name-edit-btn:hover {
        color: var(--pm-teal);
    }

    .package_management .pm-item-sku {
        font-size: .68rem;
        color: var(--pm-text-3);
        margin-top: 2px;
    }

    /* Table inputs */
    .package_management .pm-qty-input {
        width: 58px;
        padding: 5px;
        text-align: center;
        font-family: var(--pm-font);
        font-size: .78rem;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        background: #fff;
        color: var(--pm-text);
        transition: border-color .12s;
    }

    .package_management .pm-qty-input:focus {
        outline: none;
        border-color: var(--pm-teal);
        box-shadow: 0 0 0 2px rgba(13, 148, 136, .1);
    }

    .package_management .pm-price-cell {
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        overflow: hidden;
        background: #fff;
        transition: border-color .12s;
        width: 105px;
    }

    .package_management .pm-price-cell:focus-within {
        border-color: var(--pm-teal);
        box-shadow: 0 0 0 2px rgba(13, 148, 136, .1);
    }

    .package_management .pm-price-cell__sym {
        padding: 4px 6px;
        background: var(--pm-muted);
        font-size: .7rem;
        color: var(--pm-text-3);
        border-right: 1px solid var(--pm-border);
        display: flex;
        align-items: center;
    }

    .package_management .pm-price-cell input {
        width: 0;
        flex: 1;
        border: none;
        outline: none;
        padding: 4px 5px;
        font-size: .76rem;
        font-family: var(--pm-font);
        color: var(--pm-text);
        background: transparent;
        text-align: right;
        min-width: 0;
    }

    .package_management .pm-variant-sel {
        width: 120px;
        padding: 4px 6px;
        font-family: var(--pm-font);
        font-size: .74rem;
        border: 1.5px solid var(--pm-border);
        border-radius: 5px;
        color: var(--pm-text);
        background: #fff;
        margin-bottom: 4px;
        display: block;
        transition: border-color .12s;
        -webkit-appearance: none;
        appearance: none;
    }

    .package_management .pm-variant-sel:last-child {
        margin-bottom: 0;
    }

    .package_management .pm-variant-sel:focus {
        outline: none;
        border-color: var(--pm-teal);
    }

    .package_management .pm-subtotal {
        font-size: .8rem;
        font-weight: 600;
        color: var(--pm-text);
        text-align: right;
        white-space: nowrap;
    }

    .package_management .pm-items-empty {
        padding: 48px 20px;
        text-align: center;
        color: var(--pm-text-3);
    }

    .package_management .pm-items-empty i {
        font-size: 2rem;
        color: var(--pm-teal-200);
        display: block;
        margin-bottom: 10px;
    }

    .package_management .pm-items-empty p {
        font-size: .8rem;
        margin: 0;
    }

    /* ============================================================
       PACKAGE INFO TAB
       ============================================================ */
    .package_management .pm-info-grid {
        display: grid;
        grid-template-columns: 1fr 280px;
        gap: 20px;
        align-items: start;
    }

    .package_management .pm-publish-box {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        overflow: hidden;
        background: var(--pm-card);
        position: sticky;
        top: 16px;
    }

    .package_management .pm-publish-box__head {
        padding: 11px 14px;
        background: var(--pm-muted);
        border-bottom: 1px solid var(--pm-border);
        font-size: .72rem;
        font-weight: 700;
        color: var(--pm-text-2);
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .package_management .pm-publish-box__body {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .package_management .pm-publish-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .package_management .pm-autosave {
        background: var(--pm-muted);
        border-radius: var(--pm-radius);
        padding: 12px;
    }

    .package_management .pm-autosave h6 {
        font-size: .72rem;
        font-weight: 700;
        color: var(--pm-text-2);
        margin: 0 0 3px;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .package_management .pm-autosave p {
        font-size: .7rem;
        color: var(--pm-text-3);
        margin: 0 0 8px;
        line-height: 1.5;
    }

    .package_management .pm-autosave-foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .package_management .pm-autosave-time {
        font-size: .68rem;
        color: var(--pm-text-3);
        background: var(--pm-card);
        border: 1px solid var(--pm-border);
        border-radius: 4px;
        padding: 2px 7px;
        white-space: nowrap;
    }

    .package_management .pm-highlight-item {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 7px;
    }

    .package_management .pm-highlight-item i {
        color: #22c55e;
        font-size: .82rem;
        flex-shrink: 0;
    }

    .package_management .pm-highlight-del {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: 1.5px solid #fca5a5;
        border-radius: 6px;
        color: #ef4444;
        cursor: pointer;
        font-size: .7rem;
        transition: all .12s;
    }

    .package_management .pm-highlight-del:hover {
        background: #fef2f2;
        border-color: #f87171;
    }

    /* Switch */
    .package_management .pm-switch-wrap {
        display: flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
    }

    .package_management .pm-switch-wrap input[type=checkbox] {
        width: 30px;
        height: 16px;
        accent-color: var(--pm-teal);
        cursor: pointer;
    }

    .package_management .pm-switch-label {
        font-size: .76rem;
        color: var(--pm-text-2);
    }

    /* ============================================================
       SEO TAB
       ============================================================ */
    .package_management .pm-seo-grid {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
        align-items: start;
    }

    .package_management .pm-meta-img {
        border: 2px dashed var(--pm-border);
        border-radius: var(--pm-radius-lg);
        background: var(--pm-muted);
        min-height: 140px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .15s;
        overflow: hidden;
        position: relative;
    }

    .package_management .pm-meta-img:hover {
        border-color: var(--pm-teal);
        background: var(--pm-teal-50);
    }

    .package_management .pm-meta-img.has-image {
        border-style: solid;
        border-color: var(--pm-border);
    }

    .package_management .pm-meta-img img {
        width: 100%;
        height: 140px;
        object-fit: cover;
        display: block;
    }

    .package_management .pm-meta-img__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 7px;
        padding: 20px;
        text-align: center;
    }

    .package_management .pm-meta-img__empty i {
        font-size: 1.4rem;
        color: var(--pm-text-3);
    }

    .package_management .pm-meta-img__empty p {
        font-size: .73rem;
        color: var(--pm-text-3);
        margin: 0;
        line-height: 1.5;
    }

    /* SERP Preview */
    .package_management .pm-serp {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        background: #fff;
        padding: 14px;
        margin-top: 12px;
    }

    .package_management .pm-serp__label {
        font-size: .65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: var(--pm-text-3);
        margin-bottom: 10px;
    }

    .package_management .pm-serp__url {
        font-size: .72rem;
        color: #1a7f37;
        margin-bottom: 2px;
    }

    .package_management .pm-serp__title {
        font-size: .88rem;
        color: #1a0dab;
        font-weight: 500;
        text-decoration: underline;
        cursor: default;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .package_management .pm-serp__desc {
        font-size: .76rem;
        color: #4d5156;
        line-height: 1.5;
    }

    /* Social preview */
    .package_management .pm-og {
        border: 1.5px solid var(--pm-border);
        border-radius: var(--pm-radius);
        overflow: hidden;
        background: #fff;
        margin-top: 10px;
    }

    .package_management .pm-og__img {
        height: 80px;
        background: var(--pm-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .package_management .pm-og__img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .package_management .pm-og__img i {
        font-size: 1.4rem;
        color: var(--pm-text-3);
    }

    .package_management .pm-og__body {
        padding: 8px 12px;
        border-top: 1px solid var(--pm-border);
    }

    .package_management .pm-og__site {
        font-size: .62rem;
        text-transform: uppercase;
        color: var(--pm-text-3);
        margin-bottom: 2px;
    }

    .package_management .pm-og__title {
        font-size: .76rem;
        font-weight: 600;
        color: var(--pm-text);
        margin-bottom: 2px;
    }

    .package_management .pm-og__desc {
        font-size: .7rem;
        color: var(--pm-text-2);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* ============================================================
       ACTION BAR
       ============================================================ */
    .package_management .pm-action-bar {
        background: var(--pm-card);
        border: 1px solid var(--pm-border);
        border-radius: var(--pm-radius-lg);
        box-shadow: var(--pm-shadow-md);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .package_management .pm-action-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .package_management .pm-action-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
    }

    .package_management .pm-totals-line {
        font-size: .74rem;
        color: var(--pm-text-3);
        font-variant-numeric: tabular-nums;
    }

    .package_management .pm-totals-line .sep {
        margin: 0 5px;
        color: var(--pm-border-2);
    }

    /* ============================================================
       RESPONSIVE — sm < 768
       ============================================================ */
    @media (max-width: 767px) {
        .package_management .pm-tab-btn span:not(.pm-tab-badge) {
            display: none;
        }

        .package_management .pm-tab-btn {
            padding: 12px 8px;
        }

        .package_management .pm-tab-btn i {
            font-size: 1.05rem;
        }

        .package_management .pm-card-body {
            padding: 14px;
        }

        .package_management .pm-card-header {
            padding: 12px 14px;
        }

        .package_management .pm-overview-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .package_management .pm-pricing-row {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .package_management .pm-grid-2 {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .package_management .pm-grid-3 {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .package_management .pm-catalog-grid {
            grid-template-columns: 1fr;
        }

        .package_management .pm-search-panel {
            position: static;
        }

        .package_management .pm-catalog-list {
            max-height: 240px;
        }

        .package_management .pm-items-scroll {
            max-height: 360px;
        }

        .package_management .pm-info-grid {
            grid-template-columns: 1fr;
        }

        .package_management .pm-publish-box {
            position: static;
        }

        .package_management .pm-publish-row {
            grid-template-columns: 1fr;
        }

        .package_management .pm-seo-grid {
            grid-template-columns: 1fr;
        }

        .package_management .pm-action-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .package_management .pm-action-right {
            align-items: stretch;
        }

        .package_management .pm-btn--lg {
            width: 100%;
        }

        .package_management .pm-price-card__value {
            font-size: 1.1rem;
        }

        .package_management .pm-draft-banner {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* ============================================================
       RESPONSIVE — md: 768–991
       ============================================================ */
    @media (min-width: 768px) and (max-width: 991px) {
        .package_management .pm-tab-btn span:not(.pm-tab-badge) {
            font-size: .72rem;
        }

        .package_management .pm-overview-grid {
            grid-template-columns: 1fr 250px;
        }

        .package_management .pm-catalog-grid {
            grid-template-columns: 240px 1fr;
        }

        .package_management .pm-info-grid {
            grid-template-columns: 1fr 240px;
        }

        .package_management .pm-seo-grid {
            grid-template-columns: 1fr 240px;
        }

        .package_management .pm-grid-3 {
            grid-template-columns: 1fr 1fr;
        }

        .package_management .pm-catalog-list {
            max-height: 340px;
        }
    }

    /* ============================================================
       RESPONSIVE — lg: 992–1199
       ============================================================ */
    @media (min-width: 992px) and (max-width: 1199px) {
        .package_management .pm-catalog-grid {
            grid-template-columns: 270px 1fr;
        }

        .package_management .pm-overview-grid {
            grid-template-columns: 1fr 280px;
        }

        .package_management .pm-info-grid {
            grid-template-columns: 1fr 260px;
        }

        .package_management .pm-seo-grid {
            grid-template-columns: 1fr 270px;
        }

        .package_management .pm-catalog-list {
            max-height: 400px;
        }
    }

    /* ============================================================
       RESPONSIVE — xl: >= 1200
       ============================================================ */
    @media (min-width: 1200px) {
        .package_management .pm-card-body {
            padding: 24px;
        }

        .package_management .pm-catalog-grid {
            grid-template-columns: 320px 1fr;
        }

        .package_management .pm-catalog-list {
            max-height: 500px;
        }

        .package_management .pm-overview-grid {
            grid-template-columns: 1fr 340px;
        }

        .package_management .pm-info-grid {
            grid-template-columns: 1fr 290px;
        }

        .package_management .pm-seo-grid {
            grid-template-columns: 1fr 320px;
        }
    }
</style>
