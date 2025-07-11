<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class SubscriptionPlan extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'name',
        'amount',
        'currency',
        'image',
        'is_active'

    ];

    protected $casts = [

        'is_active'  => 'boolean',

    ];

    public function features()
    {
        return $this->hasMany(SubscriptionPlanFeature::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getImageAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


}
