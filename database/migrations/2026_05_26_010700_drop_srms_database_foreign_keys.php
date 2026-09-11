<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropForeignIfExists('srms_service_products', 'srms_service_products_service_id_foreign');
        $this->dropForeignIfExists('srms_service_products', 'srms_service_products_product_id_foreign');
        $this->dropForeignIfExists('srms_service_instances', 'srms_service_instances_service_id_foreign');
        $this->dropForeignIfExists('srms_service_instances', 'srms_service_instances_customer_id_foreign');
        $this->dropForeignIfExists('srms_service_instance_products', 'srms_service_instance_products_service_instance_id_foreign');
        $this->dropForeignIfExists('srms_service_instance_products', 'srms_service_instance_products_product_id_foreign');
        $this->dropForeignIfExists('srms_service_payments', 'srms_service_payments_service_instance_id_foreign');
        $this->dropForeignIfExists('srms_service_payments', 'srms_service_payments_customer_id_foreign');
        $this->dropForeignIfExists('srms_service_payments', 'srms_service_payments_payment_type_id_foreign');
        $this->dropForeignIfExists('srms_service_payments', 'srms_service_payments_account_id_foreign');
    }

    public function down(): void
    {
        // Intentionally no-op: SRMS keeps relationships in Eloquent models, not DB constraints.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (!Schema::hasTable($table) || DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }
};
