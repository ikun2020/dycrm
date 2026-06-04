<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('wechat')->index();
            $table->string('contact_person')->nullable();
            $table->string('status_after')->nullable()->index();
            $table->text('content');
            $table->text('next_action')->nullable();
            $table->timestamp('contacted_at')->index();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
