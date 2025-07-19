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
        'product_image_one',
        'product_image_two',
        'product_image_three',
        'product_image_four',
        'is_approved' ,
        'admin_comment' ,

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



    public function getProductImageOneAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImageTwoAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImageThreeAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }

    public function getProductImageFourAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


}
