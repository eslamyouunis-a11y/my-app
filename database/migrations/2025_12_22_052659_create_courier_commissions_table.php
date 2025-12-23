<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courier_commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_id')
                ->constrained()
                ->cascadeOnDelete();

            // تسليم
            $table->decimal('delivery_value', 8, 2)->default(0);
            $table->decimal('delivery_percentage', 5, 2)->default(0);

            // مدفوع
            $table->decimal('paid_value', 8, 2)->default(0);
            $table->decimal('paid_percentage', 5, 2)->default(0);

            // مرتجع على الراسل
            $table->decimal('sender_return_value', 8, 2)->default(0);
            $table->decimal('sender_return_percentage', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_commissions');
    }
};
