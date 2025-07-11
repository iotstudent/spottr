<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class BankAccount extends Model
{
    use HasFactory;

    use HasFactory,UUID;

    protected $fillable = [

        'user_id',
        'benefit_id',
        'bank_code',
        'bank_name',
        'account_number',
        'account_name',
        'is_default'

    ];

     protected $casts = [
        'is_default'  => 'boolean',
    ];
}
