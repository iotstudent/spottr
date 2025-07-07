<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Brand extends Model
{
    use HasFactory,UUID;

      protected $fillable = [

        'name',
        'image',
        'corporate_profile_id',
        'category_id',
        'description',
        'created_by_admin',

    ];


     protected $casts = [
        'created_by_admin'  => 'boolean',
    ];

    public function getImageAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function corporateProfile()
    {
        return $this->belongsTo(CorporateProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }



}
