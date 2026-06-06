<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable()->index();
            $table->string('sku')->nullable()->unique();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('samples', function (Blueprint $table): void {
            $table->foreignId('sample_item_id')
                ->nullable()
                ->after('product_id')
                ->constrained('sample_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('samples', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sample_item_id');
        });

        Schema::dropIfExists('sample_items');
    }
};
