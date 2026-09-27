<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Safe to re-run: findOrCreate never duplicates, and syncPermissions
     * resets each role to exactly the permissions defined in RoleName.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        // findOrCreate() caches its "does this exist" lookup as it goes, so the
        // first lookup (when zero permissions exist yet) can leave a stale empty
        // snapshot cached for the rest of this loop and beyond. Clear it before
        // syncPermissions() below reads permissions back by name.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleName::cases() as $roleName) {
            Role::findOrCreate($roleName->value, 'web')
                ->syncPermissions(array_map(fn (PermissionName $p) => $p->value, $roleName->permissions()));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
