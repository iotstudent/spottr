<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Transaction extends Model
{
     use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'type',
        'format',
        'amount',
        'purpose',
        'status',
    ];





    public function user()
    {
        return $this->belongsTo(User::class);
    }

     public function subscriptions()
    {
        return $this->hasMany(User::class);
    }

}
