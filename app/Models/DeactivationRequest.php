<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class DeactivationRequest extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'reason',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
