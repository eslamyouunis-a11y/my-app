<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();

            // idempotency key (مهم لتجنب تكرار القيد بسبب retry)
            $table->string('idempotency_key')->nullable()->unique();

            $table->string('source_type')->nullable(); // Shipment / Task / ManualAdjustment ...
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
