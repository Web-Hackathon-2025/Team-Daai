<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'duration_type',
        'price_interval',
        'trial_days',
        'max_cameras',
        'max_locations',
        'max_users',
        'analytics_enabled',
        'api_access_enabled',
        'recording_enabled',
        'motion_detection_enabled',
        'payment_gateway_id',
        'is_active',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
    ];

    protected $casts = [
        'duration' => 'integer',
        'trial_days' => 'integer',
        'max_cameras' => 'integer',
        'max_locations' => 'integer',
        'max_users' => 'integer',
        'analytics_enabled' => 'boolean',
        'api_access_enabled' => 'boolean',
        'recording_enabled' => 'boolean',
        'motion_detection_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];
}
