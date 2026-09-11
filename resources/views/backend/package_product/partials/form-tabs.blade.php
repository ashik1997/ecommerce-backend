{{--
    form-tabs.blade.php
    Shared between create.blade.php and edit.blade.php
    All structural UX rebuilt with .package_management .pm-* classes
--}}

<div class="package_management">

    {{-- ── OVERVIEW TAB ─────────────────────────────────────────────── --}}
    <div class="pm-tab-card" v-show="isActiveTab('overview')">
        <div class="pm-card-header">
            <div>
                <h5 class="pm-card-header__title">Package Overview</h5>
                <small class="pm-card-header__sub">Define the hero experience customers will see on the landing
                    page.</small>
            </div>
            <div class="pm-card-header__meta">
                <span class="pm-badge pm-badge--teal" v-if="items.length">
                    <i class="fas fa-cubes"></i> @{{ items.length }} items
                </span>
                <span class="pm-badge pm-badge--green" v-if="itemsTotals.savingsAmount > 0">
                    <i class="fas fa-percentage"></i> Save ৳@{{ itemsTotals.savingsAmount.toFixed(2) }}
                </span>
            </div>
        </div>

        <div class="pm-card-body">
            <div class="pm-overview-grid">

                {{-- Left: Form fields --}}
                <div>
                    @if (is_multiple_domain())
                        <div class="pm-field">
                            <label class="pm-label">Select Website <span class="req">*</span></label>
                            <select class="pm-select" v-model="overview.product_website_id">
                                <option value="">Choose a website…</option>
                                <option v-for="website in masterData.websites" :key="website.id"
                                    :value="website.id">
                                    @{{ website.name }}
                                </option>
                            </select>
                        </div>
                    @endif

                    <div class="pm-field">
                        <label class="pm-label">Package Title <span class="req">*</span></label>
                        <input type="text" class="pm-input pm-input--lg"
                            placeholder="E.g., Ultimate Eid Gadget Bundle" v-model.trim="overview.title"
                            @blur="generateSlugIfEmpty">
                    </div>

                    <div class="pm-grid-2">
                        <div class="pm-field">
                            <label class="pm-label">Internal Package Code</label>
                            <input type="text" class="pm-input" placeholder="Auto-generated if blank"
                                v-model.trim="overview.package_code">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label">Public Slug</label>
                            <div class="pm-input-group">
                                <span class="pm-input-addon"
                                    style="font-size:.7rem; max-width:120px; overflow:hidden; text-overflow:ellipsis;">{{ url('/packages') }}/</span>
                                <input type="text" placeholder="url-slug" v-model.trim="overview.slug">
                            </div>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Tagline</label>
                        <input type="text" class="pm-input" placeholder="Quick value statement"
                            v-model.trim="overview.tagline">
                    </div>

                    <div class="pm-divider"></div>

                    <div class="pm-grid-2">
                        <div class="pm-field">
                            <label class="pm-label">Hero Headline</label>
                            <input type="text" class="pm-input" placeholder="Main headline customers see"
                                v-model.trim="overview.hero_headline">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label">Hero Subheadline</label>
                            <input type="text" class="pm-input" placeholder="Support statement"
                                v-model.trim="overview.hero_subheadline">
                        </div>
                    </div>

                    <div class="pm-grid-2">
                        <div class="pm-field">
                            <label class="pm-label">CTA Label</label>
                            <input type="text" class="pm-input" placeholder="Shop The Bundle"
                                v-model.trim="overview.hero_cta_label">
                        </div>
                        <div class="pm-field">
                            <label class="pm-label">CTA Link</label>
                            <input type="text" class="pm-input" placeholder="/checkout?bundle=pkg-code"
                                v-model.trim="overview.hero_cta_link">
                        </div>
                    </div>

                    {{-- Pricing Summary --}}
                    <div class="pm-pricing-row">
                        <div class="pm-price-card pm-price-card--bundle">
                            <div class="pm-price-card__label">
                                Bundle Price <i class="fas fa-tag"></i>
                            </div>
                            <div class="pm-price-card__value">৳@{{ (pricing.package_price || 0).toFixed(2) }}</div>
                            <div class="pm-price-card__sub">Customers pay this amount</div>
                        </div>
                        <div class="pm-price-card pm-price-card--compare">
                            <div class="pm-price-card__label">
                                Compare Value <i class="fas fa-balance-scale"></i>
                            </div>
                            <div class="pm-price-card__value">
                                ৳@{{ (pricing.compare_at_price || itemsTotals.compareTotal).toFixed(2) }}
                            </div>
                            <div class="pm-price-card__sub">If purchased separately</div>
                        </div>
                        <div class="pm-price-card pm-price-card--savings">
                            <div class="pm-price-card__label">
                                Total Savings <i class="fas fa-percentage"></i>
                            </div>
                            <div class="pm-price-card__value">৳@{{ itemsTotals.savingsAmount.toFixed(2) }}</div>
                            <div class="pm-price-card__sub">@{{ itemsTotals.savingsPercent.toFixed(1) }}% off</div>
                        </div>
                    </div>
                </div>

                {{-- Right: Media --}}
                <div>
                    <div class="pm-field">
                        <label class="pm-label">Hero Image <span class="req">*</span></label>
                        <div class="pm-hero-upload" :class="{ 'has-image': media.hero.preview }"
                            @click="triggerHeroImage">
                            <template v-if="media.hero.preview">
                                <img :src="media.hero.preview" alt="Hero preview">
                                <button type="button" class="pm-img-remove" @click.stop="removeHeroImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </template>
                            <template v-else>
                                <div class="pm-hero-upload__empty">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload hero image<br><span style="font-size:.68rem;">800×800px
                                            recommended, max 5MB</span></p>
                                </div>
                            </template>
                        </div>
                        <input type="file" ref="heroImageInput" class="d-none" accept="image/*"
                            @change="handleHeroImageUpload">
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Gallery Images <span
                                style="font-weight:400; color:var(--pm-text-3);">(optional)</span></label>
                        <div class="pm-gallery-grid">
                            <div class="pm-gallery-slot" v-for="slot in 4" :key="'g-' + slot"
                                @click="triggerGallerySlot(slot - 1)">
                                <template v-if="media.gallery[slot - 1] && media.gallery[slot - 1].preview">
                                    <img :src="media.gallery[slot - 1].preview">
                                    <button type="button" class="pm-img-remove"
                                        style="width:20px;height:20px;font-size:.55rem;"
                                        @click.stop="removeGallerySlot(slot - 1)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </template>
                                <template v-else>
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Gallery @{{ slot }}</span>
                                </template>
                                <input type="file" class="d-none" :ref="'galleryInput' + (slot - 1)"
                                    accept="image/*" @change="handleGalleryUpload($event, slot - 1)">
                            </div>
                        </div>
                    </div>

                    <div class="pm-tip">
                        <i class="fas fa-lightbulb"></i>
                        Hero image is reused for metadata and social share previews by default.
                    </div>
                </div>

            </div>{{-- .pm-overview-grid --}}
        </div>
    </div>


    {{-- ── CATALOG TAB ──────────────────────────────────────────────── --}}
    <div class="pm-tab-card" v-show="isActiveTab('catalog')">
        <div class="pm-card-header">
            <div>
                <h5 class="pm-card-header__title">Select Products</h5>
                <small class="pm-card-header__sub">Search catalog, configure variants, set quantities and pricing
                    overrides.</small>
            </div>
            <div class="pm-card-header__meta">
                <span class="pm-badge pm-badge--gray"><i class="fas fa-cubes"></i> @{{ items.length }}
                    selected</span>
                <span class="pm-badge pm-badge--teal"><i class="fas fa-calculator"></i> ৳@{{ itemsTotals.itemsTotal.toFixed(2) }}</span>
            </div>
        </div>

        <div class="pm-card-body">
            <div class="pm-catalog-grid">

                {{-- Search Panel --}}
                <div class="pm-search-panel">
                    <div class="pm-search-header">
                        <div class="pm-search-bar">
                            <div class="pm-search-input">
                                <i class="fas fa-search"></i>
                                <input type="search" placeholder="Search by name, SKU, code"
                                    v-model="catalog.searchTerm" @input="debouncedSearch">
                            </div>
                            <button type="button" class="pm-icon-btn" @click="loadFeatured" title="Load featured">
                                <i class="fas fa-bolt"></i>
                            </button>
                        </div>
                        <div class="pm-search-hint">
                            <i class="fas fa-info-circle"></i> Click a product to add it
                        </div>
                    </div>

                    <div class="pm-catalog-list">
                        <div class="pm-list-state" v-if="catalog.loading">
                            <div class="spinner-border" role="status"></div>
                            <span>Fetching catalog…</span>
                        </div>
                        <div class="pm-list-state" v-else-if="catalog.products.length === 0">
                            <i class="fas fa-box-open"></i>
                            <span>No products found</span>
                        </div>
                        <div v-else>
                            <div class="pm-product-row" v-for="product in catalog.products" :key="'cp-' + product.id"
                                @click="selectCatalogProduct(product)">
                                <img :src="product.image_url" class="pm-product-row__img" alt="">
                                <div class="pm-product-row__body">
                                    <div class="pm-product-row__name">@{{ product.name }}</div>
                                    <div class="pm-product-row__meta">
                                        <span class="pm-product-row__price">৳@{{ product.effective_price }}</span>
                                        <span class="pm-product-row__compare"
                                            v-if="product.price && product.price > product.discount_price">
                                            ৳@{{ product.price }}
                                        </span>
                                        <span class="pm-product-row__type">@{{ product.variant_type }}</span>
                                        <span class="pm-product-row__stock">Stock: @{{ product.total_stock }}</span>
                                    </div>
                                </div>
                                <i class="fas fa-plus-circle pm-product-row__icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Selected Items --}}
                <div class="pm-items-panel">
                    <div class="pm-items-head">
                        <span class="pm-items-title">Selected Items</span>
                    </div>

                    <div class="pm-items-scroll">
                        {{-- Empty state --}}
                        <div class="pm-items-empty" v-if="items.length === 0">
                            <i class="fas fa-layer-group"></i>
                            <p>No products selected yet.<br>Search and click a product to add it.</p>
                        </div>

                        {{-- Table --}}
                        <table class="pm-items-table" v-if="items.length > 0">
                            <thead>
                                <tr>
                                    <th style="width:50px;"></th>
                                    <th>Product</th>
                                    <th style="width:130px;">Variant</th>
                                    <th class="th-center" style="width:70px;">Qty</th>
                                    <th style="width:110px;">Unit Price</th>
                                    <th style="width:110px;">Compare</th>
                                    <th class="th-right" style="width:90px;">Subtotal</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in items" :key="item.key">
                                    {{-- Thumb --}}
                                    <td>
                                        <div class="pm-thumb-wrap">
                                            <img :src="item.image_url" alt="">
                                            <button type="button" class="pm-thumb-cam"
                                                @click="triggerItemImageInput(index)">
                                                <i class="fas fa-camera"></i>
                                            </button>
                                            <input type="file" class="d-none" accept="image/*"
                                                :ref="'itemImageInput' + index"
                                                @change="handleItemImageUpload(item, index, $event)">
                                        </div>
                                    </td>

                                    {{-- Name --}}
                                    <td>
                                        <div class="pm-name-wrap">
                                            <span class="pm-name-text" v-if="!item._editingTitle">
                                                @{{ item.title || item.product_name }}
                                            </span>
                                            <input class="pm-name-input" v-else type="text" v-model="item.title"
                                                @blur="finishEditingItemTitle(item)"
                                                @keyup.enter="finishEditingItemTitle(item)">
                                            <button type="button" class="pm-name-edit-btn"
                                                @click="startEditingItemTitle(item)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        </div>
                                        <div class="pm-item-sku">SKU: @{{ item.sku || '—' }}</div>
                                    </td>

                                    {{-- Variant --}}
                                    <td>
                                        <select class="pm-variant-sel" v-model="item.color_id"
                                            @change="syncLegacyVariant(item)">
                                            <option value="">Default color</option>
                                            <option v-for="color in item.variant_options.colors"
                                                :key="'c-' + color" :value="color">@{{ color }}
                                            </option>
                                        </select>
                                        <select class="pm-variant-sel" v-model="item.size_id"
                                            @change="syncLegacyVariant(item)">
                                            <option value="">Default size</option>
                                            <option v-for="size in item.variant_options.sizes" :key="'s-' + size"
                                                :value="size">@{{ size }}</option>
                                        </select>
                                    </td>

                                    {{-- Qty --}}
                                    <td>
                                        <input type="number" class="pm-qty-input" min="1"
                                            v-model.number="item.quantity" @change="enforceItemQuantity(item)">
                                    </td>

                                    {{-- Unit Price --}}
                                    <td>
                                        <div class="pm-price-cell">
                                            <span class="pm-price-cell__sym">৳</span>
                                            <input type="number" min="0" step="0.01"
                                                v-model.number="item.unit_price" @change="recalculatePricing">
                                        </div>
                                    </td>

                                    {{-- Compare --}}
                                    <td>
                                        <div class="pm-price-cell">
                                            <span class="pm-price-cell__sym">৳</span>
                                            <input type="number" min="0" step="0.01"
                                                v-model.number="item.compare_at_price" @change="recalculatePricing">
                                        </div>
                                    </td>

                                    {{-- Subtotal --}}
                                    <td>
                                        <div class="pm-subtotal">
                                            ৳@{{ (item.unit_price * item.quantity).toFixed(2) }}
                                        </div>
                                    </td>

                                    {{-- Remove --}}
                                    <td>
                                        <button type="button" class="pm-btn pm-btn--danger-solid pm-btn--icon"
                                            @click="removeItem(index)">
                                            <i class="fas fa-trash-alt" style="font-size:.7rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="items.length">
                                <tr>
                                    <td colspan="6"
                                        style="text-align:right; font-size:.78rem; color:var(--pm-teal-dark); padding-right:10px;">
                                        Bundle Total
                                    </td>
                                    <td style="font-size:.88rem; font-weight:700; color:var(--pm-teal-dark);">
                                        ৳@{{ itemsTotals.itemsTotal.toFixed(2) }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="pm-tip" style="margin:12px; margin-top:0;" v-if="items.length">
                        <i class="fas fa-info-circle"></i>
                        Per-item pricing can be overridden to craft unique bundle value. Leave blank to use product
                        pricing.
                    </div>
                </div>

            </div>{{-- .pm-catalog-grid --}}
        </div>
    </div>


    {{-- ── DETAILS TAB ──────────────────────────────────────────────── --}}
    <div class="pm-tab-card" v-show="isActiveTab('details')">
        <div class="pm-card-header">
            <div>
                <h5 class="pm-card-header__title">Package Information &amp; Landing Content</h5>
                <small class="pm-card-header__sub">Control merchandising details, highlights, and publishing
                    state.</small>
            </div>
        </div>

        <div class="pm-card-body">
            <div class="pm-info-grid">

                {{-- Main content --}}
                <div>
                    <div class="pm-field">
                        <label class="pm-label">Short Description</label>
                        <textarea class="pm-textarea" rows="2" placeholder="Appears in cards and previews"
                            v-model.trim="info.short_description"></textarea>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Landing Page Description</label>
                        <textarea class="pm-textarea" rows="5" placeholder="Hero narrative, usage scenarios, etc."
                            v-model.trim="info.description"></textarea>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Key Highlights</label>
                        <div>
                            <div class="pm-highlight-item" v-for="(highlight, index) in info.highlights"
                                :key="'hl-' + index">
                                <i class="fas fa-check-circle"></i>
                                <input type="text" class="pm-input" v-model.trim="info.highlights[index]"
                                    placeholder="Value proposition…">
                                <button type="button" class="pm-highlight-del" @click="removeHighlight(index)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button" class="pm-btn pm-btn--teal-ghost pm-btn--sm" @click="addHighlight"
                                :disabled="info.highlights.length >= 6">
                                <i class="fas fa-plus"></i> Add Highlight
                            </button>
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Custom Landing Blocks <span
                                style="font-weight:400; color:var(--pm-text-3);">(optional)</span></label>
                        <textarea class="pm-textarea" rows="4" placeholder="Markdown or JSON for custom sections"
                            v-model="info.content_blocks_raw"></textarea>
                        <div style="font-size:.72rem; color:var(--pm-text-3); margin-top:4px;">
                            Optional advanced layout JSON or markdown for developer customization.
                        </div>
                    </div>
                </div>

                {{-- Publishing sidebar --}}
                <div class="pm-publish-box">
                    <div class="pm-publish-box__head">Publishing</div>
                    <div class="pm-publish-box__body">

                        <div class="pm-publish-row">
                            <div class="pm-field" style="margin:0;">
                                <label class="pm-label">Status <span class="req">*</span></label>
                                <select class="pm-select" v-model="info.status">
                                    <option v-for="status in masterData.statuses" :key="'st-' + status.value"
                                        :value="status.value">@{{ status.label }}</option>
                                </select>
                            </div>
                            <div class="pm-field" style="margin:0;">
                                <label class="pm-label">Visibility</label>
                                <select class="pm-select" v-model="info.visibility">
                                    <option v-for="option in masterData.visibility" :key="'vi-' + option.value"
                                        :value="option.value">@{{ option.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="pm-field" style="margin:0;">
                            <label class="pm-label">Publish At</label>
                            <input type="datetime-local" class="pm-input" v-model="info.publish_at">
                            <div style="font-size:.7rem; color:var(--pm-text-3); margin-top:3px;">Leave empty to
                                publish immediately.</div>
                        </div>

                        <div class="pm-field" style="margin:0;">
                            <label class="pm-label">Display Category</label>
                            <select class="pm-select" v-model="info.category_id">
                                <option value="">Unassigned</option>
                                <option v-for="category in masterData.categories" :key="'cat-' + category.id"
                                    :value="category.id">@{{ category.name }}</option>
                            </select>
                        </div>

                        <div class="pm-autosave">
                            <h6>Auto-Save</h6>
                            <p>Drafts are auto-saved locally every 20 seconds and restored on revisit.</p>
                            <div class="pm-autosave-foot">
                                <span class="pm-autosave-time" v-if="autoSave.lastSavedTime">
                                    <i class="fas fa-clock" style="margin-right:4px;"></i>@{{ autoSave.lastSavedTime | formatTime }}
                                </span>
                                <label class="pm-switch-wrap">
                                    <input type="checkbox" v-model="autoSave.enabled">
                                    <span class="pm-switch-label">Enable</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

            </div>{{-- .pm-info-grid --}}
        </div>
    </div>


    {{-- ── SEO TAB ──────────────────────────────────────────────────── --}}
    <div class="pm-tab-card" v-show="isActiveTab('seo')">
        <div class="pm-card-header">
            <div>
                <h5 class="pm-card-header__title">SEO &amp; Meta Information</h5>
                <small class="pm-card-header__sub">Optimize packaging for organic search and social sharing.</small>
            </div>
        </div>

        <div class="pm-card-body">
            <div class="pm-seo-grid">

                {{-- Meta fields --}}
                <div>
                    <div class="pm-field">
                        <label class="pm-label">Meta Title</label>
                        <input type="text" class="pm-input" maxlength="255"
                            placeholder="Title for SERP & social sharing" v-model.trim="seo.meta_title">
                        <div style="font-size:.7rem; color:var(--pm-text-3); margin-top:3px; text-align:right;">
                            @{{ (seo.meta_title || '').length }}/255
                        </div>
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Meta Keywords</label>
                        <input type="text" class="pm-input" placeholder="Comma-separated keywords"
                            v-model.trim="seo.meta_keywords">
                    </div>

                    <div class="pm-field">
                        <label class="pm-label">Meta Description</label>
                        <textarea class="pm-textarea" rows="4" maxlength="300" placeholder="150–160 characters ideal"
                            v-model.trim="seo.meta_description"></textarea>
                        <div style="font-size:.7rem; color:var(--pm-text-3); margin-top:3px; text-align:right;">
                            @{{ (seo.meta_description || '').length }}/300
                        </div>
                    </div>

                    {{-- SERP Preview --}}
                    <div class="pm-serp">
                        <div class="pm-serp__label">Search Result Preview</div>
                        <div class="pm-serp__url">{{ url('/packages') }}/<span>@{{ overview.slug || 'package-slug' }}</span></div>
                        <div class="pm-serp__title">@{{ seo.meta_title || overview.title || 'Package Title' }}</div>
                        <div class="pm-serp__desc">@{{ seo.meta_description || info.short_description || 'Meta description will appear here…' }}</div>
                    </div>
                </div>

                {{-- Meta image + social preview --}}
                <div>
                    <div class="pm-field">
                        <label class="pm-label">Meta / OG Image</label>
                        <div class="pm-meta-img" :class="{ 'has-image': seo.meta_image.preview }"
                            @click="triggerMetaImage">
                            <template v-if="seo.meta_image.preview">
                                <img :src="seo.meta_image.preview" alt="Meta preview">
                                <button type="button" class="pm-img-remove" @click.stop="removeMetaImage">
                                    <i class="fas fa-times"></i>
                                </button>
                            </template>
                            <template v-else>
                                <div class="pm-meta-img__empty">
                                    <i class="fas fa-image"></i>
                                    <p>Optional social preview image<br><span style="font-size:.64rem;">Recommended
                                            1200×630px</span></p>
                                </div>
                            </template>
                        </div>
                        <input type="file" ref="metaImageInput" class="d-none" accept="image/*"
                            @change="handleMetaImageUpload">
                    </div>

                    {{-- OG / Social card preview --}}
                    <div class="pm-og">
                        <div class="pm-og__img">
                            <img v-if="seo.meta_image.preview || media.hero.preview"
                                :src="seo.meta_image.preview || media.hero.preview" alt="">
                            <i class="fas fa-share-alt" v-else></i>
                        </div>
                        <div class="pm-og__body">
                            <div class="pm-og__site">{{ parse_url(url('/'), PHP_URL_HOST) }}</div>
                            <div class="pm-og__title">@{{ seo.meta_title || overview.title || 'Package Title' }}</div>
                            <div class="pm-og__desc">@{{ seo.meta_description || info.short_description || 'Social share description…' }}</div>
                        </div>
                    </div>

                    <div class="pm-tip" style="margin-top:12px;">
                        <i class="fas fa-lightbulb"></i>
                        If no meta image is set, the hero image is used automatically.
                    </div>
                </div>

            </div>{{-- .pm-seo-grid --}}
        </div>
    </div>

</div>{{-- .package_management --}}
