<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('creators')->whereNull('ai_score')->update(['ai_score' => 0]);
        DB::statement('ALTER TABLE creators MODIFY ai_score TINYINT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE creators MODIFY ai_score TINYINT UNSIGNED NOT NULL');
    }
};
