<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->foreignId('governorate_id')
                ->nullable()
                ->after('birth_date')
                ->constrained('governorates')
                ->nullOnDelete();

            $table->foreignId('area_id')
                ->nullable()
                ->after('governorate_id')
                ->constrained('areas')
                ->nullOnDelete();

            $table->index(['governorate_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropForeign(['governorate_id']);
            $table->dropForeign(['area_id']);
            $table->dropIndex(['governorate_id', 'area_id']);
            $table->dropColumn(['governorate_id', 'area_id']);
        });
    }
};
