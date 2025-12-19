<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'type',
        'status',
        'read_at',
        'sender_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who receives this notification
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who sent this notification
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope: Filter by type
     */
    public function scopeByType($query, $type)
    {
        if ($type !== 'all') {
            return $query->where('type', $type);
        }
        return $query;
    }

    /**
     * Scope: Search in subject and message
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope: Only unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    /**
     * Scope: Only read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    /**
     * Mark this notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Mark this notification as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'status' => 'unread',
            'read_at' => null,
        ]);
    }
}
