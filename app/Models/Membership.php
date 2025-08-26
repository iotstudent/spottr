<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Membership extends Model
{
   use HasFactory, UUID;

    protected $fillable = [
        'corporate_id',
        'seller_id',
        'status',
        'initiated_by'
    ];

    public function corporate()
    {
        return $this->belongsTo(User::class, 'corporate_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
