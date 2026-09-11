<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DbClearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tables = [
            "about_us",
            // "ac_accounts",
            // "ac_event_mappings",
            // "ac_income_categories",
            "ac_incomes",
            // "ac_investor_profit_allocations",
            "ac_investor_rules",
            "ac_money_withdraws",
            "ac_moneydeposits",
            "ac_moneytransfer",
            // "ac_transactions",

            "banners",
            "billing_addresses",
            "blog_categories",
            "blogs",
            "brands",
            "cache",
            "cache_locks",
            "carts",
            "categories",
            "category_sub_category_brands",
            "child_categories",
            "colors",
            // "config_setups",
            "contact_requests",
            // "country",
            "custom_pages",
            // "customer_categories",
            "customer_contact_histories",
            "customer_next_contact_dates",
            // "customer_source_types",
            "customers",

            "db_customer_payments",
            // "db_expense_categories",
            "db_expenses",
            // "db_paymenttypes",
            "db_purchasepayments",
            "db_supplier_payments",
            "db_suppliers",
            // "db_taxes",

            "device_conditions",
            // "districts",
            // "divisions",
            // "email_configures",
            // "email_templates",

            "failed_jobs",
            "faqs",
            "fcm_tokens",
            "flags",

            // "general_infos",
            // "google_recaptchas",

            "jobs",

            "manual_product_return_items",
            "manual_product_returns",
            "media",
            "media_files",
            // "media_folders",
            // "migrations",

            "notifications",

            "order_delivey_men",
            "order_details",
            "order_payments",
            "order_progress",
            "orders",
            // "outlets",
            
            "package_product_items",
            "package_products",

            "password_reset_tokens",
            "password_resets",

            "payment_gateways",
            // "permission_routes",
            "personal_access_tokens",
            "product_demand_predictions",
            "product_filter_attribute_mappings",
            "product_filter_attributes",
            "product_images",
            "product_models",
            // "product_order_courier_methods",
            // "product_order_delivery_methods",
            "product_order_hold",
            "product_order_hold_items",
            "product_order_products",
            "product_order_quotation_products",
            "product_order_quotations",
            "product_order_return_products",
            "product_order_returns",
            "product_orders",
            "product_purchase_order_product_units",
            "product_purchase_order_products",
            "product_purchase_orders",
            "product_purchase_other_charges",
            "product_purchase_quotation_products",
            "product_purchase_quotations",
            "product_purchase_return_products",
            "product_purchase_returns",
            "product_question_answers",
            "product_reviews",
            "product_size_values",
            "product_sizes",
            "product_stock_logs",
            "product_stock_variant_groups",
            "product_stock_variants_group_keys",
            "product_stocks",
            "product_supplier_contacts",
            "product_suppliers",
            "product_unit_pricing",
            "product_variant_combinations",
            "product_variants",
            "product_views",
            "product_warehouse_room_cartoons",
            "product_warehouse_rooms",
            "product_warehouses",
            "product_warrenties",
            "product_websites",
            "products",

            "promo_codes",
            "promotional_banners",

            // "role_permissions",
            "sessions",
            "shipping_addresses",
            "side_banners",
            "sims",
            "sms_gateways",
            "sms_histories",
            "sms_templates",
            // "social_logins",
            "storage_types",
            "subcategories",
            "subscribed_users",
            "supplier_source_types",
            "support_messages",
            "support_tickets",

            // "terms_and_policies",
            "testimonials",

            // "unions",
            // "units",
            // "upazilas",
            // "user_activities",
            // "user_addresses",
            "user_cards",
            // "user_role_permissions",
            // "user_roles",
            "user_sales_targets",
            // "users",
            "video_galleries",
            "wish_lists"
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
