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
            // Drop old duration_type field
            $table->dropColumn('duration_type');

            // Add proper duration fields
            $table->integer('duration')->default(30)->after('price')->comment('Subscription duration in days');
            $table->enum('price_interval', ['daily', 'weekly', 'monthly', 'yearly'])->default('monthly')->after('duration')->comment('Billing interval');
            $table->integer('trial_days')->default(0)->after('price_interval')->comment('Free trial period in days');
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
            $table->dropColumn(['duration', 'price_interval', 'trial_days']);
            $table->string('duration_type')->nullable()->comment('week, month, year')->after('price');
        });
    }
};
