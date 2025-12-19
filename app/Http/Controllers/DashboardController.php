<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\Review;
use App\Models\Notification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardController extends Controller
{
    use ResponseAPI;

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get dashboard statistics",
     *     description="Get dashboard stats based on user role (admin: platform stats, provider: earnings/requests, customer: bookings)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Statistics retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getStats()
    {
        try {
            $user = Auth::user();
            
            // Admin Dashboard
            if ($user->hasRole('admin')) {
                $stats = [
                    'total_customers' => User::role('customer')->count(),
                    'total_providers' => User::role('service_provider')->count(),
                    'pending_approvals' => User::role('service_provider')
                        ->whereHas('providerProfile', function($q) {
                            $q->where('is_approved', false);
                        })->count(),
                    'total_requests' => ServiceRequest::count(),
                    'completed_requests' => ServiceRequest::where('status', 'completed')->count(),
                    'total_revenue' => ServiceRequest::where('status', 'completed')
                        ->sum('price'),
                    'average_rating' => Review::avg('rating'),
                    'unread_notifications' => Notification::forUser($user->id)->byStatus('unread')->count(),
                ];
            }
            // Service Provider Dashboard
            elseif ($user->hasRole('service_provider')) {
                $stats = [
                    'total_requests' => ServiceRequest::where('provider_id', $user->id)->count(),
                    'pending_requests' => ServiceRequest::where('provider_id', $user->id)
                        ->where('status', 'requested')->count(),
                    'confirmed_requests' => ServiceRequest::where('provider_id', $user->id)
                        ->where('status', 'confirmed')->count(),
                    'completed_requests' => ServiceRequest::where('provider_id', $user->id)
                        ->where('status', 'completed')->count(),
                    'total_earnings' => ServiceRequest::where('provider_id', $user->id)
                        ->where('status', 'completed')->sum('price'),
                    'average_rating' => Review::where('provider_id', $user->id)->avg('rating'),
                    'total_reviews' => Review::where('provider_id', $user->id)->count(),
                    'unread_notifications' => Notification::forUser($user->id)->byStatus('unread')->count(),
                ];
            }
            // Customer Dashboard
            else {
                $stats = [
                    'total_requests' => ServiceRequest::where('customer_id', $user->id)->count(),
                    'pending_requests' => ServiceRequest::where('customer_id', $user->id)
                        ->where('status', 'requested')->count(),
                    'confirmed_requests' => ServiceRequest::where('customer_id', $user->id)
                        ->where('status', 'confirmed')->count(),
                    'completed_requests' => ServiceRequest::where('customer_id', $user->id)
                        ->where('status', 'completed')->count(),
                    'total_spent' => ServiceRequest::where('customer_id', $user->id)
                        ->where('status', 'completed')->sum('price'),
                    'unread_notifications' => Notification::forUser($user->id)->byStatus('unread')->count(),
                ];
            }

            return $this->success('Dashboard stats loaded successfully', $stats);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
    /**
     * Get recent activity for dashboard
     */
    public function getRecentActivity()
    {
        try {
            $user = Auth::user();
            $limit = 10;

            if ($user->hasRole('admin')) {
                // Admin sees recent service requests across platform
                $recent = ServiceRequest::with(['customer:id,first_name,last_name', 'provider:id,first_name,last_name', 'service:id,name'])
                    ->latest()
                    ->limit($limit)
                    ->get();
            } elseif ($user->hasRole('service_provider')) {
                // Provider sees their recent requests
                $recent = ServiceRequest::with(['customer:id,first_name,last_name', 'service:id,name'])
                    ->where('provider_id', $user->id)
                    ->latest()
                    ->limit($limit)
                    ->get();
            } else {
                // Customer sees their recent requests
                $recent = ServiceRequest::with(['provider:id,first_name,last_name', 'service:id,name'])
                    ->where('customer_id', $user->id)
                    ->latest()
                    ->limit($limit)
                    ->get();
            }

            return $this->success('Recent activity retrieved successfully', $recent, 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }}
