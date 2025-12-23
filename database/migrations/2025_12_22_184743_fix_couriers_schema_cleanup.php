<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {

            // 1. لو email موجود → نخليه nullable أو نشيله
            if (Schema::hasColumn('couriers', 'email')) {
                $table->dropColumn('email');
            }

            // 2. الموقع
            if (! Schema::hasColumn('couriers', 'governorate_id')) {
                $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('couriers', 'area_id')) {
                $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            }

            // 3. العمولات
            if (! Schema::hasColumn('couriers', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)->default(0);
                $table->decimal('delivery_percent', 5, 2)->default(0);
                $table->decimal('paid_fee', 10, 2)->default(0);
                $table->decimal('paid_percent', 5, 2)->default(0);
                $table->decimal('return_fee', 10, 2)->default(0);
                $table->decimal('return_percent', 5, 2)->default(0);
            }
        });
    }

    public function down(): void {}
};
