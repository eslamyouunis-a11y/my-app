<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {

            // حذف العمود القديم
            if (Schema::hasColumn('couriers', 'governorate')) {
                $table->dropColumn('governorate');
            }

            // تأكيد الأعمدة الصحيحة
            if (! Schema::hasColumn('couriers', 'governorate_id')) {
                $table->foreignId('governorate_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('couriers', 'area_id')) {
                $table->foreignId('area_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void {}
};
