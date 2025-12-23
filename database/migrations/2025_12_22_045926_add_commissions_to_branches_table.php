<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {

            // ===== تسليم عادي =====
            $table->decimal('normal_delivery_fee', 10, 2)->default(0);
            $table->decimal('normal_delivery_percent', 5, 2)->default(0);

            $table->decimal('normal_cod_fee', 10, 2)->default(0);
            $table->decimal('normal_cod_percent', 5, 2)->default(0);

            $table->decimal('normal_paid_return_fee', 10, 2)->default(0);
            $table->decimal('normal_paid_return_percent', 5, 2)->default(0);

            // ===== تسليم مكتب =====
            $table->decimal('office_delivery_fee', 10, 2)->default(0);
            $table->decimal('office_delivery_percent', 5, 2)->default(0);

            $table->decimal('office_cod_fee', 10, 2)->default(0);
            $table->decimal('office_cod_percent', 5, 2)->default(0);

            $table->decimal('office_paid_return_fee', 10, 2)->default(0);
            $table->decimal('office_paid_return_percent', 5, 2)->default(0);

            // ===== عمولات الطلبات =====
            $table->decimal('orders_fee', 10, 2)->default(0);
            $table->decimal('orders_percent', 5, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'normal_delivery_fee',
                'normal_delivery_percent',
                'normal_cod_fee',
                'normal_cod_percent',
                'normal_paid_return_fee',
                'normal_paid_return_percent',

                'office_delivery_fee',
                'office_delivery_percent',
                'office_cod_fee',
                'office_cod_percent',
                'office_paid_return_fee',
                'office_paid_return_percent',

                'orders_fee',
                'orders_percent',
            ]);
        });
    }
};
