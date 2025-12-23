<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول الأسعار الخاصة (تم إزالة النسب)
        Schema::create('merchant_special_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();

            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->cascadeOnDelete();

            // ✅ تم الاكتفاء بالقيم الثابتة فقط
            $table->decimal('delivery_fee', 10, 2)->default(0); // توصيل للمنزل
            $table->decimal('office_delivery_fee', 10, 2)->default(0); // توصيل للمكتب

            $table->timestamps();
        });

        // 2. جدول منتجات التاجر (زي ما هو)
        Schema::create('merchant_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('default_weight', 8, 2)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_products');
        Schema::dropIfExists('merchant_special_prices');
    }
};
