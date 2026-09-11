<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['product_orders', 'product_order_quotations'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'customer_contact_person_id')) {
                    $table->unsignedBigInteger('customer_contact_person_id')->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn($tableName, 'customer_type_snapshot')) {
                    $table->string('customer_type_snapshot', 20)->nullable()->after('customer_name');
                }
                if (!Schema::hasColumn($tableName, 'company_name_snapshot')) {
                    $table->string('company_name_snapshot')->nullable()->after('customer_type_snapshot');
                }
                if (!Schema::hasColumn($tableName, 'contact_person_snapshot')) {
                    $table->json('contact_person_snapshot')->nullable()->after('company_name_snapshot');
                }
                if (!Schema::hasColumn($tableName, 'total_purchase_price')) {
                    $table->decimal('total_purchase_price', 16, 2)->default(0)->after('total');
                }
                if (!Schema::hasColumn($tableName, 'gross_profit')) {
                    $table->decimal('gross_profit', 16, 2)->default(0)->after('total_purchase_price');
                }
                if (!Schema::hasColumn($tableName, 'net_profit')) {
                    $table->decimal('net_profit', 16, 2)->default(0)->after('gross_profit');
                }
            });
        }

        foreach (['product_order_products', 'product_order_quotation_products'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'product_purchase_order_id')) {
                    $table->unsignedBigInteger('product_purchase_order_id')->nullable()->after('unit_price_id');
                }
                if (!Schema::hasColumn($tableName, 'product_purchase_order_product_id')) {
                    $table->unsignedBigInteger('product_purchase_order_product_id')->nullable()->after('product_purchase_order_id');
                }
                if ($tableName === 'product_order_products' && !Schema::hasColumn($tableName, 'product_purchase_order_product_unit_id')) {
                    $table->unsignedBigInteger('product_purchase_order_product_unit_id')->nullable()->after('product_purchase_order_product_id');
                }
                if (!Schema::hasColumn($tableName, 'cost_method')) {
                    $table->string('cost_method', 30)->nullable()->after('purchase_price');
                }
                if (!Schema::hasColumn($tableName, 'item_meta')) {
                    $table->json('item_meta')->nullable()->after('price_unit');
                }
            });
        }

        $this->ensureIndex('product_orders', ['customer_contact_person_id'], 'po_customer_contact_idx');
        $this->ensureIndex('product_order_quotations', ['customer_contact_person_id'], 'poq_customer_contact_idx');

        $this->ensureIndex('product_order_products', ['product_purchase_order_id'], 'pop_purchase_idx');
        $this->ensureIndex('product_order_products', ['product_purchase_order_product_id'], 'pop_purchase_product_idx');
        $this->ensureIndex('product_order_products', ['product_purchase_order_product_unit_id'], 'pop_purchase_unit_idx');

        $this->ensureIndex('product_order_quotation_products', ['product_purchase_order_id'], 'poqp_purchase_idx');
        $this->ensureIndex('product_order_quotation_products', ['product_purchase_order_product_id'], 'poqp_purchase_product_idx');
    }

    public function down(): void
    {
        foreach (['product_order_products', 'product_order_quotation_products'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ([
                    'item_meta',
                    'cost_method',
                    'product_purchase_order_product_unit_id',
                    'product_purchase_order_product_id',
                    'product_purchase_order_id',
                ] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        foreach (['product_orders', 'product_order_quotations'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach ([
                    'net_profit',
                    'gross_profit',
                    'total_purchase_price',
                    'contact_person_snapshot',
                    'company_name_snapshot',
                    'customer_type_snapshot',
                    'customer_contact_person_id',
                ] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function ensureIndex(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $tableName)
            ->where('index_name', $indexName)
            ->exists();
    }
};
