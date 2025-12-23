<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'orders_fee')) {
                $table->dropColumn('orders_fee');
            }

            if (Schema::hasColumn('branches', 'orders_percent')) {
                $table->dropColumn('orders_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('orders_fee', 10, 2)->default(0);
            $table->decimal('orders_percent', 5, 2)->default(0);
        });
    }
};
