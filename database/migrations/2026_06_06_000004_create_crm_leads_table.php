<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('phone', 80)->nullable();
                $table->string('email')->nullable();
                $table->string('source', 80)->nullable();
                $table->string('status', 40)->default('new');
                $table->string('priority', 30)->default('normal');
                $table->decimal('estimated_value', 15, 2)->nullable();
                $table->unsignedTinyInteger('score')->default(0);
                $table->text('requirement')->nullable();
                $table->text('lost_reason')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->timestamp('qualified_at')->nullable();
                $table->timestamp('converted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('product_website_id', 'crm_leads_website_idx');
                $table->index('customer_id', 'crm_leads_customer_idx');
                $table->index('assigned_user_id', 'crm_leads_assigned_user_idx');
                $table->index('status', 'crm_leads_status_idx');
                $table->index('priority', 'crm_leads_priority_idx');
                $table->index('source', 'crm_leads_source_idx');
                $table->index('next_follow_up_at', 'crm_leads_next_follow_up_idx');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: CRM lead records are business history.
    }
};
