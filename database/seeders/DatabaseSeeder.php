<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for Karigar Application
     *
     * @return void
     */
    public function run()
    {
        // Seed Karigar roles and permissions
        $this->call(RoleSeeder::class);

        // Create Admin User (optional)
        $this->createAdmin();

        $this->command->info('✅ Karigar database seeded successfully!');
    }

    /**
     * Create a default admin user (optional)
     */
    private function createAdmin()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@karigar.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
            ]
        );

        $admin->assignRole('admin');

        $this->command->info('✓ Admin user created');
        $this->command->info('  Email: admin@karigar.com');
        $this->command->info('  Password: password123');
    }
}
