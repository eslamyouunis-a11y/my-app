<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول التجار
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_code')->unique()->nullable(); // كود التاجر
            $table->string('name'); // اسم المتجر/الشركة
            $table->string('email')->nullable(); // ايميل التواصل
            $table->text('address')->nullable();

            // الموقع
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete(); // الفرع المسؤول عنه

            // المسؤولين
            $table->string('contact_person_name'); // المسؤول عن الاكونت
            $table->string('contact_person_phone');

            $table->string('follow_up_name')->nullable(); // مسؤول المتابعة (خدمة العملاء)
            $table->string('follow_up_phone')->nullable(); // الرقم اللي هيظهر للراسل

            // الإعدادات المالية (الخاصة)
            $table->decimal('extra_weight_price', 10, 2)->default(0); // سعر الكيلو الزيادة

            // رسوم المرتجعات والإلغاء (قيمة ونسبة)
            $table->decimal('paid_return_fee', 10, 2)->default(0);
            $table->decimal('paid_return_percent', 5, 2)->default(0);

            $table->decimal('return_on_sender_fee', 10, 2)->default(0);
            $table->decimal('return_on_sender_percent', 5, 2)->default(0);

            $table->decimal('cancellation_fee', 10, 2)->default(0);
            $table->decimal('cancellation_percent', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. إضافة merchant_id لجدول المستخدمين
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable()->after('courier_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn('merchant_id');
        });
        Schema::dropIfExists('merchants');
    }
};
