<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'subject',
        'message',
        'recipient_type',
        'recipient_ids',
        'notification_type',
        'send_email',
        'recipients_count',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'send_email' => 'boolean',
        'recipients_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who sent this message
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope: Filter by sender
     */
    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * Scope: Filter by recipient type
     */
    public function scopeByRecipientType($query, $type)
    {
        if ($type !== 'all') {
            return $query->where('recipient_type', $type);
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
}
