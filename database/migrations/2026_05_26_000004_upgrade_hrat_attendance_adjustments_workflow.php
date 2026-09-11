<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpgradeHratAttendanceAdjustmentsWorkflow extends Migration
{
    public function up()
    {
        Schema::table('hrat_attendance_adjustments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('adjustment_type');
            $table->enum('requested_punch_type', ['entry', 'exit'])->nullable()->after('status');
            $table->time('requested_attendance_time')->nullable()->after('requested_punch_type');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('created_by');
            $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->index('status');
            $table->index('reviewed_by');
        });
    }

    public function down()
    {
        Schema::table('hrat_attendance_adjustments', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['reviewed_by']);
            $table->dropColumn([
                'status',
                'requested_punch_type',
                'requested_attendance_time',
                'reviewed_by',
                'reviewed_at',
                'review_note',
            ]);
        });
    }
}
