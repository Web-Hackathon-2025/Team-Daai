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
        Schema::table('users', function (Blueprint $table) {
            // Split name into first_name and last_name
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('prefix', 10)->nullable()->after('business_id')->comment('Mr, Mrs, Dr, etc');

            // Username for alternative login
            $table->string('username')->unique()->nullable()->after('email');

            // Additional contact
            $table->string('alternate_phone')->nullable()->after('location_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'prefix',
                'username',
                'alternate_phone'
            ]);
        });
    }
};
