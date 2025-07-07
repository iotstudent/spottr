<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Feedback extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'screen_shot',
        'category',
        'description',
    ];


    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function getScreenShotAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


}
