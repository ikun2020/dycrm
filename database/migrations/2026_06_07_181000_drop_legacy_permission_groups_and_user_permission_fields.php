<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assignLegacySuperAdminsToShieldRole();

        if (Schema::hasColumn('users', 'permission_group_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('permission_group_id');
            });
        }

        $this->dropUserColumns([
            'role',
            'use_custom_permissions',
            'menu_permissions',
            'editable_menus',
            'deletable_menus',
        ]);

        Schema::dropIfExists('permission_groups');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')->default('staff')->after('password')->index();
            });
        }

        if (! Schema::hasTable('permission_groups')) {
            Schema::create('permission_groups', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->json('menu_permissions')->nullable();
                $table->json('editable_menus')->nullable();
                $table->json('deletable_menus')->nullable();
                $table->boolean('notify_sample_shipments')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'permission_group_id')) {
                $table->foreignId('permission_group_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('permission_groups')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'use_custom_permissions')) {
                $table->boolean('use_custom_permissions')->default(false)->after('permission_group_id');
            }

            if (! Schema::hasColumn('users', 'menu_permissions')) {
                $table->json('menu_permissions')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('users', 'editable_menus')) {
                $table->json('editable_menus')->nullable()->after('menu_permissions');
            }

            if (! Schema::hasColumn('users', 'deletable_menus')) {
                $table->json('deletable_menus')->nullable()->after('editable_menus');
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropUserColumns(array $columns): void
    {
        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('users', $column),
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($existingColumns): void {
            $table->dropColumn($existingColumns);
        });
    }

    private function assignLegacySuperAdminsToShieldRole(): void
    {
        if (
            ! Schema::hasTable('roles')
            || ! Schema::hasTable('model_has_roles')
            || ! Schema::hasColumn('users', 'role')
        ) {
            return;
        }

        $now = now();
        $roleName = config('filament-shield.super_admin.name', 'super_admin');
        $roleId = DB::table('roles')->where([
            'name' => $roleName,
            'guard_name' => 'web',
        ])->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')
            ->where('role', 'super_admin')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $userId): int => DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]));

        if (! DB::table('model_has_roles')->where('role_id', $roleId)->exists()) {
            $firstUserId = DB::table('users')->orderBy('id')->value('id');

            if ($firstUserId) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $firstUserId,
                ]);
            }
        }
    }
};
