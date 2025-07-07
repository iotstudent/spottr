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
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
