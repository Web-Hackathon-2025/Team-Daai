<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'provider_id',
        'service_id',
        'status',
        'requested_date',
        'requested_time',
        'confirmed_date',
        'confirmed_time',
        'completed_at',
        'description',
        'address',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'confirmed_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_REQUESTED = 'requested';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the customer that made the request
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the provider for this request
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the service for this request
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the review for this request
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }

    /**
     * Scope to get confirmed requests
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope to get completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if request can be reviewed
     */
    public function canBeReviewed()
    {
        return $this->status === self::STATUS_COMPLETED && !$this->review;
    }
}
