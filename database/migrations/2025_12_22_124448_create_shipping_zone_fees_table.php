<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_zone_fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_fee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('area_id')
                ->constrained()
                ->cascadeOnDelete();

            // باب البيت فقط
            $table->decimal('home_price', 10, 2);
            $table->unsignedInteger('home_sla_days');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['shipping_fee_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_fees');
    }
};
