<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ledger_transaction_id')->constrained('ledger_transactions')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets');

            $table->string('type'); // debit/credit
            $table->bigInteger('amount'); // smallest unit (EGP as integer pounds) أو piastres حسب قرارك
            $table->string('memo')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
