<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->integer('max_cameras')->default(10)->after('duration_type');
            $table->integer('max_locations')->default(5)->after('max_cameras');
            $table->integer('max_users')->default(10)->after('max_locations');
            $table->boolean('analytics_enabled')->default(false)->after('max_users');
            $table->boolean('api_access_enabled')->default(false)->after('analytics_enabled');
            $table->boolean('recording_enabled')->default(true)->after('api_access_enabled');
            $table->boolean('motion_detection_enabled')->default(true)->after('recording_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn([
                'max_cameras',
                'max_locations',
                'max_users',
                'analytics_enabled',
                'api_access_enabled',
                'recording_enabled',
                'motion_detection_enabled'
            ]);
        });
    }
};
