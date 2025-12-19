<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('name'); // Stripe, PayPal, Razorpay, etc.
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('createdby_id')->nullable();
            $table->unsignedBigInteger('updatedby_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
