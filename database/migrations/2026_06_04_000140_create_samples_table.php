<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sample_name');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('sample_cost', 12, 2)->default(0);
            $table->string('shipping_company')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
