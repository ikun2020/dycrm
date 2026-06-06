<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('editable_menus')->nullable()->after('menu_permissions');
            $table->json('deletable_menus')->nullable()->after('editable_menus');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['editable_menus', 'deletable_menus']);
        });
    }
};
