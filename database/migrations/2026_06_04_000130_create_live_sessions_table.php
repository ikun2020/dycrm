<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('scheduled')->index();
            $table->decimal('slot_fee', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->timestamp('pre_live_remind_at')->nullable()->index();
            $table->timestamp('review_remind_at')->nullable()->index();
            $table->text('script_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
