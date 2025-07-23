<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class UserAddress extends Model
{
    use HasFactory, UUID;

     protected $fillable = [
        'user_id',
        'path',
        'coin_type',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
