<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class SubscriptionPlanFeature extends Model
{
   use HasFactory,UUID;

    protected $fillable = [

        'subscription_plan_id',
        'feature',
        'value',
    ];

    public function subscriptionplan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
