<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_groups', function (Blueprint $table): void {
            $table->boolean('notify_sample_shipments')->default(false)->after('deletable_menus');
        });
    }

    public function down(): void
    {
        Schema::table('permission_groups', function (Blueprint $table): void {
            $table->dropColumn('notify_sample_shipments');
        });
    }
};
