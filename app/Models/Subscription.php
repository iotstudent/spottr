<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class Subscription extends Model
{
     use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'subscription_plan_id',
        'transaction_id',
        'duration_months',
        'start_date',
        'end_date',
        'is_active'

    ];

     protected $casts = [
        'is_active'  => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }


}
