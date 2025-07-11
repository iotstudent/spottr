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
        'type',// credit and debit
        'format',//fiat or crypto
        'provider',
        'amount',
        'currency',
        'payment_status',   // e.g., 'pending', 'successful', 'failed'
        'payment_method',

    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
