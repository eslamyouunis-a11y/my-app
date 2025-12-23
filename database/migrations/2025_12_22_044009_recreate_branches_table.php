<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بنحذف الجدول لو موجود عشان نبنيه من جديد بنضافة
        Schema::dropIfExists('branches');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            // تأكدنا إننا بنستخدم الترقيم الآمن
            $table->string('branch_number')->unique()->nullable();

            $table->string('name');
            $table->string('branch_type'); // direct, franchise, hub
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();

            // بيانات المدير والتواصل
            $table->string('manager_name');
            $table->string('manager_phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);

            // ==========================================
            // ✅ العمولات المالية (الهيكلة النهائية)
            // ==========================================

            // 1. التوصيل العادي (Doorstep)
            $table->decimal('normal_delivery_fee', 10, 2)->default(0);
            $table->decimal('normal_delivery_percent', 5, 2)->default(0);

            $table->decimal('normal_paid_return_fee', 10, 2)->default(0);     // مرتجع مدفوع
            $table->decimal('normal_paid_return_percent', 5, 2)->default(0);

            $table->decimal('normal_return_on_sender_fee', 10, 2)->default(0); // مرتجع على الراسل
            $table->decimal('normal_return_on_sender_percent', 5, 2)->default(0);

            // 2. توصيل المكتب (Office) - تسليم فقط
            $table->decimal('office_delivery_fee', 10, 2)->default(0);
            $table->decimal('office_delivery_percent', 5, 2)->default(0);

            // (تم إلغاء أعمدة التحصيل COD وأي أعمدة قديمة)

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
