<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class CorporateProfile extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'kyc_doc',
        'industry_id',
        'company_name',
        'company_size',
        'company_address',
        'company_description',
        'tags',
        'website_url',
    ];


    public function getKycDocAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }


    public function representative()
    {
        return $this->has(Representative::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

}
