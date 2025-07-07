<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Product extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'brand_id',
        'name',
        'corporate_profile_id',
        'category_id',
        'sub_category_id',
        'description',
        'weight',
        'dimension',
        'additional_specification',
        'attribute',
        'variants',
        'tags',
        'is_available',
        'price',
        'product_code',
        'product_image_1',
        'product_image_2',
        'product_image_3',
        'product_image_4',
        'created_by_admin'
    ];


    protected $casts = [
        'is_available' => 'boolean',
        'created_by_admin'  => 'boolean',
        'attribute' => 'array',
        'variants' => 'array',
    ];


    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function corporateProfile()
    {
        return $this->belongsTo(CorporateProfile::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class,'sub_category_id');
    }

    public function listings()
    {
        return $this->hasMany(ProductListing::class);
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
