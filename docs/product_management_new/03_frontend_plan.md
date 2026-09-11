# 03 Frontend Plan

Last updated: 2026-06-07

## Goal

Build the product builder as a Vue 3 and Pinia app, following the POS v3 style already used in the project.

## Blade Shell

File:

```text
resources/views/backend/product_management/v3/form.blade.php
```

The Blade should:

```text
extend backend.master
inject window.PRODUCT_V3_CONFIG
load Vue 3
load Pinia
load required third-party libraries
load Product v3 JS files
load Product v3 CSS
render only a root mount element
```

Mount element:

```html
<div id="product-v3-app" class="product-v3-page"></div>
```

Do not render large form markup in Blade.

## JavaScript File Tree

```text
public/assets/js/product-management/v3/
  app.js
  bootstrap.js

  stores/
    configStore.js
    uiStore.js
    productSessionStore.js
    catalogStore.js
    basicInfoStore.js
    imageStore.js
    contentStore.js
    pricingStore.js
    variantStore.js
    filterStore.js
    attributeStore.js
    shippingStore.js
    relatedStore.js
    notificationStore.js
    seoStore.js
    faqStore.js
    draftStore.js

  components/
    layout/ProductV3App.js
    layout/ProductV3Header.js
    layout/ProductV3TabNav.js
    layout/ProductV3ActionBar.js
    layout/ProductV3SaveStatus.js

    sections/BasicInfoSection.js
    sections/ImageSection.js
    sections/ContentSection.js
    sections/PricingSection.js
    sections/VariantSection.js
    sections/FilterAttributeSection.js
    sections/AttributeSection.js
    sections/ShippingSection.js
    sections/RelatedSection.js
    sections/NotificationSection.js
    sections/SeoSection.js
    sections/FaqSection.js

    fields/Select2Field.js
    fields/SlugField.js
    fields/MoneyInput.js
    fields/RichTextEditor.js
    fields/MediaPickerField.js
    fields/ToggleField.js
    fields/DateTimeField.js

  utils/
    api.js
    cloneDeep.js
    debounce.js
    slug.js
    money.js
    validation.js
    tabGuards.js
    responseErrors.js
    variantMatrix.js
    productHydrator.js
    productSerializer.js
    draftStorage.js
    toast.js
```

## Namespace

Use:

```js
window.ProductV3
```

Do not pollute the global scope with many loose functions.

## Config Object

Create mode:

```js
window.PRODUCT_V3_CONFIG = {
  configVersion: 1,
  mode: 'create',
  productId: null,
  routes: {
    create: '/product-management/v3/create',
    editTemplate: '/product-management/v3/products/__ID__/edit',
    bootstrap: '/product-management/v3/bootstrap',
    showTemplate: '/product-management/v3/products/__ID__',
    basicInfoStore: '/product-management/v3/products/basic-info',
    basicInfoUpdateTemplate: '/product-management/v3/products/__ID__/basic-info',
    imagesUpdateTemplate: '/product-management/v3/products/__ID__/images',
    contentUpdateTemplate: '/product-management/v3/products/__ID__/content',
    pricingUpdateTemplate: '/product-management/v3/products/__ID__/pricing',
    variantsUpdateTemplate: '/product-management/v3/products/__ID__/variants',
    filtersUpdateTemplate: '/product-management/v3/products/__ID__/filters',
    attributesUpdateTemplate: '/product-management/v3/products/__ID__/attributes',
    shippingUpdateTemplate: '/product-management/v3/products/__ID__/shipping',
    relatedUpdateTemplate: '/product-management/v3/products/__ID__/related',
    notificationUpdateTemplate: '/product-management/v3/products/__ID__/notification',
    seoUpdateTemplate: '/product-management/v3/products/__ID__/seo',
    faqUpdateTemplate: '/product-management/v3/products/__ID__/faq',
    slugCheck: '/product-management/v3/slug/check',
    productSearch: '/product-management/v3/options/products'
  }
}
```

Edit mode:

```js
window.PRODUCT_V3_CONFIG = {
  mode: 'edit',
  productId: 123,
  routes: {}
}
```

## Store Responsibilities

### configStore

Owns:

```text
routes
mode
initial productId
feature flags
```

### productSessionStore

Owns:

```text
productId
mode
setup status
completed tabs
active tab
tab lock state
global saving state
```

### catalogStore

Owns:

```text
categories
subcategories
child categories
brands
models
units
flags
websites
variant groups
filter groups
dependent option loading
```

### one store per tab

Each tab store owns only that tab's state, validation, dirty flag, and save action.

Example:

```text
pricingStore owns base pricing, unit pricing, special offer.
imageStore owns product image, gallery, SEO image only if included, and media picker selection.
variantStore owns variant group selections and generated combinations.
```

## Tab Lock UX

Create mode with no product ID:

```text
Basic Info tab enabled.
All other tabs visually disabled.
Disabled tab click shows a small warning:
Please create the product from Basic Info first.
```

Edit mode or create mode after Basic Info save:

```text
All tabs enabled.
```

## Save Behavior

Each section has its own Save button.

Optional global action bar:

```text
Save current tab
Save and next
Validate current tab
Back to list
```

Do not create a global Save All button in the first v3 build.

## Basic Info Save Transition

When create mode Basic Info save succeeds:

```text
productSessionStore.productId = response.product_id
productSessionStore.mode = 'edit'
unlock all tabs
replace browser URL with edit URL if available
mark basic_info as saved
optionally move to Images tab
```

## Error Handling

Each store should keep its own errors:

```js
errors: {}
isSaving: false
lastSavedAt: null
dirty: false
```

Shared error helper converts Laravel 422 to inline fields.

## Draft Storage

Draft storage should be per product and per tab.

Create mode before product ID:

```text
product_v3_create_basic_info_draft
```

After product ID:

```text
product_v3_{productId}_{tab}_draft
```

Avoid storing huge binary image data. Store media IDs and URLs only.

