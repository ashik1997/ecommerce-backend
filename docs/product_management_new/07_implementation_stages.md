# 07 Implementation Stages

Last updated: 2026-06-07

## Stage 0: Baseline Guard

Goal:

```text
Confirm existing product management remains untouched.
```

Tasks:

```text
Create route file for v3 or add isolated route group.
Create empty v3 page controller.
Create empty v3 Blade shell.
Do not modify old ProductManagementController.
Do not modify old create/edit Blade except navigation link only if explicitly approved.
```

Verification:

```text
Old /product-management/create still loads.
Old /product-management/edit/{id} still loads.
New /product-management/v3/create loads empty shell.
```

## Stage 1: Frontend Shell

Goal:

```text
Mount Vue 3 ProductV3 app.
```

Tasks:

```text
Add ProductV3 namespace.
Add configStore.
Add productSessionStore.
Add layout components.
Add tab navigation with locked tabs.
Add bootstrap endpoint returning reference data.
```

Verification:

```text
Create mode shows Basic Info enabled and all other tabs locked.
Locked tab click shows warning.
No product save yet.
```

## Stage 2: Basic Info Create and Update

Goal:

```text
Create product row from Basic Info only.
```

Tasks:

```text
StoreBasicInfoRequest.
UpdateBasicInfoRequest.
ProductV3BasicInfoService.
StoreBasicInfoController.
UpdateBasicInfoController.
BasicInfoSection.
SlugField.
catalogStore dependent options.
```

Verification:

```text
POST basic info creates product.
Response returns product_id.
Tabs unlock after success.
URL changes to edit URL.
Edit mode can update basic info.
```

## Stage 3: Global Media Manager Foundation

Goal:

```text
Build reusable modal media picker.
```

Tasks:

```text
Media library endpoint.
Global modal component.
window.MediaManager.open.
Single and multiple selection.
Upload tab using existing /media/upload.
Library tab with search and pagination.
MediaPickerField wrapper.
```

Verification:

```text
Two media picker fields on same page can open the same modal without callback conflict.
Single mode returns one selected file.
Multiple mode returns many selected files.
Clearing field does not delete media.
```

## Stage 4: Images Tab

Goal:

```text
Save product image and gallery independently.
```

Tasks:

```text
UpdateImagesRequest.
ProductV3ImageService.
UpdateImagesController.
ImageSection.
Use MediaPickerField.
Mark selected media permanent.
Sync media usage if enabled.
```

Verification:

```text
Product image saves without touching pricing/content.
Gallery saves without touching variants.
Detach removes image from product only.
```

## Stage 5: Content and SEO Tabs

Goal:

```text
Save descriptions and meta fields independently.
```

Tasks:

```text
ContentSection.
SeoSection.
RichTextEditor wrapper.
UpdateContentController.
UpdateSeoController.
```

Verification:

```text
Content save does not modify SEO.
SEO save does not modify content.
```

## Stage 6: Pricing Tab

Goal:

```text
Save pricing and unit pricing independently.
```

Tasks:

```text
PricingSection.
Unit pricing rows.
Discount calculation helper.
ProductV3PricingService.
UpdatePricingController.
```

Verification:

```text
Pricing save replaces only this product's unit pricing.
Stock is not created or modified from pricing tab.
```

## Stage 7: Variants Tab

Goal:

```text
Save sale/POS/cart variants independently.
```

Tasks:

```text
VariantSection.
variantMatrix utility.
ProductV3VariantService.
Variant image support through MediaPickerField.
Safe inactive/delete behavior for removed variants.
```

Verification:

```text
Variants save without touching product filters.
Variant with stock history is marked inactive when removed.
Variant without stock history can be deleted.
```

## Stage 8: Filters and Attributes Tabs

Goal:

```text
Separate storefront filters from sale variants.
```

Tasks:

```text
FilterAttributeSection.
AttributeSection.
ProductV3FilterService.
ProductV3AttributeService.
```

Verification:

```text
Filter save updates product_filter_attributes and mappings only.
Attribute save updates products.attributes only.
```

## Stage 9: Shipping, Related, Notification, FAQ

Goal:

```text
Complete remaining independent tabs.
```

Tasks:

```text
ShippingSection.
RelatedSection.
NotificationSection.
FaqSection.
Product search select.
Notification image through MediaPickerField.
```

Verification:

```text
Each tab saves only owned fields.
Notification image detach does not delete media.
FAQ filters empty rows.
```

## Stage 10: Completion Service and Publish Readiness

Goal:

```text
Provide product setup progress.
```

Tasks:

```text
ProductV3CompletionService.
Return completed_tabs and missing_tabs from save responses.
Optionally add setup_status migration.
Add publish readiness warnings.
```

Verification:

```text
Product can exist as draft after Basic Info.
UI clearly shows missing required setup areas.
```

## Stage 11: Navigation and Sidebar Cutover

Goal:

```text
Expose v3 only when approved.
```

Tasks:

```text
Add optional sidebar link to v3 create.
Keep old links until business approves cutover.
Possibly add feature flag for v3.
```

Verification:

```text
Old product management remains accessible.
v3 can be tested independently.
```

