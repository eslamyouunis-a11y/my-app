<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. أرصدة الفرع
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('commission_balance', 15, 2)->default(0); // رصيد العمولة (أرباح)
            $table->decimal('total_balance', 15, 2)->default(0);      // الرصيد الإجمالي (خزنة + عهدة)
            $table->decimal('couriers_custody_balance', 15, 2)->default(0); // رصيد عهدة المناديب
        });

        // 2. أرصدة التاجر
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0); // الرصيد الإجمالي
        });

        // 3. أرصدة المندوب
        Schema::table('couriers', function (Blueprint $table) {
            $table->decimal('commission_balance', 15, 2)->default(0); // رصيد العمولة
            $table->decimal('custody_balance', 15, 2)->default(0);    // رصيد العهدة (فلوس الشحنات)
        });
    }

    public function down(): void
    {
        // لو حبيت ترجع في كلامك
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['commission_balance', 'total_balance', 'couriers_custody_balance']));
        Schema::table('merchants', fn (Blueprint $table) => $table->dropColumn(['balance']));
        Schema::table('couriers', fn (Blueprint $table) => $table->dropColumn(['commission_balance', 'custody_balance']));
    }
};
