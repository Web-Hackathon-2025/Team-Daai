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
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');

            // Basic Information
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('template')->nullable(); // RTSP template name
            $table->string('protocol')->default('RTSP');
            $table->string('server')->nullable(); // FlynetES-01, FlynetES-02, etc.

            // Connection Details (RTSP)
            $table->text('rtsp_url')->nullable();
            $table->string('rtsp_username')->nullable();
            $table->string('rtsp_password')->nullable();
            $table->string('rtsp_host')->nullable();
            $table->integer('rtsp_port')->default(554);
            $table->string('rtsp_path')->nullable(); // /Streaming/Channels/101

            // Connection Details (RTMP)
            $table->text('rtmp_url')->nullable();

            // Technical Specifications
            $table->string('resolution')->nullable(); // 1920x1080, 1280x720, etc.
            $table->string('camera_type')->nullable(); // Dome, Bullet, PTZ, etc.
            $table->boolean('analytical')->default(false); // AI analytics enabled
            $table->string('codec')->nullable(); // H.264, H.265
            $table->integer('fps')->nullable(); // Frames per second
            $table->integer('bitrate')->nullable(); // Kbps

            // Storage & Recording Plan
            $table->integer('storage_days')->default(30); // 1-365 days
            $table->integer('pre_alarm_seconds')->default(10); // 0-30 seconds
            $table->decimal('storage_size_gb', 10, 2)->nullable(); // Calculated storage

            // Location Details
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Status & Monitoring
            $table->string('status')->default('online');
            $table->timestamp('last_seen_at')->nullable();
            $table->text('comments')->nullable();

            // Stream Management
            $table->string('stream_key')->nullable(); // Unique stream identifier
            $table->text('hls_url')->nullable(); // HLS playlist URL
            $table->boolean('is_streaming')->default(false);
            $table->integer('stream_pid')->nullable(); // FFmpeg process ID

            // Metadata
            $table->json('metadata')->nullable(); // Additional camera info
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes(); // Soft delete support

            // Indexes for performance
            $table->index('business_id');
            $table->index('location_id');
            $table->index('protocol');
            $table->index('server');
            $table->index('status');
            $table->index('is_active');
            $table->index('is_streaming');
            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cameras');
    }
};
