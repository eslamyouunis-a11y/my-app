<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();

            // ✅ التعديل: جعلنا كل الحقول nullable لتقبل القيم الفارغة

            // 1. تسليم (Delivery)
            $table->decimal('delivery_value', 10, 2)->nullable()->default(0);
            $table->decimal('delivery_percentage', 5, 2)->nullable()->default(0);

            // 2. مرتجع مدفوع (Paid Return)
            $table->decimal('paid_value', 10, 2)->nullable()->default(0);
            $table->decimal('paid_percentage', 5, 2)->nullable()->default(0);

            // 3. مرتجع على الراسل (Return On Sender)
            $table->decimal('sender_return_value', 10, 2)->nullable()->default(0);
            $table->decimal('sender_return_percentage', 5, 2)->nullable()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_commissions');
    }
};
