<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles for Karigar application
        $roles = [
            'customer',
            'service_provider',
            'admin',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api'],
                ['name' => $roleName, 'guard_name' => 'api']
            );
        }

        // Create permissions for the application
        $permissions = [
            // Customer permissions
            'create_service_request',
            'view_own_requests',
            'cancel_own_request',
            'submit_review',
            'view_providers',

            // Service Provider permissions
            'manage_provider_profile',
            'manage_services',
            'view_incoming_requests',
            'accept_request',
            'reject_request',
            'reschedule_request',
            'complete_request',
            'cancel_assigned_request',

            // Admin permissions
            'manage_users',
            'approve_providers',
            'suspend_users',
            'view_all_requests',
            'view_platform_stats',
            'manage_roles',
            'moderate_reviews',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'api'],
                ['name' => $permissionName, 'guard_name' => 'api']
            );
        }

        // Assign permissions to roles
        $customerRole = Role::findByName('customer', 'api');
        $customerRole->givePermissionTo([
            'create_service_request',
            'view_own_requests',
            'cancel_own_request',
            'submit_review',
            'view_providers',
        ]);

        $providerRole = Role::findByName('service_provider', 'api');
        $providerRole->givePermissionTo([
            'manage_provider_profile',
            'manage_services',
            'view_incoming_requests',
            'accept_request',
            'reject_request',
            'reschedule_request',
            'complete_request',
            'cancel_assigned_request',
            'view_providers', // Can view other providers
        ]);

        $adminRole = Role::findByName('admin', 'api');
        $adminRole->givePermissionTo(Permission::where('guard_name', 'api')->get()); // Give all permissions to admin

        $this->command->info('✅ Roles and permissions created successfully for Karigar!');
        $this->command->info('   - customer');
        $this->command->info('   - service_provider');
        $this->command->info('   - admin');
    }
}
