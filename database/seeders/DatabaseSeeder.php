<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\SubscriptionPackage;
use App\Models\Business;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create Permissions
        $this->createPermissions();

        // Create Roles
        $this->createRoles();

        // Create Default Subscription Package
        $package = $this->createDefaultPackage();

        // Create Super Admin User
        $this->createSuperAdmin();

        // Create Sample Business with Business Admin
        $this->createSampleBusiness($package);

        $this->command->info('Database seeded successfully!');
    }

    private function createPermissions()
    {
        $permissions = [
            // Business Management
            'view-businesses',
            'create-business',
            'edit-business',
            'delete-business',

            // Package Management
            'view-packages',
            'create-package',
            'edit-package',
            'delete-package',

            // Camera Management
            'view-cameras',
            'create-camera',
            'edit-camera',
            'delete-camera',

            // Location Management
            'view-locations',
            'create-location',
            'edit-location',
            'delete-location',

            // User Management
            'view-users',
            'create-user',
            'edit-user',
            'delete-user',

            // Role & Permission Management
            'view-roles',
            'create-role',
            'edit-role',
            'delete-role',
            'assign-permissions',

            // Analytics & Reports
            'view-analytics',
            'view-reports',
            'export-data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        $this->command->info('✓ Permissions created');
    }

    private function createRoles()
    {
        // Super Admin Role (no business_id - global)
        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'api',
            'business_id' => null
        ]);
        $superAdmin->syncPermissions(Permission::all()); // All permissions

        // Business Admin Role Template (will be created per business)
        $businessAdminTemplate = Role::firstOrCreate([
            'name' => 'Business Admin Template',
            'guard_name' => 'api',
            'business_id' => null
        ]);
        $businessAdminTemplate->syncPermissions([
            'view-cameras', 'create-camera', 'edit-camera', 'delete-camera',
            'view-locations', 'create-location', 'edit-location', 'delete-location',
            'view-users', 'create-user', 'edit-user', 'delete-user',
            'view-roles', 'create-role', 'edit-role', 'delete-role',
            'view-analytics', 'view-reports'
        ]);

        // Business User Role Template
        $businessUserTemplate = Role::firstOrCreate([
            'name' => 'Business User Template',
            'guard_name' => 'api',
            'business_id' => null
        ]);
        $businessUserTemplate->syncPermissions([
            'view-cameras',
            'view-locations',
            'view-analytics'
        ]);

        $this->command->info('✓ Roles created');
    }

    private function createDefaultPackage()
    {
        $package = SubscriptionPackage::firstOrCreate(
            ['name' => 'Starter Package'],
            [
                'description' => 'Basic package for small businesses',
                'price' => '99.99',
                'duration' => 30,
                'price_interval' => 'monthly',
                'trial_days' => 7,
                'max_cameras' => 10,
                'max_locations' => 5,
                'max_users' => 10,
                'analytics_enabled' => true,
                'api_access_enabled' => false,
                'recording_enabled' => true,
                'motion_detection_enabled' => true,
                'is_active' => true,
            ]
        );

        // Create Premium Package
        SubscriptionPackage::firstOrCreate(
            ['name' => 'Premium Package'],
            [
                'description' => 'Advanced package for growing businesses',
                'price' => '199.99',
                'duration' => 30,
                'price_interval' => 'monthly',
                'trial_days' => 14,
                'max_cameras' => 50,
                'max_locations' => 20,
                'max_users' => 50,
                'analytics_enabled' => true,
                'api_access_enabled' => true,
                'recording_enabled' => true,
                'motion_detection_enabled' => true,
                'is_active' => true,
            ]
        );

        // Create Enterprise Package
        SubscriptionPackage::firstOrCreate(
            ['name' => 'Enterprise Package'],
            [
                'description' => 'Unlimited package for large enterprises',
                'price' => '499.99',
                'duration' => 365,
                'price_interval' => 'yearly',
                'trial_days' => 30,
                'max_cameras' => 1000,
                'max_locations' => 100,
                'max_users' => 500,
                'analytics_enabled' => true,
                'api_access_enabled' => true,
                'recording_enabled' => true,
                'motion_detection_enabled' => true,
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Subscription packages created');
        return $package;
    }

    private function createSuperAdmin()
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@pinkdreams.store'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'business_id' => null,
                'location_id' => null,
            ]
        );

        $superAdmin->assignRole('Super Admin');

        $this->command->info('✓ Super Admin created');
        $this->command->info('  Email: admin@pinkdreams.store');
        $this->command->info('  Password: password');
    }

    private function createSampleBusiness($package)
    {
        // Create a sample business
        $business = Business::firstOrCreate(
            ['email' => 'business@example.com'],
            [
                'name' => 'Demo Business',
                'domain' => 'demo.pinkdreams.store',
                'owner' => 'Demo Owner',
                'phone' => '+1234567890',
                'address' => '123 Demo Street, Demo City',
                'website' => 'https://demo.example.com',
                'subscription_package_id' => $package->id,
                'subscription_status' => 'active',
                'subscription_end_date' => now()->addYear(),
                'is_active' => true,
            ]
        );

        // Create Business Admin role for this business
        $businessAdminRole = Role::firstOrCreate([
            'name' => 'Business Admin',
            'guard_name' => 'api',
            'business_id' => $business->id
        ]);
        $businessAdminRole->syncPermissions([
            'view-cameras', 'create-camera', 'edit-camera', 'delete-camera',
            'view-locations', 'create-location', 'edit-location', 'delete-location',
            'view-users', 'create-user', 'edit-user', 'delete-user',
            'view-roles', 'create-role', 'edit-role', 'delete-role',
            'view-analytics', 'view-reports'
        ]);

        // Create Business Admin User
        $businessAdmin = User::firstOrCreate(
            ['email' => 'businessadmin@example.com'],
            [
                'name' => 'Business Admin',
                'password' => Hash::make('password'),
                'business_id' => $business->id,
                'location_id' => null,
            ]
        );
        $businessAdmin->assignRole($businessAdminRole);

        $this->command->info('✓ Sample business created');
        $this->command->info('  Business Admin Email: businessadmin@example.com');
        $this->command->info('  Password: password');
    }
}
