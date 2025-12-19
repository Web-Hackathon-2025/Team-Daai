<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StreamController extends Controller
{
    use ResponseAPI, LogsActivity;

    /**
     * Start camera stream conversion to HLS
     *
     * @OA\Post(
     *     path="/api/stream/{id}/start",
     *     tags={"Streaming"},
     *     summary="Start camera stream",
     *     description="Start FFmpeg process to convert RTSP/RTMP to HLS format for web playback",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stream started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Stream started successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="camera_id", type="integer", example=1),
     *                 @OA\Property(property="stream_key", type="string", example="abc123def456"),
     *                 @OA\Property(property="hls_url", type="string", example="https://api.example.com/storage/streams/1/index.m3u8"),
     *                 @OA\Property(property="is_streaming", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Camera is already streaming"),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera"),
     *     @OA\Response(response=500, description="Failed to start stream"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function start($id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('super-admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized access to this camera', 403);
        }

        // Check if already streaming
        if ($camera->is_streaming) {
            return $this->error('Camera is already streaming', 400);
        }

        try {
            // Start FFmpeg process via artisan command
            Artisan::call('stream:camera', ['camera_id' => $camera->id]);

            $camera->refresh();

            $this->logCameraActivity(
                action: 'start_stream',
                description: "Started stream for camera '{$camera->name}'",
                modelId: $camera->id
            );

            return $this->success('Stream started successfully', [
                'camera_id' => $camera->id,
                'stream_key' => $camera->stream_key,
                'hls_url' => $camera->hls_url,
                'is_streaming' => $camera->is_streaming,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start camera stream: ' . $e->getMessage());
            return $this->error('Failed to start stream. Please try again.', 500);
        }
    }

    /**
     * Stop camera stream
     *
     * @OA\Post(
     *     path="/api/stream/{id}/stop",
     *     tags={"Streaming"},
     *     summary="Stop camera stream",
     *     description="Stop FFmpeg streaming process and cleanup HLS files",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stream stopped successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Stream stopped successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="camera_id", type="integer", example=1),
     *                 @OA\Property(property="is_streaming", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Camera is not streaming"),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera"),
     *     @OA\Response(response=500, description="Failed to stop stream"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function stop($id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('super-admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized access to this camera', 403);
        }

        // Check if actually streaming
        if (!$camera->is_streaming) {
            return $this->error('Camera is not streaming', 400);
        }

        try {
            // Stop FFmpeg process
            Artisan::call('stream:camera', [
                'camera_id' => $camera->id,
                '--stop' => true
            ]);

            $camera->refresh();

            $this->logCameraActivity(
                action: 'stop_stream',
                description: "Stopped stream for camera '{$camera->name}'",
                modelId: $camera->id
            );

            return $this->success('Stream stopped successfully', [
                'camera_id' => $camera->id,
                'is_streaming' => $camera->is_streaming,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop camera stream: ' . $e->getMessage());
            return $this->error('Failed to stop stream. Please try again.', 500);
        }
    }

    /**
     * Get camera stream status
     *
     * @OA\Get(
     *     path="/api/stream/{id}/status",
     *     tags={"Streaming"},
     *     summary="Get stream status",
     *     description="Check if camera stream is active and retrieve stream details including process status",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stream status retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Stream status retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="camera_id", type="integer", example=1),
     *                 @OA\Property(property="camera_name", type="string", example="Front Gate Camera"),
     *                 @OA\Property(property="is_streaming", type="boolean", example=true),
     *                 @OA\Property(property="stream_pid", type="integer", example=12345, description="FFmpeg process ID"),
     *                 @OA\Property(property="process_running", type="boolean", example=true),
     *                 @OA\Property(property="hls_url", type="string"),
     *                 @OA\Property(property="stream_key", type="string"),
     *                 @OA\Property(property="last_seen_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function status($id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('super-admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized access to this camera', 403);
        }

        $pidFile = storage_path("app/streams/{$camera->id}.pid");
        $isProcessRunning = false;

        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);

            // Check if process is actually running
            if (PHP_OS_FAMILY === 'Windows') {
                exec("tasklist /FI \"PID eq {$pid}\" 2>NUL | find \"{$pid}\" >NUL", $output, $result);
                $isProcessRunning = ($result === 0);
            } else {
                $isProcessRunning = file_exists("/proc/{$pid}");
            }

            // Update database if process status doesn't match
            if (!$isProcessRunning && $camera->is_streaming) {
                $camera->stopStreaming();
            }
        }

        return $this->success('Stream status retrieved', [
            'camera_id' => $camera->id,
            'camera_name' => $camera->name,
            'is_streaming' => $camera->is_streaming,
            'stream_pid' => $camera->stream_pid,
            'process_running' => $isProcessRunning,
            'hls_url' => $camera->hls_url,
            'stream_key' => $camera->stream_key,
            'last_seen_at' => $camera->last_seen_at,
        ]);
    }

    /**
     * Serve HLS playlist or segments
     *
     * @OA\Get(
     *     path="/api/stream/{id}/serve/{file}",
     *     tags={"Streaming"},
     *     summary="Serve HLS files",
     *     description="Serve HLS playlist (.m3u8) or video segments (.ts) for video playback",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="file",
     *         in="path",
     *         description="Filename (index.m3u8 or segment.ts)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="HLS file content",
     *         @OA\MediaType(
     *             mediaType="application/vnd.apple.mpegurl",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found or Stream file not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera stream"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function serve($id, $file)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            abort(404, 'Camera not found');
        }

        // Check business isolation
        if (!Auth::user()->hasRole('super-admin') && $camera->business_id != Auth::user()->business_id) {
            abort(403, 'Unauthorized access to this camera stream');
        }

        $path = "streams/{$camera->id}/{$file}";

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Stream file not found');
        }

        $mimeTypes = [
            'm3u8' => 'application/vnd.apple.mpegurl',
            'ts' => 'video/mp2t',
        ];

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        return response()
            ->file(storage_path("app/public/{$path}"), [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    }

    /**
     * Get all streaming cameras
     *
     * @OA\Get(
     *     path="/api/stream/active",
     *     tags={"Streaming"},
     *     summary="Get all streaming cameras",
     *     description="Retrieve list of all cameras currently streaming",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="Filter by business ID (super-admin only)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Streaming cameras retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Streaming cameras retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="is_streaming", type="boolean", example=true),
     *                     @OA\Property(property="hls_url", type="string"),
     *                     @OA\Property(
     *                         property="business",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(
     *                         property="location",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getStreamingCameras(Request $request)
    {
        $businessId = Auth::user()->hasRole('super-admin')
            ? $request->get('business_id')
            : Auth::user()->business_id;

        $query = Camera::with(['business:id,name', 'location:id,name'])
            ->when($businessId, function($q) use ($businessId) {
                $q->forBusiness($businessId);
            })
            ->streaming()
            ->active();

        $cameras = $query->get();

        return $this->success('Streaming cameras retrieved', $cameras);
    }

    /**
     * Restart camera stream (stop then start)
     *
     * @OA\Post(
     *     path="/api/stream/{id}/restart",
     *     tags={"Streaming"},
     *     summary="Restart camera stream",
     *     description="Stop and restart FFmpeg streaming process (useful for recovering from errors)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stream restarted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Stream restarted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="camera_id", type="integer", example=1),
     *                 @OA\Property(property="is_streaming", type="boolean", example=true),
     *                 @OA\Property(property="hls_url", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera"),
     *     @OA\Response(response=500, description="Failed to restart stream"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function restart($id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('super-admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized access to this camera', 403);
        }

        try {
            // Stop if streaming
            if ($camera->is_streaming) {
                Artisan::call('stream:camera', [
                    'camera_id' => $camera->id,
                    '--stop' => true
                ]);
                sleep(2); // Wait for graceful shutdown
            }

            // Start stream
            Artisan::call('stream:camera', ['camera_id' => $camera->id]);

            $camera->refresh();

            $this->logCameraActivity(
                action: 'restart_stream',
                description: "Restarted stream for camera '{$camera->name}'",
                modelId: $camera->id
            );

            return $this->success('Stream restarted successfully', [
                'camera_id' => $camera->id,
                'is_streaming' => $camera->is_streaming,
                'hls_url' => $camera->hls_url,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restart camera stream: ' . $e->getMessage());
            return $this->error('Failed to restart stream. Please try again.', 500);
        }
    }
}
