<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // مين صاحب المعاملة؟ (فرع - تاجر - مندوب) - Polymorphic
            $table->morphs('transactable');

            // نوع المحفظة اللي بنلعب فيها (عمولة، عهدة، رصيد أساسي)
            $table->string('wallet_type');

            // نوع الحركة (إضافة / خصم)
            $table->enum('type', ['credit', 'debit']); // credit=إضافة، debit=خصم

            $table->decimal('amount', 15, 2); // المبلغ
            $table->text('description')->nullable(); // الوصف

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
