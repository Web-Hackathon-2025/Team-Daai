<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'location',
        'availability',
        'phone',
        'description',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * Get the user that owns the provider profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the services for the provider
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'provider_id', 'user_id');
    }

    /**
     * Get the service requests for this provider
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'provider_id', 'user_id');
    }
}
