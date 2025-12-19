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
        Schema::table('businesses', function (Blueprint $table) {
            // Business start date
            $table->date('start_date')->nullable()->after('name');

            // Additional contact
            $table->string('alternate_phone')->nullable()->after('phone');

            // Address fields
            $table->string('country')->nullable()->after('address');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->string('zip_code', 20)->nullable()->after('city');
            $table->string('landmark')->nullable()->after('zip_code');

            // Business settings
            $table->string('timezone')->default('UTC')->after('landmark');
            $table->string('currency', 10)->default('USD')->after('timezone');

            // Payment info
            $table->string('paid_via')->nullable()->after('subscription_end_date')->comment('PayPal, Stripe, Bank Transfer, etc');
            $table->string('payment_transaction_id')->nullable()->after('paid_via');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'start_date',
                'alternate_phone',
                'country',
                'state',
                'city',
                'zip_code',
                'landmark',
                'timezone',
                'currency',
                'paid_via',
                'payment_transaction_id'
            ]);
        });
    }
};
