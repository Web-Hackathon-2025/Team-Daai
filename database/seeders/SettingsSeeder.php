<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Global Super Admin Settings
        $globalSettings = [
            // System Settings
            [
                'key' => 'app_name',
                'value' => 'Pink Dreams VMS',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Application name',
                'is_public' => true,
            ],
            [
                'key' => 'app_url',
                'value' => env('APP_URL', 'https://api.pinkdreams.store'),
                'type' => 'string',
                'group' => 'system',
                'description' => 'Application URL',
                'is_public' => true,
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'system',
                'description' => 'Enable maintenance mode',
                'is_public' => false,
            ],

            // Email Settings (Global)
            [
                'key' => 'mail_driver',
                'value' => 'smtp',
                'type' => 'string',
                'group' => 'email',
                'description' => 'Mail driver',
                'is_public' => false,
            ],
            [
                'key' => 'mail_host',
                'value' => 'smtp.mailtrap.io',
                'type' => 'string',
                'group' => 'email',
                'description' => 'SMTP host',
                'is_public' => false,
            ],
            [
                'key' => 'mail_port',
                'value' => '587',
                'type' => 'integer',
                'group' => 'email',
                'description' => 'SMTP port',
                'is_public' => false,
            ],
            [
                'key' => 'mail_encryption',
                'value' => 'tls',
                'type' => 'string',
                'group' => 'email',
                'description' => 'Mail encryption',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_address',
                'value' => 'noreply@pinkdreams.store',
                'type' => 'string',
                'group' => 'email',
                'description' => 'From email address',
                'is_public' => false,
            ],
            [
                'key' => 'mail_from_name',
                'value' => 'Pink Dreams VMS',
                'type' => 'string',
                'group' => 'email',
                'description' => 'From name',
                'is_public' => false,
            ],

            // General Settings
            [
                'key' => 'timezone',
                'value' => 'UTC',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default timezone',
                'is_public' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default currency',
                'is_public' => true,
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Date format',
                'is_public' => true,
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i:s',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Time format',
                'is_public' => true,
            ],

            // Security Settings
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Session timeout in minutes',
                'is_public' => false,
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Maximum login attempts',
                'is_public' => false,
            ],
            [
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Minimum password length',
                'is_public' => true,
            ],
        ];

        foreach ($globalSettings as $setting) {
            Setting::updateOrCreate(
                [
                    'business_id' => null,
                    'key' => $setting['key']
                ],
                $setting
            );
        }

        $this->command->info('Global settings seeded successfully!');
    }
}
