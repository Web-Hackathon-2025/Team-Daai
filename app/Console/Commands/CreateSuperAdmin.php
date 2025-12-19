<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin
                            {--email= : Email address for the super admin}
                            {--name= : Name of the super admin}
                            {--password= : Password for the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Admin user with all permissions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating Super Admin User...');
        $this->info('');

        // Get or ask for email
        $email = $this->option('email') ?: $this->ask('Enter email address');

        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email'
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email or email already exists!');
            return Command::FAILURE;
        }

        // Get or ask for name
        $name = $this->option('name') ?: $this->ask('Enter full name');

        // Get or ask for password
        $password = $this->option('password') ?: $this->secret('Enter password (min 6 characters)');

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters!');
            return Command::FAILURE;
        }

        // Ensure Super Admin role exists
        $this->ensureSuperAdminRole();

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Assign admin role
        $user->assignRole('admin');

        $this->info('');
        $this->info('✓ Admin user created successfully!');
        $this->info('');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', 'admin'],
                ['Permissions', 'All admin permissions granted'],
            ]
        );

        $this->info('');
        $this->info('You can now login with these credentials.');

        return Command::SUCCESS;
    }

    /**
     * Ensure admin role exists with all permissions
     */
    private function ensureSuperAdminRole()
    {
        // Check if admin role exists
        $superAdmin = Role::where('name', 'admin')
            ->where('guard_name', 'api')
            ->first();

        if (!$superAdmin) {
            $this->warn('admin role does not exist. Creating it now...');

            $superAdmin = Role::create([
                'name' => 'admin',
                'guard_name' => 'api'
            ]);

            // Get all permissions
            $permissions = Permission::where('guard_name', 'api')->get();

            if ($permissions->count() === 0) {
                $this->warn('No permissions found. Creating default permissions...');
                $this->createDefaultPermissions();
                $permissions = Permission::where('guard_name', 'api')->get();
            }

            // Assign all permissions to Super Admin
            $superAdmin->syncPermissions($permissions);

            $this->info('✓ Super Admin role created with all permissions');
        }
    }

    /**
     * Create default permissions if they don't exist
     */
    private function createDefaultPermissions()
    {
        $permissions = [
            'view-businesses', 'create-business', 'edit-business', 'delete-business',
            'view-packages', 'create-package', 'edit-package', 'delete-package',
            'view-cameras', 'create-camera', 'edit-camera', 'delete-camera',
            'view-locations', 'create-location', 'edit-location', 'delete-location',
            'view-users', 'create-user', 'edit-user', 'delete-user',
            'view-roles', 'create-role', 'edit-role', 'delete-role',
            'assign-permissions', 'view-analytics', 'view-reports', 'export-data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        $this->info('✓ Default permissions created');
    }
}
