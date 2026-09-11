# 04 Tab Contracts

Last updated: 2026-06-07

## Purpose

This document defines which tab owns which fields and endpoint. It prevents accidental cross-tab writes.

## Basic Info

Create endpoint:

```text
POST /product-management/v3/products/basic-info
```

Update endpoint:

```text
PUT /product-management/v3/products/{product}/basic-info
```

Owns:

```text
products.name
products.slug
products.code
products.sku
products.barcode
products.hsn_code
products.product_website_id
products.category_id
products.subcategory_id
products.childcategory_id
products.brand_id
products.model_id
products.unit_id
products.flag_id
products.status
products.availability_status
products.is_demo
products.is_package
products.is_facebook_product_feed
products.is_low_stock_order
products.has_imei
products.contact_number
products.contact_description
```

Create mode minimum required:

```text
name
slug
category_id
```

Business decision pending:

```text
Whether product image is required before product can be published, not before basic row creation.
```

Response after create:

```json
{
  "success": true,
  "product_id": 123,
  "message": "Product created.",
  "edit_url": "/product-management/v3/products/123/edit"
}
```

## Images

Endpoint:

```text
PUT /product-management/v3/products/{product}/images
```

Owns:

```text
products.image
products.multiple_images
media_files permanent status for selected new uploads
media_in_uses rows for product image and gallery when enabled
```

Payload:

```json
{
  "product_image_id": 10,
  "gallery_image_ids": [11, 12, 13]
}
```

Rules:

```text
Field clear detaches image from product.
Field clear does not delete media file.
Selected media IDs must exist.
New uploaded temp media becomes permanent after successful save.
```

## Content

Endpoint:

```text
PUT /product-management/v3/products/{product}/content
```

Owns:

```text
products.short_description
products.description
products.specification
products.warrenty_policy
products.size_chart
products.video_url
products.tags
```

Rules:

```text
Normalize YouTube/Vimeo URL on frontend and validate as URL or accepted embed URL on backend.
Rich text fields are saved independently from SEO and pricing.
```

## Pricing

Endpoint:

```text
PUT /product-management/v3/products/{product}/pricing
```

Owns:

```text
products.price
products.discount_price
products.discount_parcent
products.wholesale_price
products.retail_price
products.mrp_price
products.reward_points
products.low_stock
products.min_order_qty
products.max_order_qty
products.special_offer
products.offer_end_time
product_unit_pricings
```

Payload:

```json
{
  "price": 1000,
  "discount_price": 900,
  "discount_percent": 10,
  "unit_pricing": []
}
```

Rules:

```text
Do not update product stock here.
Replace product_unit_pricings for this product in a transaction.
Price must be greater than zero before publish readiness.
```

## Variants

Endpoint:

```text
PUT /product-management/v3/products/{product}/variants
```

Owns:

```text
products.has_variant
product_variant_combinations
variant image media usage
```

Payload:

```json
{
  "has_variants": true,
  "variant_combinations": [
    {
      "id": null,
      "combination_key": "color:red|size:m",
      "variant_values": {"color": "Red", "size": "M"},
      "price": 1000,
      "discount_price": 900,
      "additional_price": 0,
      "low_stock_alert": 10,
      "sku": "SKU-RED-M",
      "barcode": "123",
      "image_id": 20
    }
  ]
}
```

Rules:

```text
Do not create product_stocks rows.
Do not directly set stock from the form.
New variants start with stock 0.
Existing variant with stock history must not be hard-deleted.
If removed from form and stock/log history exists, mark inactive.
If no history exists, delete is allowed.
```

## Filters

Endpoint:

```text
PUT /product-management/v3/products/{product}/filters
```

Owns:

```text
product_filter_attributes
product_filter_attribute_mappings
```

Payload:

```json
{
  "filter_attributes": {
    "pattern": [1, 2],
    "fit": [5]
  }
}
```

Rules:

```text
Filters are storefront category filters only.
Filters are not sale variants.
Filters do not create variant combinations.
```

## Attributes

Endpoint:

```text
PUT /product-management/v3/products/{product}/attributes
```

Owns:

```text
products.attributes
```

Payload:

```json
{
  "attributes": {
    "material": "Cotton",
    "style": "Casual",
    "dimensions": {}
  }
}
```

## Shipping

Endpoint:

```text
PUT /product-management/v3/products/{product}/shipping
```

Owns:

```text
products.shipping_info
products.tax_info
products.is_free_shipping
```

Payload:

```json
{
  "shipping_info": {
    "is_free_shipping": 1,
    "weight": "1.5",
    "dimension_unit": "kg"
  },
  "tax_info": {
    "tax_class_id": null,
    "tax_percent": 0
  }
}
```

## Related Products

Endpoint:

```text
PUT /product-management/v3/products/{product}/related
```

Owns:

```text
products.related_similar_products
products.related_recommended_products
products.related_addon_products
```

Payload:

```json
{
  "similar": [1, 2],
  "recommended": [3, 4],
  "addons": [
    {"product_id": 5, "is_default": 1}
  ]
}
```

Rules:

```text
Current product cannot be related to itself.
All product IDs must exist.
```

## Notification

Endpoint:

```text
PUT /product-management/v3/products/{product}/notification
```

Owns:

```text
products.notification_title
products.notification_description
products.notification_button_text
products.notification_button_url
products.notification_image_id
products.notification_image_path
products.notification_is_show
```

Payload:

```json
{
  "title": "Offer",
  "description": "Promo copy",
  "button_text": "Buy now",
  "button_url": "https://example.com",
  "image_id": 21,
  "is_show": 1
}
```

## SEO

Endpoint:

```text
PUT /product-management/v3/products/{product}/seo
```

Owns:

```text
products.meta_title
products.meta_keywords
products.meta_description
```

## FAQ

Endpoint:

```text
PUT /product-management/v3/products/{product}/faq
```

Owns:

```text
products.faq
```

Payload:

```json
{
  "faq": [
    {"question": "Question?", "answer": "Answer."}
  ]
}
```

Rules:

```text
Filter empty question/answer pairs.
Store only valid rows.
```

