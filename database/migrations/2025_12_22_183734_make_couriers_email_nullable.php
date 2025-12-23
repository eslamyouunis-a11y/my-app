<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            // لو العمود موجود
            if (Schema::hasColumn('couriers', 'email')) {
                $table->string('email')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            if (Schema::hasColumn('couriers', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }
};
