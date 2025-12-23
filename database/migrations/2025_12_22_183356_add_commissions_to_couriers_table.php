<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {

            // تسليم
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('delivery_percent', 5, 2)->default(0);

            // مدفوع
            $table->decimal('paid_fee', 10, 2)->default(0);
            $table->decimal('paid_percent', 5, 2)->default(0);

            // مرتجع على الراسل
            $table->decimal('return_fee', 10, 2)->default(0);
            $table->decimal('return_percent', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_fee',
                'delivery_percent',
                'paid_fee',
                'paid_percent',
                'return_fee',
                'return_percent',
            ]);
        });
    }
};
