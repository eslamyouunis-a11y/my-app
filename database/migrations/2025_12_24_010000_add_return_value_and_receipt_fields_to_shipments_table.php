<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('return_value', 10, 2)->default(0)->after('order_price');
            $table->text('returned_content')->nullable()->after('content');
            $table->timestamp('received_from_courier_at')->nullable()->after('delivered_at');
            $table->foreignId('received_from_courier_by')->nullable()->constrained('users')->nullOnDelete()->after('received_from_courier_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['received_from_courier_by']);
            $table->dropColumn(['return_value', 'returned_content', 'received_from_courier_at', 'received_from_courier_by']);
        });
    }
};
