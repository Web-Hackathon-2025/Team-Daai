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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who performed action');
            $table->string('user_name')->nullable()->comment('Cached user name');
            $table->string('user_email')->nullable()->comment('Cached user email');
            $table->unsignedBigInteger('business_id')->nullable()->comment('Business context for multi-tenant isolation');
            $table->string('category', 50)->comment('Business, Package, User, Settings, Auth, Camera, Location, etc.');
            $table->string('action', 100)->comment('Created, Updated, Deleted, Login, Logout, etc.');
            $table->text('description')->comment('Human-readable description of the activity');
            $table->string('model_type', 100)->nullable()->comment('Related model class name');
            $table->unsignedBigInteger('model_id')->nullable()->comment('Related model ID');
            $table->json('changes')->nullable()->comment('Old/new values for updates');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6 address');
            $table->text('user_agent')->nullable()->comment('Browser/device information');
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('business_id');
            $table->index('category');
            $table->index('action');
            $table->index('created_at');
            $table->index(['business_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
