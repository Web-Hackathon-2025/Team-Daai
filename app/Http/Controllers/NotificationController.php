<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\SentMessage;
use App\Models\User;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    use ResponseAPI, LogsActivity;

    /**
     * Send notification to users
     * Super Admins can send to anyone, Business Admins can only send to their business users
     */
    public function send(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $isBusinessAdmin = $user->hasRole('Business Admin');

        // Check if user has permission to send notifications
        if (!$isSuperAdmin && !$isBusinessAdmin) {
            return $this->error('Unauthorized. Only Admins can send notifications.', 403);
        }

        // Validate based on user role
        $recipientTypes = $isSuperAdmin
            ? 'all,super_admins,businesses,business_users,specific'
            : 'business_users,specific';

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'recipient_type' => "required|in:{$recipientTypes}",
            'recipient_ids' => 'nullable|array',
            'recipient_ids.*' => 'exists:users,id',
            'notification_type' => 'nullable|in:info,warning,alert,system',
            'send_email' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Get recipients based on type and user role
            $recipients = $this->getRecipients(
                $validated['recipient_type'],
                $validated['recipient_ids'] ?? [],
                $isSuperAdmin ? null : $user->business_id
            );

            if (empty($recipients)) {
                return $this->error('No recipients found for the selected criteria.', 400);
            }

            // Create sent message record
            $sentMessage = SentMessage::create([
                'sender_id' => Auth::id(),
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'recipient_type' => $validated['recipient_type'],
                'recipient_ids' => $validated['recipient_ids'] ?? null,
                'notification_type' => $validated['notification_type'] ?? 'info',
                'send_email' => $validated['send_email'] ?? false,
                'recipients_count' => count($recipients),
            ]);

            // Create notifications for each recipient
            $notificationsData = [];
            foreach ($recipients as $userId) {
                $notificationsData[] = [
                    'user_id' => $userId,
                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'type' => $validated['notification_type'] ?? 'info',
                    'sender_id' => Auth::id(),
                    'status' => 'unread',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert notifications for performance
            Notification::insert($notificationsData);

            // Log activity
            $this->logActivity(
                category: 'notification',
                action: 'send',
                description: "Sent notification '{$validated['subject']}' to {$sentMessage->recipients_count} users",
                // modelType: SentMessage::class,
                // modelId: $sentMessage->id,
                changes: [
                    'subject' => $validated['subject'],
                    'recipient_type' => $validated['recipient_type'],
                    'recipients_count' => $sentMessage->recipients_count,
                ]
            );

            DB::commit();

            return $this->success(
                "Message sent successfully to {$sentMessage->recipients_count} users",
                $sentMessage,
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send notification: ' . $e->getMessage());
            return $this->error('Failed to send notification. Please try again.', 500);
        }
    }

    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $query = Notification::with('sender:id,first_name,last_name,email')
            ->forUser($userId);

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 25);
        $notifications = $query->paginate($perPage);

        // Get unread count
        $unreadCount = Notification::forUser($userId)->unread()->count();

        $data = $notifications->toArray();
        $data['unread_count'] = $unreadCount;

        return $this->success('Notifications loaded successfully', $data);
    }

    /**
     * Get notification by ID
     */
    public function show($id)
    {
        $userId = Auth::id();

        $notification = Notification::with('sender:id,first_name,last_name,email')
            ->forUser($userId)
            ->find($id);

        if (!$notification) {
            return $this->error('Notification not found', 404);
        }

        // Auto-mark as read when viewing
        if ($notification->status === 'unread') {
            $notification->markAsRead();
        }

        return $this->success('Notification retrieved successfully', $notification);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'id' => 'nullable|exists:notifications,id',
            'ids' => 'nullable|array',
            'ids.*' => 'exists:notifications,id',
        ]);

        try {
            if (isset($validated['ids'])) {
                // Bulk mark as read
                Notification::forUser($userId)
                    ->whereIn('id', $validated['ids'])
                    ->update([
                        'status' => 'read',
                        'read_at' => now(),
                    ]);

                $this->logActivity(
                    category: 'notification',
                    action: 'mark_read',
                    description: 'Marked ' . count($validated['ids']) . ' notifications as read'
                );
            } elseif (isset($validated['id'])) {
                // Single mark as read
                $notification = Notification::forUser($userId)->find($validated['id']);

                if (!$notification) {
                    return $this->error('Notification not found', 404);
                }

                $notification->markAsRead();

                $this->logActivity(
                    category: 'notification',
                    action: 'mark_read',
                    description: "Marked notification '{$notification->subject}' as read"
                );
            }

            return $this->success('Notification(s) marked as read');
        } catch (\Exception $e) {
            Log::error('Failed to mark notifications as read: ' . $e->getMessage());
            return $this->error('Failed to mark notifications as read', 500);
        }
    }

    /**
     * Mark notifications as unread
     */
    public function markAsUnread(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'id' => 'nullable|exists:notifications,id',
            'ids' => 'nullable|array',
            'ids.*' => 'exists:notifications,id',
        ]);

        try {
            if (isset($validated['ids'])) {
                // Bulk mark as unread
                Notification::forUser($userId)
                    ->whereIn('id', $validated['ids'])
                    ->update([
                        'status' => 'unread',
                        'read_at' => null,
                    ]);

                $this->logActivity(
                    category: 'notification',
                    action: 'mark_unread',
                    description: 'Marked ' . count($validated['ids']) . ' notifications as unread'
                );
            } elseif (isset($validated['id'])) {
                // Single mark as unread
                $notification = Notification::forUser($userId)->find($validated['id']);

                if (!$notification) {
                    return $this->error('Notification not found', 404);
                }

                $notification->markAsUnread();

                $this->logActivity(
                    category: 'notification',
                    action: 'mark_unread',
                    description: "Marked notification '{$notification->subject}' as unread"
                );
            }

            return $this->success('Notification(s) marked as unread');
        } catch (\Exception $e) {
            Log::error('Failed to mark notifications as unread: ' . $e->getMessage());
            return $this->error('Failed to mark notifications as unread', 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $userId = Auth::id();

        try {
            $count = Notification::forUser($userId)
                ->unread()
                ->update([
                    'status' => 'read',
                    'read_at' => now(),
                ]);

            $this->logActivity(
                category: 'notification',
                action: 'mark_all_read',
                description: "Marked all {$count} notifications as read"
            );

            return $this->success('All notifications marked as read', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
            return $this->error('Failed to mark all notifications as read', 500);
        }
    }

    /**
     * Delete notifications
     */
    public function delete(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'id' => 'nullable|exists:notifications,id',
            'ids' => 'nullable|array',
            'ids.*' => 'exists:notifications,id',
        ]);
        try {
            if (isset($validated['ids'])) {
                // Bulk delete
                $count = Notification::forUser($userId)
                    ->whereIn('id', $validated['ids'])
                    ->delete();

                $this->logActivity(
                    category: 'notification',
                    action: 'delete',
                    description: "Deleted {$count} notifications"
                );
            } elseif (isset($validated['id'])) {
                // Single delete
                $notification = Notification::find($validated['id']);


                if (!$notification) {
                    return $this->error('Notification not found', 404);
                }

                $subject = $notification->subject;
                $notification->delete();

                $this->logActivity(
                    category: 'notification',
                    action: 'delete',
                    description: "Deleted notification '{$subject}'"
                );
            }

            return $this->success('Notification(s) deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete notifications: ' . $e->getMessage());
            return $this->error('Failed to delete notifications', 500);
        }
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead()
    {
        $userId = Auth::id();

        try {
            $count = Notification::forUser($userId)
                ->read()
                ->delete();

            $this->logActivity(
                category: 'notification',
                action: 'delete_all_read',
                description: "Deleted all {$count} read notifications"
            );

            return $this->success('All read notifications deleted successfully', ['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to delete read notifications: ' . $e->getMessage());
            return $this->error('Failed to delete read notifications', 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        $userId = Auth::id();
        $count = Notification::forUser($userId)->unread()->count();

        return $this->success('Unread count retrieved', ['count' => $count]);
    }

    /**
     * Get sent message history
     * Super Admins and Business Admins can view their sent messages
     */
    public function getSentMessages(Request $request)
    {
        $user = Auth::user();

        // Check if user has permission to view sent messages
        if (!$user->hasRole(['Super Admin', 'Business Admin'])) {
            return $this->error('Unauthorized. Only Admins can view sent messages.', 403);
        }

        $query = SentMessage::with('sender:id,first_name,last_name,email')
            ->bySender(Auth::id())
            ->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('recipient_type') && $request->recipient_type !== 'all') {
            $query->byRecipientType($request->recipient_type);
        }

        $perPage = $request->get('per_page', 10);
        $messages = $query->paginate($perPage);

        return $this->success('Message history loaded successfully', $messages);
    }

    /**
     * Get sent message by ID
     * Super Admins and Business Admins can view their sent messages
     */
    public function showSentMessage($id)
    {
        $user = Auth::user();

        // Check if user has permission to view sent messages
        if (!$user->hasRole(['Super Admin', 'Business Admin'])) {
            return $this->error('Unauthorized. Only Admins can view sent messages.', 403);
        }

        $message = SentMessage::with('sender:id,first_name,last_name,email')
            ->bySender(Auth::id())
            ->find($id);

        if (!$message) {
            return $this->error('Message not found', 404);
        }

        return $this->success('Message retrieved successfully', $message);
    }

    /**
     * Delete sent message
     * Only Super Admins can delete sent messages
     */
    public function deleteSentMessage(Request $request)
    {
        // Check if user is super admin
        if (!Auth::user()->hasRole('Super Admin')) {
            return $this->error('Unauthorized. Only Super Admins can delete sent messages.', 403);
        }

        $validated = $request->validate([
            'id' => 'required|exists:sent_messages,id',
        ]);

        try {
            $message = SentMessage::bySender(Auth::id())->find($validated['id']);

            if (!$message) {
                return $this->error('Message not found', 404);
            }

            $subject = $message->subject;
            $message->delete();

            $this->logActivity(
                category: 'notification',
                action: 'delete_sent_message',
                description: "Deleted sent message '{$subject}'"
            );

            return $this->success('Message deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete sent message: ' . $e->getMessage());
            return $this->error('Failed to delete sent message', 500);
        }
    }

    /**
     * Helper: Get recipient user IDs based on type
     * If businessId is provided, filter users by that business
     */
    private function getRecipients($type, $specificIds = [], $businessId = null)
    {
        switch ($type) {
            case 'all':
                // Only Super Admin can send to all
                return User::pluck('id')->toArray();

            case 'super_admins':
                // Only Super Admin can send to super admins
                return User::role('Super Admin')->pluck('id')->toArray();

            case 'businesses':
                // Only Super Admin can send to business admins
                return User::role('Business Admin')->pluck('id')->toArray();

            case 'business_users':
                // Business Admin: only their business users
                // Super Admin: all business users
                $query = User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['Manager', 'Viewer', 'User']);
                });

                if ($businessId) {
                    $query->where('business_id', $businessId);
                }

                return $query->pluck('id')->toArray();

            case 'specific':
                // Business Admin: only users from their business
                // Super Admin: any users
                if ($businessId && !empty($specificIds)) {
                    return User::whereIn('id', $specificIds)
                        ->where('business_id', $businessId)
                        ->pluck('id')
                        ->toArray();
                }
                return $specificIds ?? [];

            default:
                return [];
        }
    }

    /**
     * Get users list for notification dropdown (Business Admin only sees their users)
     */
    public function getUsersForNotification()
    {
        $user = Auth::user();

        if (!$user->hasRole(['Super Admin', 'Business Admin'])) {
            return $this->error('Unauthorized', 403);
        }

        $query = User::select('id', 'first_name', 'last_name', 'email', 'business_id')
            ->with('roles:name');

        // Business Admin can only see their business users
        if (!$user->hasRole('Super Admin')) {
            $query->where('business_id', $user->business_id);
        }

        $users = $query->orderBy('first_name')->get();

        return $this->success('Users loaded successfully', $users);
    }
}
