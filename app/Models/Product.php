<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Log;
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
        'product_image_one',
        'product_image_two',
        'product_image_three',
        'product_image_four',
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
