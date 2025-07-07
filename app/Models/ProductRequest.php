<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class ProductRequest extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'name',
        'category_id',
        'sub_category_id',
        'description',
        'weight',
        'dimension',
        'additional_specification',
        'attribute',
        'variants',
        'tags',
        'price',
        'product_code',
        'product_image_1',
        'product_image_2',
        'product_image_3',
        'product_image_4',
        'is_approved' ,
        'comment' ,

    ];


    protected $casts = [
        'is_approved' => 'boolean',
        'attribute' => 'array',
        'variants' => 'array',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class,'sub_category_id');
    }



    public function getProductImage1Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImage2Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImage3Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImage4Attribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


}
