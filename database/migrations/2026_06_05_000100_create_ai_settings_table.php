<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false)->index();
            $table->string('provider_name')->nullable();
            $table->string('api_base_url')->default('https://api.openai.com/v1');
            $table->text('api_key')->nullable();
            $table->string('model')->default('gpt-4o-mini');
            $table->unsignedSmallInteger('timeout')->default(60);
            $table->decimal('temperature', 3, 2)->default(0.20);
            $table->unsignedSmallInteger('max_tokens')->default(1600);
            $table->text('system_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
