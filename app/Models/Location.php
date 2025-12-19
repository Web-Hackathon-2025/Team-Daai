<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'business_id',
        'is_active',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
