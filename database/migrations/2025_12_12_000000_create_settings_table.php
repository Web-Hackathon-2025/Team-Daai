<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, integer
            $table->string('group')->default('general'); // general, email, payment, system
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            // Unique constraint: one key per business (null business_id = global settings)
            $table->unique(['business_id', 'key']);
            
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
