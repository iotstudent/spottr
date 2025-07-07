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
        'image_1',
        'image_2',
        'image_3',
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



    public function getImage1Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getImage2Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getImage3Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

}
