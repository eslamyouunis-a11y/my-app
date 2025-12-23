<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            // polymorphic owner: Merchant / Courier / Branch
            $table->morphs('owner');

            $table->string('type'); // WalletType
            $table->string('currency', 3)->default('EGP');

            // ممنوع تعديله مباشرة: هنحافظ عليه كـ cached balance (اختياري)
            // أو نخليه null ونعتمد على SUM(entries) — الأفضل للأمان لاحقًا:
            $table->bigInteger('cached_balance')->default(0);

            $table->timestamps();

            $table->unique(['owner_type', 'owner_id', 'type'], 'wallets_owner_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
