<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {

            // رقم الفرع (لو مش موجود)
            if (!Schema::hasColumn('branches', 'branch_number')) {
                $table->unsignedInteger('branch_number')->unique()->after('id');
            }

            // إيميل الفرع (حساب الدخول)
            if (!Schema::hasColumn('branches', 'email')) {
                $table->string('email')->unique()->after('manager_phone');
            }

            // نوع الفرع
            if (!Schema::hasColumn('branches', 'branch_type')) {
                $table->enum('branch_type', ['direct', 'franchise', 'hub'])->after('governorate_id');
            }

            // حذف أعمدة ملغية
            if (Schema::hasColumn('branches', 'code')) {
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('branches', 'manager_number')) {
                $table->dropColumn('manager_number');
            }
        });
    }

    public function down(): void
    {
        // مش هنرجّع الأعمدة الملغية
    }
};
