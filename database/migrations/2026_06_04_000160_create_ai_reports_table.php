<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->string('report_type')->default('tracking')->index();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('grade')->nullable()->index();
            $table->text('summary');
            $table->text('risk_points')->nullable();
            $table->text('next_steps')->nullable();
            $table->json('raw_payload')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
    }
};
