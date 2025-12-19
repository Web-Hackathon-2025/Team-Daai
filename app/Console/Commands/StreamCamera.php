<?php

namespace App\Console\Commands;

use App\Models\Camera;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class StreamCamera extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stream:camera {camera_id} {--stop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start or stop camera stream conversion to HLS';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cameraId = $this->argument('camera_id');
        $stop = $this->option('stop');

        if ($stop) {
            return $this->stopStream($cameraId);
        }

        return $this->startStream($cameraId);
    }

    /**
     * Start camera stream
     */
    private function startStream($cameraId)
    {
        try {
            $camera = Camera::findOrFail($cameraId);

            // Get stream URL based on protocol
            $streamUrl = $camera->protocol === 'RTSP'
                ? $camera->getRtspFullUrl()
                : $camera->rtmp_url;

            if (!$streamUrl) {
                $this->error("No valid stream URL found for camera {$cameraId}");
                return Command::FAILURE;
            }

            // Create HLS directory
            $hlsDir = storage_path("app/public/streams/{$cameraId}");
            if (!file_exists($hlsDir)) {
                mkdir($hlsDir, 0755, true);
            }

            // Ensure PID directory exists
            $pidDir = storage_path("app/streams");
            if (!file_exists($pidDir)) {
                mkdir($pidDir, 0755, true);
            }

            // FFmpeg command to convert to HLS
            $command = $this->buildFFmpegCommand($streamUrl, $hlsDir, $camera);

            $this->info("Starting stream for camera: {$camera->name}");
            $this->info("Stream URL: {$streamUrl}");
            $this->info("Output directory: {$hlsDir}");

            // Start FFmpeg process
            $process = new Process($command);
            $process->setTimeout(null); // Run indefinitely
            $process->start();

            // Save PID for stopping later
            $pidFile = storage_path("app/streams/{$cameraId}.pid");
            file_put_contents($pidFile, $process->getPid());

            // Update camera record
            $hlsUrl = url("api/stream/hls/{$cameraId}/playlist.m3u8");
            $camera->update([
                'is_streaming' => true,
                'stream_pid' => $process->getPid(),
                'hls_url' => $hlsUrl,
                'status' => 'online',
                'last_seen_at' => now(),
            ]);

            $this->info("Stream started successfully!");
            $this->info("PID: {$process->getPid()}");
            $this->info("HLS URL: {$hlsUrl}");

            Log::info("Started stream for camera {$cameraId}", [
                'camera_name' => $camera->name,
                'pid' => $process->getPid(),
                'hls_url' => $hlsUrl,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to start stream: " . $e->getMessage());
            Log::error("Failed to start stream for camera {$cameraId}: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Stop camera stream
     */
    private function stopStream($cameraId)
    {
        try {
            $camera = Camera::findOrFail($cameraId);
            $pidFile = storage_path("app/streams/{$cameraId}.pid");

            if (!file_exists($pidFile)) {
                $this->error("Stream not running for camera {$cameraId}");

                // Clean up database state
                $camera->stopStreaming();

                return Command::FAILURE;
            }

            $pid = file_get_contents($pidFile);

            // Kill the process
            if (PHP_OS_FAMILY === 'Windows') {
                exec("taskkill /F /PID {$pid} 2>NUL", $output, $result);
            } else {
                exec("kill {$pid} 2>/dev/null", $output, $result);
            }

            // Clean up
            unlink($pidFile);

            // Update camera record
            $camera->stopStreaming();
            $camera->update(['status' => 'offline']);

            $this->info("Stream stopped successfully for camera: {$camera->name}");

            Log::info("Stopped stream for camera {$cameraId}", [
                'camera_name' => $camera->name,
                'pid' => $pid,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to stop stream: " . $e->getMessage());
            Log::error("Failed to stop stream for camera {$cameraId}: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Build FFmpeg command based on camera configuration
     */
    private function buildFFmpegCommand($streamUrl, $outputDir, $camera)
    {
        $command = ['ffmpeg'];

        // Input options for RTSP
        if ($camera->protocol === 'RTSP') {
            $command[] = '-rtsp_transport';
            $command[] = 'tcp';
            $command[] = '-fflags';
            $command[] = '+genpts';
        }

        // Input stream
        $command[] = '-i';
        $command[] = $streamUrl;

        // Video codec
        $command[] = '-c:v';
        $command[] = 'libx264';

        // Preset for encoding speed
        $command[] = '-preset';
        $command[] = 'ultrafast';

        // Tune for low latency
        $command[] = '-tune';
        $command[] = 'zerolatency';

        // Audio codec
        $command[] = '-c:a';
        $command[] = 'aac';

        // Output format
        $command[] = '-f';
        $command[] = 'hls';

        // HLS segment time (2 seconds)
        $command[] = '-hls_time';
        $command[] = '2';

        // Number of segments to keep (5 segments = 10 seconds buffer)
        $command[] = '-hls_list_size';
        $command[] = '5';

        // Delete old segments
        $command[] = '-hls_flags';
        $command[] = 'delete_segments';

        // Allow cache for faster startup
        $command[] = '-hls_allow_cache';
        $command[] = '1';

        // Output playlist
        $command[] = "{$outputDir}/playlist.m3u8";

        return $command;
    }
}
