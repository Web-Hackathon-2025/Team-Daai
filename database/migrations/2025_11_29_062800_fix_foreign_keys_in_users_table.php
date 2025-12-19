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
            // Drop old integer columns
            $table->dropColumn(['business_id', 'location_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Add new unsignedBigInteger columns with foreign keys
            $table->unsignedBigInteger('business_id')->nullable()->after('password');
            $table->unsignedBigInteger('location_id')->nullable()->after('business_id');

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');
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
            $table->dropForeign(['business_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['business_id', 'location_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('business_id')->nullable();
            $table->integer('location_id')->nullable();
        });
    }
};
