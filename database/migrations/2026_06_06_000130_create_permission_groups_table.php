<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->json('menu_permissions')->nullable();
            $table->json('editable_menus')->nullable();
            $table->json('deletable_menus')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('permission_group_id')
                ->nullable()
                ->after('role')
                ->constrained('permission_groups')
                ->nullOnDelete();
            $table->boolean('use_custom_permissions')
                ->default(false)
                ->after('permission_group_id');
        });

        $now = now();
        $allMenus = [
            'creators',
            'follow-ups',
            'products',
            'sample-items',
            'samples',
            'live-sessions',
            'gmv-records',
            'ai-reports',
        ];
        $bdViewMenus = $allMenus;
        $bdEditMenus = [
            'creators',
            'follow-ups',
            'samples',
            'live-sessions',
            'ai-reports',
        ];
        $operationViewMenus = [
            'creators',
            'products',
            'sample-items',
            'samples',
            'live-sessions',
            'gmv-records',
            'ai-reports',
        ];
        $operationEditMenus = [
            'products',
            'sample-items',
            'samples',
            'live-sessions',
            'gmv-records',
        ];

        $businessGroupId = DB::table('permission_groups')->insertGetId([
            'name' => '商务 BD',
            'description' => '适合商务开发和达人跟进：可维护达人、跟进、寄样、直播和 AI 报告，不默认授予删除权限。',
            'menu_permissions' => json_encode($bdViewMenus, JSON_UNESCAPED_UNICODE),
            'editable_menus' => json_encode($bdEditMenus, JSON_UNESCAPED_UNICODE),
            'deletable_menus' => json_encode([], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('permission_groups')->insert([
            [
                'name' => '运营',
                'description' => '适合商品、样品、寄样、直播和 GMV 数据维护，不默认授予删除权限。',
                'menu_permissions' => json_encode($operationViewMenus, JSON_UNESCAPED_UNICODE),
                'editable_menus' => json_encode($operationEditMenus, JSON_UNESCAPED_UNICODE),
                'deletable_menus' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '只读',
                'description' => '可以查看全部业务菜单，但不能新增、编辑或删除。',
                'menu_permissions' => json_encode($allMenus, JSON_UNESCAPED_UNICODE),
                'editable_menus' => json_encode([], JSON_UNESCAPED_UNICODE),
                'deletable_menus' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('users')
            ->where('role', '!=', 'super_admin')
            ->update([
                'permission_group_id' => $businessGroupId,
                'use_custom_permissions' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('permission_group_id');
            $table->dropColumn('use_custom_permissions');
        });

        Schema::dropIfExists('permission_groups');
    }
};
