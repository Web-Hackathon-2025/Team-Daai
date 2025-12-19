<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // Profile fields
        'first_name',
        'last_name',
        'prefix',
        'username',
        'phone',
        'alternate_phone',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function providerProfile()
    {
        return $this->hasOne(ProviderProfile::class);
    }

    /**
     * Get the services offered by this provider
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'provider_id');
    }

    /**
     * Get service requests as a customer
     */
    public function customerRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id');
    }

    /**
     * Get service requests as a provider
     */
    public function providerRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'provider_id');
    }

    /**
     * Get reviews written by this customer
     */
    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    /**
     * Get reviews received by this provider
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }
}
