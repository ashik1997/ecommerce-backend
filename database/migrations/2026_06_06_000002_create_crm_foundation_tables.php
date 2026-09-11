<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_customer_tags')) {
            Schema::create('crm_customer_tags', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->string('name', 120);
                $table->string('slug', 160)->nullable();
                $table->string('color', 40)->nullable();
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_tags_website_idx');
                $table->index('status', 'crm_tags_status_idx');
                $table->index('slug', 'crm_tags_slug_idx');
            });
        }

        if (!Schema::hasTable('crm_customer_tag_pivots')) {
            Schema::create('crm_customer_tag_pivots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('crm_customer_tag_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['customer_id', 'crm_customer_tag_id'], 'crm_customer_tag_unique');
                $table->index('customer_id', 'crm_tag_pivots_customer_idx');
                $table->index('crm_customer_tag_id', 'crm_tag_pivots_tag_idx');
            });
        }

        if (!Schema::hasTable('crm_tasks')) {
            Schema::create('crm_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('assigned_user_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('task_type', 50)->nullable();
                $table->string('priority', 30)->nullable();
                $table->string('status', 30)->default('pending');
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('completion_note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('product_website_id', 'crm_tasks_website_idx');
                $table->index('customer_id', 'crm_tasks_customer_idx');
                $table->index('assigned_user_id', 'crm_tasks_assigned_user_idx');
                $table->index('status', 'crm_tasks_status_idx');
                $table->index('task_type', 'crm_tasks_type_idx');
                $table->index('priority', 'crm_tasks_priority_idx');
                $table->index('due_at', 'crm_tasks_due_at_idx');
            });
        }

        if (!Schema::hasTable('crm_activities')) {
            Schema::create('crm_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('activity_type', 50);
                $table->string('subject')->nullable();
                $table->text('description')->nullable();
                $table->string('source_module', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_activities_website_idx');
                $table->index('customer_id', 'crm_activities_customer_idx');
                $table->index('activity_type', 'crm_activities_type_idx');
                $table->index(['source_module', 'source_id'], 'crm_activities_source_idx');
                $table->index('performed_by', 'crm_activities_performed_by_idx');
                $table->index('occurred_at', 'crm_activities_occurred_at_idx');
            });
        }

        if (!Schema::hasTable('crm_notes')) {
            Schema::create('crm_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('customer_id');
                $table->text('note');
                $table->boolean('is_private')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('product_website_id', 'crm_notes_website_idx');
                $table->index('customer_id', 'crm_notes_customer_idx');
                $table->index('is_private', 'crm_notes_private_idx');
            });
        }

        if (!Schema::hasTable('crm_communications')) {
            Schema::create('crm_communications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_website_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('channel', 40);
                $table->string('direction', 30)->default('outbound');
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->string('status', 40)->nullable();
                $table->string('provider', 80)->nullable();
                $table->string('provider_reference', 160)->nullable();
                $table->string('source_module', 80)->nullable();
                $table->unsignedBigInteger('sent_by')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index('product_website_id', 'crm_communications_website_idx');
                $table->index('customer_id', 'crm_communications_customer_idx');
                $table->index('channel', 'crm_communications_channel_idx');
                $table->index('direction', 'crm_communications_direction_idx');
                $table->index('status', 'crm_communications_status_idx');
                $table->index('provider_reference', 'crm_communications_provider_ref_idx');
                $table->index('source_module', 'crm_communications_source_module_idx');
                $table->index('sent_at', 'crm_communications_sent_at_idx');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: CRM audit/foundation records are business history.
    }
};
