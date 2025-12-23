<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_fees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_governorate_id')->constrained('governorates');
            $table->foreignId('to_governorate_id')->constrained('governorates');

            // باب البيت
            $table->decimal('home_price', 10, 2);
            $table->unsignedInteger('home_sla_days');

            // تسليم مكتب
            $table->decimal('office_price', 10, 2);
            $table->unsignedInteger('office_sla_days');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['from_governorate_id', 'to_governorate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_fees');
    }
};
