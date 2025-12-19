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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'alert', 'system', 'alarm', 'message'])->default('info');
            $table->enum('status', ['read', 'unread'])->default('unread');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
