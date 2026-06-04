<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gmv_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('live_session_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on')->index();
            $table->decimal('gmv', 14, 2)->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('refunds_count')->default(0);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('slot_fee', 12, 2)->default(0);
            $table->decimal('sample_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gmv_records');
    }
};
