<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Representative extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'corporate_profile_id',
        'job_title',
        'email',
        'phone',
        'pic',
        'first_name',
        'last_name',
        'bio'
    ];


    public function corporateProfile()
    {
        return $this->belongsTo(CorporateProfile::class)->withTrashed();
    }


    public function getPicAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }
}
