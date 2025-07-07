<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class SubCategory extends Model
{
    use HasFactory,UUID;

    protected $fillable = [
        'category_id',
        'name',

    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function products()
    {
        return $this->hasMany(Product::class);
    }

}
