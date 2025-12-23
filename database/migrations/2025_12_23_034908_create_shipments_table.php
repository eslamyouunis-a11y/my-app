<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            // 1. الأكواد والعلاقات
            $table->string('barcode')->unique()->index(); // باركود الشحنة
            $table->foreignId('original_shipment_id')->nullable()->constrained('shipments')->nullOnDelete(); // للشحنات المرتجعة (R)

            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete(); // الراسل
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete(); // الفرع الحالي
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete(); // المندوب الحالي

            // 2. بيانات المرسل إليه
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->string('receiver_phone_alt')->nullable();
            $table->foreignId('governorate_id')->constrained(); // عشان حساب السعر
            $table->foreignId('area_id')->nullable()->constrained();
            $table->text('address');

            // 3. الحالة والنوع
            $table->string('status')->default('saved'); // ShipmentStatus
            $table->string('shipping_type')->default('normal'); // ShipmentType

            // 4. تفاصيل الطرد
            $table->text('content')->nullable(); // محتوى الشحنة
            $table->decimal('weight', 8, 2)->default(1); // الوزن
            $table->boolean('is_open_allowed')->default(false); // قابل للفتح
            $table->boolean('is_fragile')->default(false); // قابل للكسر
            $table->boolean('is_office_pickup')->default(false); // استلام من المكتب (بيغير السعر)

            // 5. 💰 الماليات (القيم المحفوظة - Snapshots)
            // سعر المنتج نفسه
            $table->decimal('order_price', 10, 2)->default(0);

            // مصاريف الشحن وتفاصيلها
            $table->decimal('base_shipping_fee', 10, 2)->default(0); // سعر الشحن الأساسي (بدون وزن)
            $table->decimal('extra_weight_fee', 10, 2)->default(0);  // سعر الوزن الزائد
            $table->decimal('total_shipping_fee', 10, 2)->default(0); // الإجمالي (أساسي + وزن)

            // مصاريف تُطبق عند اللزوم (بتكون 0 في البداية)
            $table->decimal('return_fee', 10, 2)->default(0);       // رسوم المرتجع (تُحسب لو الحالة بقت مرتجع)
            $table->decimal('cancellation_fee', 10, 2)->default(0); // رسوم الإلغاء

            // النتائج النهائية
            $table->decimal('cod_amount', 10, 2)->default(0);       // المطلوب تحصيله من العميل (شامل الشحن)
            $table->decimal('merchant_net_amount', 10, 2)->default(0); // الصافي للتاجر (بعد خصم العمولات)

            // 6. التواريخ والملاحظات
            $table->date('delivery_date')->nullable(); // SLA Date
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('return_reason')->nullable(); // سبب الارتجاع/الإلغاء

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
