<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class NotificationController extends Controller
{
    use ResponseAPI;

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Get user notifications",
     *     description="Retrieve paginated list of notifications for authenticated user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Notifications retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);

            $notifications = Notification::forUser($user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->success('Notifications retrieved successfully', $notifications);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Get unread count",
     *     description="Get count of unread notifications for authenticated user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Unread count retrieved"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            $count = Notification::forUser($user->id)
                ->byStatus('unread')
                ->count();

            return $this->success('Unread count retrieved', ['count' => $count]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Get notification details",
     *     description="Get a single notification (automatically marks as read)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification retrieved"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $notification = Notification::forUser($user->id)->findOrFail($id);

            // Mark as read when viewed
            if ($notification->status === 'unread') {
                $notification->update(['status' => 'read']);
            }

            return $this->success('Notification retrieved successfully', $notification);
        } catch (Exception $e) {
            return $this->error('Notification not found', 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-as-read",
     *     tags={"Notifications"},
     *     summary="Mark notifications as read",
     *     description="Mark specific notifications as read by their IDs",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"notification_ids"},
     *             @OA\Property(property="notification_ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Notifications marked as read"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function markAsRead(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'exists:notifications,id'
            ]);

            $user = Auth::user();
            $updated = Notification::forUser($user->id)
                ->whereIn('id', $request->notification_ids)
                ->update(['status' => 'read']);

            return $this->success("$updated notifications marked as read");
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-all-as-read",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     description="Mark all unread notifications as read for authenticated user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="All notifications marked as read"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            $updated = Notification::forUser($user->id)
                ->where('status', 'unread')
                ->update(['status' => 'read']);

            return $this->success("All notifications marked as read ($updated notifications)");
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Delete notification",
     *     description="Delete a specific notification",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Notification deleted"),
     *     @OA\Response(response=404, description="Notification not found")
     * )
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $notification = Notification::forUser($user->id)->findOrFail($id);
            $notification->delete();

            return $this->success('Notification deleted successfully');
        } catch (Exception $e) {
            return $this->error('Notification not found', 404);
        }
    }
}
