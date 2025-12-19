<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Camera extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'location_id',

        // Basic Information
        'name',
        'manufacturer',
        'template',
        'protocol',
        'server',

        // Connection Details (RTSP)
        'rtsp_url',
        'rtsp_username',
        'rtsp_password',
        'rtsp_host',
        'rtsp_port',
        'rtsp_path',

        // Connection Details (RTMP)
        'rtmp_url',

        // Technical Specifications
        'resolution',
        'camera_type',
        'analytical',
        'codec',
        'fps',
        'bitrate',

        // Storage & Recording Plan
        'storage_days',
        'pre_alarm_seconds',
        'storage_size_gb',

        // Location Details
        'address',
        'latitude',
        'longitude',

        // Status & Monitoring
        'status',
        'last_seen_at',
        'comments',

        // Stream Management
        'stream_key',
        'hls_url',
        'is_streaming',
        'stream_pid',
        'stream_status',
        'last_error',
        'last_stream_check',

        // Metadata
        'metadata',
        'is_active',
    ];

    protected $hidden = [
        'rtsp_password',
        'deleted_at'
    ];

    protected $casts = [
        'analytical' => 'boolean',
        'is_streaming' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'storage_days' => 'integer',
        'pre_alarm_seconds' => 'integer',
        'rtsp_port' => 'integer',
        'fps' => 'integer',
        'bitrate' => 'integer',
        'stream_pid' => 'integer',
        'storage_size_gb' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'last_seen_at' => 'datetime',
        'last_stream_check' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['playback_url'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($camera) {
            if (empty($camera->stream_key)) {
                $camera->stream_key = Str::random(32);
            }
        });
    }

    /**
     * Get the business that owns the camera
     */
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Get the location where camera is installed
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Get the playback URL (HLS) for frontend
     */
    public function getPlaybackUrlAttribute()
    {
        if (!$this->stream_key) {
            return null;
        }

        $hlsBaseUrl = env('HLS_BASE_URL', 'http://91.99.26.87:8888/live/');
        return $hlsBaseUrl . $this->stream_key . '/index.m3u8';
    }

    /**
     * Scope: Filter by business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
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
     * Scope: Filter by protocol
     */
    public function scopeByProtocol($query, $protocol)
    {
        if ($protocol !== 'all') {
            return $query->where('protocol', $protocol);
        }
        return $query;
    }

    /**
     * Scope: Filter by server
     */
    public function scopeByServer($query, $server)
    {
        if ($server !== 'all') {
            return $query->where('server', $server);
        }
        return $query;
    }

    /**
     * Scope: Only active cameras
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Only streaming cameras
     */
    public function scopeStreaming($query)
    {
        return $query->where('is_streaming', true);
    }

    /**
     * Scope: Search by name, manufacturer, host
     */
    public function scopeSearch($query, $search)
    {
        if (!empty($search)) {
            return $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%")
                  ->orWhere('rtsp_host', 'like', "%{$search}%")
                  ->orWhere('server', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Get full RTSP URL
     */
    public function getRtspFullUrl()
    {
        if (!empty($this->rtsp_url)) {
            return $this->rtsp_url;
        }

        if (!empty($this->rtsp_username) && !empty($this->rtsp_host)) {
            $auth = $this->rtsp_username;
            if (!empty($this->rtsp_password)) {
                $auth .= ':' . $this->rtsp_password;
            }

            $url = "rtsp://{$auth}@{$this->rtsp_host}";

            if (!empty($this->rtsp_port) && $this->rtsp_port != 554) {
                $url .= ":{$this->rtsp_port}";
            }

            if (!empty($this->rtsp_path)) {
                $url .= $this->rtsp_path;
            }

            return $url;
        }

        return null;
    }

    /**
     * Get stream URL (RTSP, RTMP, or HLS)
     */
    public function getStreamUrl()
    {
        // If HLS is available, return HLS URL
        if (!empty($this->hls_url)) {
            return $this->hls_url;
        }

        // Return RTSP or RTMP URL
        if ($this->protocol === 'RTSP') {
            return $this->getRtspFullUrl();
        } elseif ($this->protocol === 'RTMP') {
            return $this->rtmp_url;
        }

        return null;
    }

    /**
     * Check if camera is online
     */
    public function isOnline()
    {
        return $this->status === 'online';
    }

    /**
     * Update camera status
     */
    public function updateStatus($status)
    {
        $this->update([
            'status' => $status,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Start streaming
     */
    public function startStreaming($pid = null)
    {
        $this->update([
            'is_streaming' => true,
            'stream_pid' => $pid,
        ]);
    }

    /**
     * Stop streaming
     */
    public function stopStreaming()
    {
        $this->update([
            'is_streaming' => false,
            'stream_pid' => null,
        ]);
    }

    /**
     * Calculate estimated storage size
     */
    public function calculateStorageSize()
    {
        // Basic calculation: bitrate * fps * storage_days * 60 * 60 * 24 / 8 / 1024 / 1024 / 1024
        // This is a rough estimate
        if ($this->bitrate && $this->storage_days) {
            $bytesPerDay = ($this->bitrate * 1024 / 8) * 60 * 60 * 24;
            $totalBytes = $bytesPerDay * $this->storage_days;
            $gb = $totalBytes / (1024 * 1024 * 1024);

            $this->update(['storage_size_gb' => round($gb, 2)]);
        }
    }
}
