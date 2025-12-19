<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'price',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the provider that offers this service
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the service requests for this service
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get the provider profile
     */
    public function providerProfile()
    {
        return $this->hasOneThrough(
            ProviderProfile::class,
            User::class,
            'id', // Foreign key on users table
            'user_id', // Foreign key on provider_profiles table
            'provider_id', // Local key on services table
            'id' // Local key on users table
        );
    }
}
