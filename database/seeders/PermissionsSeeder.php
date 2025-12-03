<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'sanctum';

        collect([
            'dummy-model.create',
            'dummy-model.update',
            'dummy-model.delete',
        ])->each(function (string $permission) use ($guard): void {
            Permission::query()->updateOrCreate(
                ['name' => $permission, 'guard_name' => $guard],
                []
            );
        });
    }
}
