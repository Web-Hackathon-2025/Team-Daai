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
        Schema::create('sent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('message');
            $table->enum('recipient_type', ['all', 'super_admins', 'businesses', 'business_users', 'specific'])->default('all');
            $table->json('recipient_ids')->nullable();
            $table->enum('notification_type', ['info', 'warning', 'alert', 'system'])->default('info');
            $table->boolean('send_email')->default(false);
            $table->integer('recipients_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index('sender_id');
            $table->index('created_at');
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sent_messages');
    }
};
