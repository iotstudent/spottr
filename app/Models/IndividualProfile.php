<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class IndividualProfile extends Model
{
    use HasFactory,UUID;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'verification_level',
        'type',
        'bio',
        'address',
        'store_name',
        'store_desc',
        'store_email',
        'store_phone',
        'store_bg_image'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStoreBgImageAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }
}
