<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-18 and constitution article VI: the two domain rules that one row can express move down to the engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE laptops.laptop ADD CONSTRAINT ck_laptop_total_positive CHECK (total_units >= 1)');
        DB::statement("ALTER TABLE laptops.\"user\" ADD CONSTRAINT ck_user_role CHECK (role IN ('admin', 'assistant'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE laptops.laptop DROP CONSTRAINT IF EXISTS ck_laptop_total_positive');
        DB::statement('ALTER TABLE laptops."user" DROP CONSTRAINT IF EXISTS ck_user_role');
    }
};
