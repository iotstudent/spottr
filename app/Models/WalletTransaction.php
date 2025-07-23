<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class WalletTransaction extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'tx_ref',
        'transaction_id',
        'type',
        'format',
        'provider',
        'amount',
        'currency',
        'payment_status',
        'payment_method',

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
