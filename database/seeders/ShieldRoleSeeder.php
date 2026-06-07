<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $businessRole = Role::firstOrCreate([
            'name' => '商务 BD',
            'guard_name' => 'web',
        ]);

        $operationsRole = Role::firstOrCreate([
            'name' => '运营',
            'guard_name' => 'web',
        ]);

        $sampleRole = Role::firstOrCreate([
            'name' => '寄样专员',
            'guard_name' => 'web',
        ]);

        $this->ensurePermissions([
            'ReceiveSampleShipmentNotification',
        ]);

        $this->deleteUnusedDetailPermissions();
        $this->deleteLegacySampleNotificationPermission();
        $this->deleteLegacyPermissionGroupPermissions();
        $this->deleteSystemManagementPermissions();

        $businessRole->syncPermissions($this->permissionsForResources([
            'Creator',
            'FollowUp',
            'Product',
            'SampleItem',
            'Sample',
            'LiveSession',
            'GmvRecord',
            'AiReport',
        ], ['ViewAny', 'Create', 'Update']));

        $operationsRole->syncPermissions($this->permissionsForResources([
            'Creator',
            'Product',
            'SampleItem',
            'Sample',
            'LiveSession',
            'GmvRecord',
            'AiReport',
        ], ['ViewAny', 'Create', 'Update']));

        $sampleRole->syncPermissions(array_merge(
            $this->permissionsForResources(['Sample', 'SampleItem'], ['ViewAny', 'Create', 'Update']),
            ['ReceiveSampleShipmentNotification'],
        ));

        $superAdminRole->syncPermissions(Permission::query()->pluck('name')->all());

        if (Schema::hasColumn('users', 'role')) {
            User::query()
                ->where('role', 'super_admin')
                ->get()
                ->each(fn (User $user) => $user->assignRole($superAdminRole));
        }

        if (! User::role($superAdminRole->name)->exists()) {
            User::query()
                ->orderBy('id')
                ->first()
                ?->assignRole($superAdminRole);
        }

        User::query()
            ->whereDoesntHave('roles')
            ->get()
            ->each(fn (User $user) => $user->assignRole($businessRole));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function deleteUnusedDetailPermissions(): void
    {
        Permission::query()
            ->where('name', 'like', 'View:%')
            ->whereNotIn('name', [
                'View:NitikError',
                'View:Role',
            ])
            ->delete();
    }

    private function deleteLegacySampleNotificationPermission(): void
    {
        Permission::query()
            ->where('name', 'receive_sample_shipment_notification')
            ->delete();
    }

    private function deleteLegacyPermissionGroupPermissions(): void
    {
        Permission::query()
            ->where('name', 'like', '%:PermissionGroup')
            ->delete();
    }

    private function deleteSystemManagementPermissions(): void
    {
        Permission::query()
            ->where(function ($query): void {
                foreach ([
                    'AiSetting',
                    'NitikError',
                    'OperationLog',
                    'Role',
                    'User',
                ] as $subject) {
                    $query->orWhere('name', 'like', '%:'.$subject);
                }
            })
            ->delete();
    }

    /**
     * @param  array<int, string>  $resources
     * @param  array<int, string>  $actions
     * @return array<int, string>
     */
    private function permissionsForResources(array $resources, array $actions): array
    {
        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = $action.':'.$resource;
            }
        }

        return $this->ensurePermissions($permissions);
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    private function ensurePermissions(array $names): array
    {
        foreach ($names as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        return $names;
    }
}
