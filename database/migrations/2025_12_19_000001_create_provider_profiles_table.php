<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('category')->nullable(); // plumber, electrician, tutor, etc.
            $table->string('location')->nullable(); // City or area
            $table->string('availability')->nullable(); // e.g., "Mon-Fri 9AM-6PM"
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('category');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_profiles');
    }
};
