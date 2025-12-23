<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();

            // كود المندوب
            $table->string('courier_code')->unique();

            // بيانات أساسية
            $table->string('full_name');
            $table->string('national_id')->unique();
            $table->string('email')->unique();
            $table->string('phone');

            $table->date('birth_date')->nullable();

            // عنوان (نص عادي)
            $table->string('governorate');
            $table->string('city');
            $table->text('address');

            // وسيلة النقل
            $table->enum('vehicle_type', [
                'car',
                'motorcycle',
                'bicycle',
                'public_transport',
            ]);

            // الرخص
            $table->date('driving_license_expiry')->nullable();
            $table->date('vehicle_license_expiry')->nullable();

            // معلومات طوارئ (اختيارية)
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relation')->nullable();
            $table->string('emergency_phone_1')->nullable();
            $table->string('emergency_phone_2')->nullable();
            $table->string('emergency_address')->nullable();

            // الحالة
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
