<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Exception;

class StreamSyncController extends Controller
{
    use ResponseAPI;

    /**
     * Sync endpoint for gateway agents to poll
     * Returns list of active cameras that should be streaming
     *
     * @param int $businessId
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncConfig($businessId)
    {
        try {
            // Get all active cameras for this business
            $cameras = Camera::where('business_id', $businessId)
                ->where('is_active', 1)
                ->whereNotNull('rtsp_url')
                ->get(['id', 'name', 'rtsp_url', 'stream_key', 'rtsp_username', 'rtsp_password']);

            // Build complete RTSP URLs with credentials if needed
            $cameraList = $cameras->map(function ($camera) {
                $rtspUrl = $camera->rtsp_url;

                // Add credentials to RTSP URL if not already present and credentials exist
                if ($camera->rtsp_username && $camera->rtsp_password && !str_contains($rtspUrl, '@')) {
                    $rtspUrl = str_replace('rtsp://', "rtsp://{$camera->rtsp_username}:{$camera->rtsp_password}@", $rtspUrl);
                }

                return [
                    'id' => $camera->id,
                    'name' => $camera->name,
                    'rtsp_url' => $rtspUrl,
                    'stream_key' => $camera->stream_key,
                ];
            });

            return response()->json([
                'status' => 'success',
                'vps_push_url' => env('RTMP_PUSH_URL', 'rtmp://91.99.26.87:1935/live/'),
                'hls_base_url' => env('HLS_BASE_URL', 'http://91.99.26.87:8888/live/'),
                'cameras' => $cameraList,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update camera stream status (called by agent)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStreamStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'stream_key' => 'required|string',
                'status' => 'required|in:streaming,stopped,error',
                'error_message' => 'nullable|string',
            ]);

            $camera = Camera::where('stream_key', $validated['stream_key'])->first();

            if (!$camera) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Camera not found',
                ], 404);
            }

            $camera->stream_status = $validated['status'];
            if (isset($validated['error_message'])) {
                $camera->last_error = $validated['error_message'];
            }
            $camera->last_stream_check = now();
            $camera->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stream status updated',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
