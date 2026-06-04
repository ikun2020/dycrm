<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->decimal('retail_price', 12, 2)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->text('selling_points')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
