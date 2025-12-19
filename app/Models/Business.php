<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'domain',
        'owner',
        'email',
        'phone',
        'address',
        'website',
        'logo',
        'subscription_package_id',
        'subscription_status',
        'subscription_end_date',
        'is_active',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        // Business metadata
        'start_date',
        'alternate_phone',
        // Address
        'country',
        'state',
        'city',
        'zip_code',
        'landmark',
        // Settings
        'timezone',
        'currency',
        // Payment
        'paid_via',
        'payment_transaction_id',
        // Email Settings
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'enable_welcome_email_to_new_business',
        'enable_new_business_registration_email',
        'enable_new_subscription_email',
        'welcome_email_subject',
    ];

    protected $casts = [
        'start_date' => 'date',
        'subscription_end_date' => 'date',
        'enable_welcome_email_to_new_business' => 'boolean',
        'enable_new_business_registration_email' => 'boolean',
        'enable_new_subscription_email' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/app/public/' .  $this->logo);
        }

        return null;
    }
    public function subscription_package()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    // User which has role of Business Admin
    public function adminUser()
    {
        return $this->hasOne(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'Business Admin');
        });
    }

    public function cameras()
    {
        return $this->hasMany(Camera::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    public function paymentGateways()
    {
        return $this->hasMany(PaymentGateway::class);
    }
}
