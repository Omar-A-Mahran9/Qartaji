<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissionArray = config('acl.permissions');

        foreach ($allPermissionArray as $modelType => $allPermissions) {
            $type = $modelType == 'adminMultiShop' ? 'admin' : $modelType;
            $type = $type == 'shopMultiShop' ? 'shop' : $type;

            foreach ($allPermissions as $permissionName => $permissionValues) {
                foreach ($permissionValues as $permission) {
                    Permission::findOrCreate($type.'.'.$permissionName.'.'.$permission);
                }
            }
        }

        Artisan::call('cache:clear');
    }
}
