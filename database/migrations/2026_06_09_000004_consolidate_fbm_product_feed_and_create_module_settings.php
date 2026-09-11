<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureCanonicalProductFeedFlag();
        $this->ensureModuleSettings();
    }

    public function down(): void
    {
        // Product opt-in data is intentionally preserved on rollback. Dropping the
        // canonical flag would silently discard operator selections.
        Schema::dropIfExists('fbm_module_settings');
    }

    private function ensureCanonicalProductFeedFlag(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $addedCanonicalFlag = false;

        if (!Schema::hasColumn('products', 'is_facebook_product_feed')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_facebook_product_feed')->default(false);
            });
            $addedCanonicalFlag = true;
        }

        if ($addedCanonicalFlag) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['is_facebook_product_feed', 'status'], 'products_fbm_feed_status_idx');
            });
        }

        if (Schema::hasColumn('products', 'is_facebook_feed')) {
            DB::table('products')
                ->where('is_facebook_feed', 1)
                ->where(function ($query) {
                    $query->whereNull('is_facebook_product_feed')
                        ->orWhere('is_facebook_product_feed', 0);
                })
                ->update(['is_facebook_product_feed' => 1]);
        }
    }

    private function ensureModuleSettings(): void
    {
        if (!Schema::hasTable('fbm_module_settings')) {
            Schema::create('fbm_module_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->boolean('feed_enabled')->default(true);
                $table->unsignedInteger('feed_cache_ttl_minutes')->default(360);
                $table->timestamp('last_feed_cache_invalidated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        } else {
            $this->addMissingSettingsColumns();
        }

        if (!DB::table('fbm_module_settings')->where('id', 1)->exists()) {
            DB::table('fbm_module_settings')->insert([
                'id' => 1,
                'feed_enabled' => true,
                'feed_cache_ttl_minutes' => 360,
                'last_feed_cache_invalidated_at' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function addMissingSettingsColumns(): void
    {
        $columns = [
            'feed_enabled' => fn(Blueprint $table) => $table->boolean('feed_enabled')->default(true),
            'feed_cache_ttl_minutes' => fn(Blueprint $table) => $table->unsignedInteger('feed_cache_ttl_minutes')->default(360),
            'last_feed_cache_invalidated_at' => fn(Blueprint $table) => $table->timestamp('last_feed_cache_invalidated_at')->nullable(),
            'updated_by' => fn(Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'created_at' => fn(Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn(Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('fbm_module_settings', $column)) {
                continue;
            }

            Schema::table('fbm_module_settings', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }
    }
};
