<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->timestamp('expected_delivery_at')->nullable()->after('accepted_at');
            $table->timestamp('rescheduled_at')->nullable()->after('expected_delivery_at');
            $table->timestamp('rescheduled_for')->nullable()->after('rescheduled_at');
            $table->string('reschedule_reason')->nullable()->after('rescheduled_for');
            $table->text('reschedule_notes')->nullable()->after('reschedule_reason');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_at',
                'expected_delivery_at',
                'rescheduled_at',
                'rescheduled_for',
                'reschedule_reason',
                'reschedule_notes',
            ]);
        });
    }
};
