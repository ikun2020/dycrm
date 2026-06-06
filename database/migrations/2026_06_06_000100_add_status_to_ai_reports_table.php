<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_reports', function (Blueprint $table): void {
            $table->string('status')->default('completed')->index()->after('report_type');
            $table->text('error_message')->nullable()->after('next_steps');
        });
    }

    public function down(): void
    {
        Schema::table('ai_reports', function (Blueprint $table): void {
            $table->dropColumn(['status', 'error_message']);
        });
    }
};
