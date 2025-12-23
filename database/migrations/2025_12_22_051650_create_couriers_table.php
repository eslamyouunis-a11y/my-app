<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('couriers'); // أمان عشان لو الجدول موجود يمسحه

        Schema::create('couriers', function (Blueprint $table) {
            $table->id();

            // البيانات الأساسية
            $table->string('courier_code')->unique()->nullable();
            $table->string('full_name');
            $table->string('phone')->unique(); // عشان نمنع التكرار من الداتابيز
            $table->string('national_id')->unique(); // عشان نمنع التكرار
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();

            // بيانات المركبة
            $table->string('vehicle_type')->nullable();
            $table->date('driving_license_expiry')->nullable();
            $table->date('vehicle_license_expiry')->nullable();

            // العلاقات
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();

            $table->boolean('is_active')->default(true);

            // ✅ ده العمود اللي كان ناقص ومسبب المشكلة
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
