<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE branches
            MODIFY branch_type ENUM('direct','franchise','hub')
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE branches
            MODIFY branch_type ENUM('مباشر','امتياز تجاري','مركز فرز')
            NOT NULL
        ");
    }
};
