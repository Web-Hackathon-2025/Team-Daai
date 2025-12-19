<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Business;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CameraController extends Controller
{
    use ResponseAPI, LogsActivity;

    /**
     * Get all cameras with filters
     *
     * @OA\Get(
     *     path="/api/cameras",
     *     tags={"Cameras"},
     *     summary="Get all cameras with filters",
     *     description="Retrieve paginated list of cameras with optional filters (status, protocol, server, location, streaming state, search)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="Filter by business ID (Super Admin only)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by camera status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"online", "offline", "unstable", "disabled"})
     *     ),
     *     @OA\Parameter(
     *         name="protocol",
     *         in="query",
     *         description="Filter by protocol",
     *         required=false,
     *         @OA\Schema(type="string", enum={"RTSP", "RTMP", "HTTP", "HTTPS"})
     *     ),
     *     @OA\Parameter(
     *         name="server",
     *         in="query",
     *         description="Filter by server name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_streaming",
     *         in="query",
     *         description="Filter by streaming state",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by camera name or manufacturer",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cameras loaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Cameras loaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="business_id", type="integer"),
     *                         @OA\Property(property="location_id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="manufacturer", type="string"),
     *                         @OA\Property(property="protocol", type="string"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="is_streaming", type="boolean"),
     *                         @OA\Property(
     *                             property="business",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         ),
     *                         @OA\Property(
     *                             property="location",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->hasRole('Super Admin')
            ? $request->get('business_id')
            : Auth::user()->business_id;

        $query = Camera::with(['business:id,name', 'location:id,name'])
            ->when($businessId, function($q) use ($businessId) {
                $q->forBusiness($businessId);
            });

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('protocol')) {
            $query->byProtocol($request->protocol);
        }

        if ($request->has('server')) {
            $query->byServer($request->server);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('is_streaming')) {
            $query->where('is_streaming', $request->is_streaming);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $query->active()->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $cameras = $query->paginate($perPage);

        return $this->success('Cameras loaded successfully', $cameras);
    }

    /**
     * Get camera statistics
     *
     * @OA\Get(
     *     path="/api/cameras/stats",
     *     tags={"Cameras"},
     *     summary="Get camera statistics",
     *     description="Retrieve camera counts by status and streaming state",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="Filter by business ID (Super Admin only)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camera statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera statistics retrieved"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="online", type="integer", example=45),
     *                 @OA\Property(property="offline", type="integer", example=3),
     *                 @OA\Property(property="unstable", type="integer", example=1),
     *                 @OA\Property(property="disabled", type="integer", example=1),
     *                 @OA\Property(property="streaming", type="integer", example=30)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function stats(Request $request)
    {
        $businessId = Auth::user()->hasRole('Super Admin')
            ? $request->get('business_id')
            : Auth::user()->business_id;

        $query = Camera::when($businessId, function($q) use ($businessId) {
            $q->forBusiness($businessId);
        })->active();

        $stats = [
            'total' => $query->count(),
            'online' => (clone $query)->where('status', 'online')->count(),
            'offline' => (clone $query)->where('status', 'offline')->count(),
            'unstable' => (clone $query)->where('status', 'unstable')->count(),
            'disabled' => (clone $query)->where('status', 'disabled')->count(),
            'streaming' => (clone $query)->where('is_streaming', true)->count(),
        ];

        return $this->success('Camera statistics retrieved', $stats);
    }

    /**
     * Get single camera by ID
     *
     * @OA\Get(
     *     path="/api/cameras/{id}",
     *     tags={"Cameras"},
     *     summary="Get single camera",
     *     description="Retrieve detailed information about a specific camera including stream URL",
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
     *         description="Camera retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="business_id", type="integer"),
     *                 @OA\Property(property="location_id", type="integer"),
     *                 @OA\Property(property="name", type="string", example="Front Gate Camera"),
     *                 @OA\Property(property="manufacturer", type="string", example="Hikvision"),
     *                 @OA\Property(property="protocol", type="string", example="RTSP"),
     *                 @OA\Property(property="rtsp_url", type="string"),
     *                 @OA\Property(property="status", type="string", example="online"),
     *                 @OA\Property(property="is_streaming", type="boolean"),
     *                 @OA\Property(property="full_stream_url", type="string"),
     *                 @OA\Property(property="resolution", type="string", example="1920x1080"),
     *                 @OA\Property(property="fps", type="integer", example=25),
     *                 @OA\Property(
     *                     property="business",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="location",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="address", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized access to this camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show($id)
    {
        $camera = Camera::with(['business:id,name', 'location:id,name,address'])
            ->active()
            ->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('Super Admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized access to this camera', 403);
        }

        // Add stream URL to response
        $camera->full_stream_url = $camera->getStreamUrl();

        return $this->success('Camera retrieved successfully', $camera);
    }

    /**
     * Create new camera
     *
     * @OA\Post(
     *     path="/api/cameras",
     *     tags={"Cameras"},
     *     summary="Create new camera",
     *     description="Add a new camera to the system. Checks camera limits based on subscription package.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "protocol"},
     *             @OA\Property(property="business_id", type="integer", example=1, description="Business ID (Super Admin only)"),
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Front Gate Camera"),
     *             @OA\Property(property="manufacturer", type="string", example="Hikvision"),
     *             @OA\Property(property="template", type="string", example="DS-2CD2xxx"),
     *             @OA\Property(property="protocol", type="string", enum={"RTSP", "RTMP", "HTTP", "HTTPS"}, example="RTSP"),
     *             @OA\Property(property="server", type="string", example="vms-server-01"),
     *             @OA\Property(property="rtsp_url", type="string", example="rtsp://192.168.1.100:554/stream1"),
     *             @OA\Property(property="rtsp_username", type="string", example="admin"),
     *             @OA\Property(property="rtsp_password", type="string", example="password123"),
     *             @OA\Property(property="rtsp_host", type="string", example="192.168.1.100"),
     *             @OA\Property(property="rtsp_port", type="integer", example=554),
     *             @OA\Property(property="rtsp_path", type="string", example="/stream1"),
     *             @OA\Property(property="rtmp_url", type="string", example="rtmp://server/live/stream"),
     *             @OA\Property(property="resolution", type="string", example="1920x1080"),
     *             @OA\Property(property="camera_type", type="string", example="Dome"),
     *             @OA\Property(property="analytical", type="boolean", example=false),
     *             @OA\Property(property="codec", type="string", example="H.264"),
     *             @OA\Property(property="fps", type="integer", example=25),
     *             @OA\Property(property="bitrate", type="integer", example=4096, description="Bitrate in Kbps"),
     *             @OA\Property(property="storage_days", type="integer", example=30, description="Retention period 1-365 days"),
     *             @OA\Property(property="pre_alarm_seconds", type="integer", example=10, description="Pre-alarm buffer 0-30 seconds"),
     *             @OA\Property(property="address", type="string", example="Main Entrance"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-23.550520),
     *             @OA\Property(property="longitude", type="number", format="float", example=-46.633308),
     *             @OA\Property(property="comments", type="string", example="24/7 monitoring required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Camera created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="stream_key", type="string", description="Auto-generated unique stream key")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Camera limit exceeded for this business package"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Failed to create camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id',
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'template' => 'nullable|string|max:255',
            'protocol' => 'required|in:RTSP,RTMP,HTTP,HTTPS',
            'server' => 'nullable|string|max:255',

            // RTSP fields
            'rtsp_url' => 'nullable|string',
            'rtsp_username' => 'nullable|string',
            'rtsp_password' => 'nullable|string',
            'rtsp_host' => 'nullable|string',
            'rtsp_port' => 'nullable|integer',
            'rtsp_path' => 'nullable|string',

            // RTMP fields
            'rtmp_url' => 'nullable|string',

            // Technical specs
            'resolution' => 'nullable|string',
            'camera_type' => 'nullable|string',
            'analytical' => 'nullable|boolean',
            'codec' => 'nullable|string',
            'fps' => 'nullable|integer',
            'bitrate' => 'nullable|integer',

            // Storage
            'storage_days' => 'nullable|integer|min:1|max:365',
            'pre_alarm_seconds' => 'nullable|integer|min:0|max:30',

            // Location
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            // Other
            'comments' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Set business_id
            $validated['business_id'] = Auth::user()->hasRole('Super Admin')
                ? $validated['business_id'] ?? Auth::user()->business_id
                : Auth::user()->business_id;
            $validated['analytical'] = $validated['analytical'] ?? false;

            // Check camera limit
            if (!$this->checkCameraLimit($validated['business_id'])) {
                return $this->error('Camera limit exceeded for this business package', 403);
            }

            // Set default status to online if RTSP or RTMP URL is provided
            if (!isset($validated['status']) && (isset($validated['rtsp_url']) || isset($validated['rtmp_url']))) {
                $validated['status'] = 'online';
            }

            // Create camera
            $camera = Camera::create($validated);

            // Calculate storage size
            if ($camera->bitrate && $camera->storage_days) {
                $camera->calculateStorageSize();
            }

            $this->logCameraActivity(
                action: 'create',
                description: "Created camera '{$camera->name}'",
                // modelId: $camera->id,
                changes: $validated
            );

            DB::commit();

            $camera->load(['business:id,name', 'location:id,name']);
            return $this->success('Camera created successfully', $camera, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create camera: ' . $e->getMessage());
            return $this->error('Failed to create camera. Please try again.', 500);
        }
    }

    /**
     * Update camera
     *
     * @OA\Put(
     *     path="/api/cameras/{id}",
     *     tags={"Cameras"},
     *     summary="Update camera",
     *     description="Update camera configuration including RTSP/RTMP settings, location, status, and technical specs",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "protocol"},
     *             @OA\Property(property="location_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="manufacturer", type="string"),
     *             @OA\Property(property="template", type="string"),
     *             @OA\Property(property="protocol", type="string", enum={"RTSP", "RTMP", "HTTP", "HTTPS"}),
     *             @OA\Property(property="server", type="string"),
     *             @OA\Property(property="rtsp_url", type="string"),
     *             @OA\Property(property="rtsp_username", type="string"),
     *             @OA\Property(property="rtsp_password", type="string"),
     *             @OA\Property(property="rtsp_host", type="string"),
     *             @OA\Property(property="rtsp_port", type="integer"),
     *             @OA\Property(property="rtsp_path", type="string"),
     *             @OA\Property(property="rtmp_url", type="string"),
     *             @OA\Property(property="resolution", type="string"),
     *             @OA\Property(property="camera_type", type="string"),
     *             @OA\Property(property="analytical", type="boolean"),
     *             @OA\Property(property="codec", type="string"),
     *             @OA\Property(property="fps", type="integer"),
     *             @OA\Property(property="bitrate", type="integer"),
     *             @OA\Property(property="storage_days", type="integer"),
     *             @OA\Property(property="pre_alarm_seconds", type="integer"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="status", type="string", enum={"online", "offline", "unstable", "disabled"}),
     *             @OA\Property(property="comments", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camera updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized to update this camera"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Failed to update camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request, $id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('Super Admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized to update this camera', 403);
        }

        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'name' => 'required|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'template' => 'nullable|string|max:255',
            'protocol' => 'required|in:RTSP,RTMP,HTTP,HTTPS',
            'server' => 'nullable|string|max:255',

            // RTSP fields
            'rtsp_url' => 'nullable|string',
            'rtsp_username' => 'nullable|string',
            'rtsp_password' => 'nullable|string',
            'rtsp_host' => 'nullable|string',
            'rtsp_port' => 'nullable|integer',
            'rtsp_path' => 'nullable|string',

            // RTMP fields
            'rtmp_url' => 'nullable|string',

            // Technical specs
            'resolution' => 'nullable|string',
            'camera_type' => 'nullable|string',
            'analytical' => 'nullable|boolean',
            'codec' => 'nullable|string',
            'fps' => 'nullable|integer',
            'bitrate' => 'nullable|integer',

            // Storage
            'storage_days' => 'nullable|integer|min:1|max:365',
            'pre_alarm_seconds' => 'nullable|integer|min:0|max:30',

            // Location
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            // Status
            'status' => 'nullable|in:online,offline,unstable,disabled',
            'comments' => 'nullable|string',
        ]);
        // dd($validated);
        try {
            DB::beginTransaction();

            // Set default status to online if RTSP or RTMP URL is provided and status not explicitly set
            if (!isset($validated['status']) && (isset($validated['rtsp_url']) || isset($validated['rtmp_url']))) {
                $validated['status'] = 'online';
            }
            $validated['analytical'] = $validated['analytical'] ?? false;

            $camera->update($validated);

            // Recalculate storage if bitrate or storage_days changed
            if ($camera->wasChanged(['bitrate', 'storage_days']) && $camera->bitrate && $camera->storage_days) {
                $camera->calculateStorageSize();
            }

            $this->logCameraActivity(
                action: 'update',
                description: "Updated camera '{$camera->name}'",
                // modelId: $camera->id,
                changes: $validated
            );

            DB::commit();

            $camera->load(['business:id,name', 'location:id,name']);
            return $this->success('Camera updated successfully', $camera);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update camera: ' . $e->getMessage());
            return $this->error('Failed to update camera. Please try again.', 500);
        }
    }

    /**
     * Update camera comments (for inline editing)
     *
     * @OA\Patch(
     *     path="/api/cameras/{id}/comments",
     *     tags={"Cameras"},
     *     summary="Update camera comments",
     *     description="Quick update of camera comments field only (for inline editing)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="comments", type="string", example="Updated maintenance notes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comments updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Comments updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized to update this camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function updateComments(Request $request, $id)
    {
        $camera = Camera::active()->find($id);
        // dd($camera);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        // if (!Auth::user()->hasRole('Super Admin') && $camera->business_id != Auth::user()->business_id) {
        //     return $this->error('Unauthorized to update this camera', 403);
        // }

        $validated = $request->validate([
            'comments' => 'nullable|string',
        ]);
        $camera->update($validated);

        $this->logCameraActivity(
            action: 'update_comments',
            description: "Updated comments for camera '{$camera->name}'"
        );

        return $this->success('Comments updated successfully', $camera);
    }

    /**
     * Migrate camera to different server
     *
     * @OA\Patch(
     *     path="/api/cameras/{id}/migrate",
     *     tags={"Cameras"},
     *     summary="Migrate camera to different server",
     *     description="Move camera processing to a different VMS server (Super Admin or business-admin only)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Camera ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"server"},
     *             @OA\Property(property="server", type="string", example="vms-server-02", description="Target server name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camera migrated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera migrated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized to migrate camera"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function migrateServer(Request $request, $id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation (only super admin or business admin can migrate)
        if (!Auth::user()->hasRole(['Super Admin', 'business-admin'])) {
            return $this->error('Unauthorized to migrate camera', 403);
        }

        if (!Auth::user()->hasRole('Super Admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized to migrate this camera', 403);
        }

        $validated = $request->validate([
            'server' => 'required|string|max:255',
        ]);

        $oldServer = $camera->server;
        $camera->update($validated);

        $this->logCameraActivity(
            action: 'migrate_server',
            description: "Migrated camera '{$camera->name}' from {$oldServer} to {$validated['server']}",
            modelId: $camera->id,
            changes: $validated
        );

        return $this->success('Camera migrated successfully', $camera);
    }

    /**
     * Delete camera
     *
     * @OA\Delete(
     *     path="/api/cameras/{id}",
     *     tags={"Cameras"},
     *     summary="Delete camera",
     *     description="Soft delete a camera from the system",
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
     *         description="Camera deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Camera not found"),
     *     @OA\Response(response=403, description="Unauthorized to delete this camera"),
     *     @OA\Response(response=500, description="Failed to delete camera"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function destroy($id)
    {
        $camera = Camera::active()->find($id);

        if (!$camera) {
            return $this->error('Camera not found', 404);
        }

        // Check business isolation
        if (!Auth::user()->hasRole('Super Admin') && $camera->business_id != Auth::user()->business_id) {
            return $this->error('Unauthorized to delete this camera', 403);
        }

        try {
            $cameraName = $camera->name;
            $camera->delete();

            $this->logCameraActivity(
                action: 'delete',
                description: "Deleted camera '{$cameraName}'"
            );

            return $this->success('Camera deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete camera: ' . $e->getMessage());
            return $this->error('Failed to delete camera. Please try again.', 500);
        }
    }

    /**
     * Get all cameras with coordinates for map display
     *
     * @OA\Get(
     *     path="/api/cameras/map",
     *     tags={"Cameras"},
     *     summary="Get cameras for map display",
     *     description="Retrieve all cameras with coordinates (latitude/longitude) for displaying markers on dashboard map",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="Filter by business ID (Super Admin only)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="location_id",
     *         in="query",
     *         description="Filter by location ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by camera status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"online", "offline", "unstable", "disabled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Camera map data loaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Camera map data loaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Front Gate Camera"),
     *                     @OA\Property(property="latitude", type="number", format="float", example=-23.550520),
     *                     @OA\Property(property="longitude", type="number", format="float", example=-46.633308),
     *                     @OA\Property(property="status", type="string", example="online"),
     *                     @OA\Property(property="is_streaming", type="boolean", example=true),
     *                     @OA\Property(property="manufacturer", type="string", example="Hikvision"),
     *                     @OA\Property(property="address", type="string", example="Main Entrance"),
     *                     @OA\Property(property="playback_url", type="string", example="http://91.99.26.87:8888/live/cam_abc123/index.m3u8"),
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
    public function getMapData(Request $request)
    {
        $businessId = Auth::user()->hasRole('Super Admin')
            ? $request->get('business_id')
            : Auth::user()->business_id;

        $query = Camera::with(['location:id,name'])
            ->select('id', 'name', 'latitude', 'longitude', 'status', 'is_streaming', 'manufacturer', 'address', 'location_id', 'stream_key')
            ->when($businessId, function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->active();

        // Apply filters
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $cameras = $query->get();

        return $this->success('Camera map data loaded successfully', $cameras);
    }

    /**
     * Get list of manufacturers for dropdown
     *
     * @OA\Get(
     *     path="/api/cameras/manufacturers",
     *     tags={"Cameras"},
     *     summary="Get camera manufacturers list",
     *     description="Retrieve predefined list of camera manufacturers for dropdown selection",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Manufacturers loaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Manufacturers loaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="string", example="Hikvision")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getManufacturers()
    {
        $manufacturers = [
            'Hikvision',
            'Dahua',
            'Axis',
            'Intelbras',
            'Tecvoz',
            'Vivotek',
            'Uniview',
            'LuxVision',
            'Sony',
            'Samsung',
            'Panasonic',
            'Bosch',
            'Honeywell',
            'Hanwha',
            'Pelco',
            'Avigilon',
            'Mobotix',
            'Geovision',
            'ACTi',
            'Arecont Vision',
        ];

        return $this->success('Manufacturers loaded successfully', $manufacturers);
    }

    /**
     * Helper: Check camera limit for business
     */
    private function checkCameraLimit($businessId)
    {
        $business = Business::with('subscription_package')->find($businessId);

        if (!$business || !$business->subscription_package) {
            return false;
        }

        $currentCount = Camera::where('business_id', $businessId)->active()->count();
        return $currentCount < $business->subscription_package->max_cameras;
    }
}
