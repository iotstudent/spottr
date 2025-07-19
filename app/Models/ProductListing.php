<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class ProductListing extends Model
{
     use HasFactory,UUID;


      protected $fillable = [

        'user_id',
        'product_id',
        'description',
        'seller_unit_price',
        'currency',
        'image_one',
        'image_two',
        'image_three',
        'is_active',
    ];


     protected $casts = [
        'is_active' => 'boolean'
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }



    public function getImageOneAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getImageTwoAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getImageThreeAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

}
