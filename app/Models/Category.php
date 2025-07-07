<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Category extends Model
{
    use HasFactory,UUID;

    protected $fillable = [
        'name',
    ];


    public function brands()
    {
        return $this->hasMany(Product::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class);
    }
}
