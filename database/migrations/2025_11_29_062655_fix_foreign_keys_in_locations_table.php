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
        Schema::table('locations', function (Blueprint $table) {
            // Drop old integer columns
            $table->dropColumn(['business_id', 'createdby_id', 'updatedby_id', 'deletedby_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            // Add new unsignedBigInteger columns with foreign keys
            $table->unsignedBigInteger('business_id')->nullable()->after('address');
            $table->unsignedBigInteger('createdby_id')->nullable()->after('is_active');
            $table->unsignedBigInteger('updatedby_id')->nullable()->after('createdby_id');
            $table->unsignedBigInteger('deletedby_id')->nullable()->after('updatedby_id');

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->foreign('createdby_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updatedby_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('deletedby_id')
                ->references('id')
                ->on('users')
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
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['createdby_id']);
            $table->dropForeign(['updatedby_id']);
            $table->dropForeign(['deletedby_id']);
            $table->dropColumn(['business_id', 'createdby_id', 'updatedby_id', 'deletedby_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->integer('business_id')->nullable();
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
        });
    }
};
