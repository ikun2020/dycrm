<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_posts', function (Blueprint $table): void {
            $table->string('category', 100)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_posts', function (Blueprint $table): void {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
