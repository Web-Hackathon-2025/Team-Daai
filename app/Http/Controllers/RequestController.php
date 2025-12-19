<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    use ResponseAPI;

    /**
     * @OA\Get(
     *     path="/api/requests",
     *     tags={"Service Requests"},
     *     summary="Get all service requests",
     *     description="Retrieve service requests filtered by user role. Customers see their requests, providers see requests for them.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"requested", "confirmed", "completed", "cancelled"})),
     *     @OA\Response(response=200, description="Requests retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = ServiceRequest::with(['provider', 'customer', 'service']);

            // Filter based on role
            if ($user->hasRole('customer')) {
                $query->where('customer_id', $user->id);
            } elseif ($user->hasRole('service_provider')) {
                $query->where('provider_id', $user->id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $requests = $query->orderBy('created_at', 'desc')->get();

            $data = $requests->map(function ($req) use ($user) {
                $isCustomer = $user->hasRole('customer');

                return [
                    'id' => $req->id,
                    'provider_id' => $req->provider_id,
                    'provider_name' => $req->provider->name ?? null,
                    'customer_id' => $req->customer_id,
                    'customer_name' => $req->customer->name ?? null,
                    'service_id' => $req->service_id,
                    'service_name' => $req->service->name ?? null,
                    'status' => $req->status,
                    'requested_date' => $req->requested_date,
                    'requested_time' => $req->requested_time,
                    'confirmed_date' => $req->confirmed_date,
                    'confirmed_time' => $req->confirmed_time,
                    'description' => $req->description,
                    'address' => $req->address,
                    'rejection_reason' => $req->rejection_reason,
                    'created_at' => $req->created_at->toISOString(),
                    'updated_at' => $req->updated_at->toISOString(),
                ];
            });

            return $this->success('Requests retrieved successfully', ['requests' => $data]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve requests: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/requests",
     *     tags={"Service Requests"},
     *     summary="Create new service request",
     *     description="Customer creates a booking request for a service provider",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"provider_id", "service_id", "requested_date", "requested_time", "address"},
     *             @OA\Property(property="provider_id", type="integer", example=2),
     *             @OA\Property(property="service_id", type="integer", example=1),
     *             @OA\Property(property="requested_date", type="string", format="date", example="2025-12-25"),
     *             @OA\Property(property="requested_time", type="string", example="10:00 AM"),
     *             @OA\Property(property="description", type="string", example="Need electrical wiring repair"),
     *             @OA\Property(property="address", type="string", example="123 Main St, New York, NY")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Service request created successfully"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'requested_date' => 'required|date|after_or_equal:today',
            'requested_time' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            // Verify provider is a service provider
            $provider = User::findOrFail($request->provider_id);
            if (!$provider->hasRole('service_provider')) {
                return $this->error('Invalid provider', 400);
            }

            $serviceRequest = ServiceRequest::create([
                'customer_id' => Auth::id(),
                'provider_id' => $request->provider_id,
                'service_id' => $request->service_id,
                'status' => 'requested',
                'requested_date' => $request->requested_date,
                'requested_time' => $request->requested_time,
                'description' => $request->description,
                'address' => $request->address,
            ]);

            $serviceRequest->load(['provider', 'service']);

            return $this->success('Service request created successfully', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'provider_id' => $serviceRequest->provider_id,
                    'service_id' => $serviceRequest->service_id,
                    'status' => $serviceRequest->status,
                    'requested_date' => $serviceRequest->requested_date,
                    'requested_time' => $serviceRequest->requested_time,
                    'description' => $serviceRequest->description,
                    'address' => $serviceRequest->address,
                    'created_at' => $serviceRequest->created_at->toISOString(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/requests/{id}",
     *     tags={"Service Requests"},
     *     summary="Get request details",
     *     description="Get detailed information about a specific service request",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Request details retrieved"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Request not found")
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $serviceRequest = ServiceRequest::with(['provider', 'customer', 'service'])->findOrFail($id);

            // Check authorization
            if ($user->hasRole('customer') && $serviceRequest->customer_id !== $user->id) {
                return $this->error('Unauthorized', 403);
            }
            if ($user->hasRole('service_provider') && $serviceRequest->provider_id !== $user->id) {
                return $this->error('Unauthorized', 403);
            }

            $data = [
                'id' => $serviceRequest->id,
                'provider_id' => $serviceRequest->provider_id,
                'provider_name' => $serviceRequest->provider->name ?? null,
                'customer_id' => $serviceRequest->customer_id,
                'customer_name' => $serviceRequest->customer->name ?? null,
                'service_id' => $serviceRequest->service_id,
                'service_name' => $serviceRequest->service->name ?? null,
                'service_price' => $serviceRequest->service->price ?? null,
                'status' => $serviceRequest->status,
                'requested_date' => $serviceRequest->requested_date,
                'requested_time' => $serviceRequest->requested_time,
                'confirmed_date' => $serviceRequest->confirmed_date,
                'confirmed_time' => $serviceRequest->confirmed_time,
                'description' => $serviceRequest->description,
                'address' => $serviceRequest->address,
                'rejection_reason' => $serviceRequest->rejection_reason,
                'created_at' => $serviceRequest->created_at->toISOString(),
                'updated_at' => $serviceRequest->updated_at->toISOString(),
            ];

            return $this->success('Request details retrieved successfully', ['request' => $data]);
        } catch (\Exception $e) {
            return $this->error('Request not found', 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/requests/{id}/accept",
     *     tags={"Service Requests"},
     *     summary="Accept service request",
     *     description="Service provider accepts a requested booking",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Request accepted"),
     *     @OA\Response(response=400, description="Invalid status transition"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function accept($id)
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            if ($serviceRequest->provider_id !== Auth::id()) {
                return $this->error('Unauthorized', 403);
            }

            if ($serviceRequest->status !== 'requested') {
                return $this->error('Request cannot be accepted in current status', 400);
            }

            $serviceRequest->update([
                'status' => 'confirmed',
                'confirmed_date' => $serviceRequest->requested_date,
                'confirmed_time' => $serviceRequest->requested_time,
            ]);

            return $this->success('Request accepted successfully', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                    'confirmed_date' => $serviceRequest->confirmed_date,
                    'confirmed_time' => $serviceRequest->confirmed_time,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to accept request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/requests/{id}/reject",
     *     tags={"Service Requests"},
     *     summary="Reject service request",
     *     description="Service provider rejects a requested booking",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", example="Not available on requested date")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Request rejected"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function reject(Request $request, $id)
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            if ($serviceRequest->provider_id !== Auth::id()) {
                return $this->error('Unauthorized', 403);
            }

            if (!in_array($serviceRequest->status, ['requested', 'confirmed'])) {
                return $this->error('Request cannot be rejected in current status', 400);
            }

            $serviceRequest->update([
                'status' => 'cancelled',
                'rejection_reason' => $request->input('reason', 'Provider rejected the request'),
            ]);

            return $this->success('Request rejected successfully', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                    'rejection_reason' => $serviceRequest->rejection_reason,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to reject request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/requests/{id}/reschedule",
     *     tags={"Service Requests"},
     *     summary="Reschedule service request",
     *     description="Service provider reschedules a booking to a new date/time",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"new_date", "new_time"},
     *             @OA\Property(property="new_date", type="string", format="date", example="2025-12-26"),
     *             @OA\Property(property="new_time", type="string", example="2:00 PM"),
     *             @OA\Property(property="reason", type="string", example="Previous booking conflict")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Request rescheduled"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function reschedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_date' => 'required|date|after_or_equal:today',
            'new_time' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            if ($serviceRequest->provider_id !== Auth::id()) {
                return $this->error('Unauthorized', 403);
            }

            if (!in_array($serviceRequest->status, ['requested', 'confirmed'])) {
                return $this->error('Request cannot be rescheduled in current status', 400);
            }

            $serviceRequest->update([
                'confirmed_date' => $request->new_date,
                'confirmed_time' => $request->new_time,
                'status' => 'confirmed',
            ]);

            return $this->success('Request rescheduled successfully', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                    'confirmed_date' => $serviceRequest->confirmed_date,
                    'confirmed_time' => $serviceRequest->confirmed_time,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to reschedule request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/requests/{id}/complete",
     *     tags={"Service Requests"},
     *     summary="Mark request as completed",
     *     description="Service provider marks a confirmed request as completed",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Request marked as completed"),
     *     @OA\Response(response=400, description="Invalid status"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function complete($id)
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            if ($serviceRequest->provider_id !== Auth::id()) {
                return $this->error('Unauthorized', 403);
            }

            if ($serviceRequest->status !== 'confirmed') {
                return $this->error('Only confirmed requests can be completed', 400);
            }

            $serviceRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $this->success('Request marked as completed', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                    'completed_at' => $serviceRequest->completed_at,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to complete request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/requests/{id}/cancel",
     *     tags={"Service Requests"},
     *     summary="Cancel service request",
     *     description="Customer or provider can cancel a requested/confirmed booking",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Request cancelled"),
     *     @OA\Response(response=400, description="Invalid status"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            $isCustomer = $user->hasRole('customer') && $serviceRequest->customer_id === $user->id;
            $isProvider = $user->hasRole('service_provider') && $serviceRequest->provider_id === $user->id;

            if (!$isCustomer && !$isProvider) {
                return $this->error('Unauthorized', 403);
            }

            if (!in_array($serviceRequest->status, ['requested', 'confirmed'])) {
                return $this->error('Request cannot be cancelled in current status', 400);
            }

            $serviceRequest->update([
                'status' => 'cancelled',
                'rejection_reason' => $isCustomer ? 'Cancelled by customer' : 'Cancelled by provider',
            ]);

            return $this->success('Request cancelled successfully', [
                'request' => [
                    'id' => $serviceRequest->id,
                    'status' => $serviceRequest->status,
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to cancel request: ' . $e->getMessage(), 500);
        }
    }
}
