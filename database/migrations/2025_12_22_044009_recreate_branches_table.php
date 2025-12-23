<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('branch_number')->unique();

            $table->string('name');
            $table->string('manager_name');
            $table->string('manager_phone');
            $table->string('email')->unique();

            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();

            // ❗ string مش enum عشان ميحصلش truncate
            $table->string('branch_type');

            $table->text('address');

            $table->boolean('is_active')->default(true);

            /* ===== عمولات تسليم عادي ===== */
            $table->decimal('normal_delivery_fee', 10, 2)->default(0);
            $table->decimal('normal_delivery_percent', 5, 2)->default(0);
            $table->decimal('normal_cod_fee', 10, 2)->default(0);
            $table->decimal('normal_cod_percent', 5, 2)->default(0);
            $table->decimal('normal_paid_return_fee', 10, 2)->default(0);
            $table->decimal('normal_paid_return_percent', 5, 2)->default(0);

            /* ===== عمولات تسليم مكتب ===== */
            $table->decimal('office_delivery_fee', 10, 2)->default(0);
            $table->decimal('office_delivery_percent', 5, 2)->default(0);
            $table->decimal('office_cod_fee', 10, 2)->default(0);
            $table->decimal('office_cod_percent', 5, 2)->default(0);
            $table->decimal('office_paid_return_fee', 10, 2)->default(0);
            $table->decimal('office_paid_return_percent', 5, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
