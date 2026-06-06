<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->string('creator_type')->nullable()->after('agency_name')->index();
            $table->string('follower_tier')->nullable()->after('followers_count')->index();
            $table->string('primary_category')->nullable()->after('follower_tier')->index();
            $table->decimal('reputation_score', 5, 2)->nullable()->after('primary_category');
            $table->decimal('avg_sales_amount', 14, 2)->nullable()->after('reputation_score');
            $table->decimal('daily_sales_amount', 14, 2)->nullable()->after('avg_sales_amount');
            $table->decimal('male_fan_ratio', 5, 4)->nullable()->after('avg_order_value');
            $table->decimal('female_fan_ratio', 5, 4)->nullable()->after('male_fan_ratio');
            $table->string('gender_tendency')->nullable()->after('female_fan_ratio');
            $table->text('province_overview')->nullable()->after('gender_tendency');
            $table->text('city_overview')->nullable()->after('province_overview');
        });

        DB::table('creators')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->update([
                'creator_type' => DB::raw('COALESCE(creator_type, category)'),
                'primary_category' => DB::raw('COALESCE(primary_category, category)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('creators', function (Blueprint $table): void {
            $table->dropColumn([
                'creator_type',
                'follower_tier',
                'primary_category',
                'reputation_score',
                'avg_sales_amount',
                'daily_sales_amount',
                'male_fan_ratio',
                'female_fan_ratio',
                'gender_tendency',
                'province_overview',
                'city_overview',
            ]);
        });
    }
};
