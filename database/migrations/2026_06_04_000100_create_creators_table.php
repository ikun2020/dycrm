<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creators', function (Blueprint $table): void {
            $table->id();
            $table->string('nickname');
            $table->string('real_name')->nullable();
            $table->string('platform')->default('douyin')->index();
            $table->string('platform_uid')->nullable()->index();
            $table->string('homepage_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('wechat')->nullable();
            $table->string('region')->nullable();
            $table->string('agency_name')->nullable();
            $table->string('category')->nullable()->index();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->unsignedInteger('avg_viewers')->default(0);
            $table->decimal('avg_order_value', 12, 2)->default(0);
            $table->decimal('quote_fee', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('cooperation_status')->default('to_develop')->index();
            $table->json('tags')->nullable();
            $table->unsignedTinyInteger('ai_score')->default(0)->index();
            $table->string('ai_grade')->nullable()->index();
            $table->text('ai_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creators');
    }
};
