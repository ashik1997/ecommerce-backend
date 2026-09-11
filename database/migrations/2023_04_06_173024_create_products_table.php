<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('store_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->unsignedBigInteger('childcategory_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('model', 200)->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('hsn_code', 100)->nullable();
            $table->string('image')->nullable();
            $table->longText('multiple_images')->nullable();
            $table->longText('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('specification')->nullable();
            $table->json('attributes')->nullable()->comment('Product attributes like material, style, pattern, dimensions, measurements');
            $table->json('shipping_info')->nullable()->comment('Shipping information: weight, dimensions, package type, returnable');
            $table->json('tax_info')->nullable()->comment('Tax information: tax_class_id, tax_percent');
            $table->longText('warrenty_policy')->nullable();
            $table->longText('size_chart')->nullable();
            $table->double('price')->default(0);
            $table->double('wholesale_price')->default(0);
            $table->double('retail_price')->default(0);
            $table->double('mrp_price')->default(0);
            $table->float('discount')->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->double('discount_price')->default(0);
            $table->unsignedInteger('discount_parcent')->nullable();
            $table->double('reward_points')->default(0);
            $table->double('stock')->default(0);
            $table->integer('min_order_qty')->default(1);
            $table->integer('max_order_qty')->nullable();
            $table->integer('low_stock')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('tags')->nullable();
            $table->string('video_url')->nullable();
            $table->tinyInteger('warrenty_id')->nullable();
            $table->string('chest', 60)->nullable();
            $table->string('length', 60)->nullable();
            $table->string('sleeve', 60)->nullable();
            $table->string('waist', 60)->nullable();
            $table->string('weight', 60)->nullable();
            $table->string('size_ratio', 100)->nullable();
            $table->text('fabrication')->nullable();
            $table->string('fabrication_gsm_ounce', 100)->nullable();
            $table->string('contact_number', 40)->nullable();
            $table->text('contact_description')->nullable();
            $table->string('availability_status', 50)->default('in_stock');
            $table->json('related_similar_products')->nullable();
            $table->json('related_recommended_products')->nullable();
            $table->json('related_addon_products')->nullable();
            $table->string('notification_title')->nullable();
            $table->text('notification_description')->nullable();
            $table->string('notification_button_text', 150)->nullable();
            $table->string('notification_button_url')->nullable();
            $table->string('notification_image_path')->nullable();
            $table->unsignedBigInteger('notification_image_id')->nullable();
            $table->boolean('notification_is_show')->default(false);
            $table->string('slug')->nullable();
            $table->tinyInteger('flag_id')->nullable();
            $table->tinyInteger('special_offer')->default(0)->comment('0=>Not; 1=>Yes');
            $table->tinyInteger('has_variant')->default(0)->comment('0=>No Variant; 1=>Product Has variant based on Colors, Region etc.');
            $table->string('offer_end_time')->nullable();
            $table->string('meta_title')->nullable();
            $table->longText('meta_keywords')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('meta_image')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=>Active; 0=>Inactive');
            $table->boolean('is_package')->default(false);
            $table->tinyInteger('is_demo')->default(0)->comment('0=>original; 1=>Demo');
            $table->tinyInteger('is_product_qty_multiply')->nullable();
            $table->string('added_by')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('subsubcategory_id')->nullable();
            $table->integer('publisher_id')->nullable();
            $table->text('photos')->nullable();
            $table->string('thumbnail_img')->nullable();
            $table->text('pdf_images')->nullable();
            $table->string('image_alt_tag')->nullable();
            $table->string('video_provider', 50)->nullable();
            $table->string('partial_payment_type', 50)->nullable();
            $table->decimal('partial_payment_price', 16, 2)->nullable();
            $table->string('stock_availability', 50)->nullable();
            $table->text('stock_notes')->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->string('unit', 50)->nullable();
            $table->integer('dealer_min_qty')->nullable();
            $table->string('tax_type', 50)->nullable();
            $table->string('pick_up', 50)->nullable();
            $table->decimal('shipping_cost_inside_dhaka', 16, 2)->nullable();
            $table->decimal('shipping_cost_outside_dhaka', 16, 2)->nullable();
            $table->integer('num_of_sale')->nullable();
            $table->string('youtube_video_link')->nullable();
            $table->string('meta_img')->nullable();
            $table->string('pdf')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('product_type', 50)->nullable();
            $table->boolean('todays_deal')->default(false);
            $table->boolean('published')->default(true);
            $table->boolean('featured')->default(false);
            $table->decimal('dealer_price', 16, 2)->nullable();
            $table->decimal('cash_price', 16, 2)->nullable();
            $table->decimal('purchase_price', 16, 2)->nullable();
            $table->integer('current_stock')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->boolean('digital')->nullable();
            $table->json('faq')->nullable();
            $table->unsignedBigInteger('product_website_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
