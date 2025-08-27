<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class DistributionList extends Model
{
    use HasFactory,UUID;

    protected $fillable = [

        'corporate_id',
        'name',
        'description',
        'type',

    ];


    public function corporate()
    {
        return $this->belongsTo(User::class, 'corporate_id');
    }

    public function members()
    {
        return $this->hasMany(DistributionListMember::class, 'distribution_list_id');
    }
    
}
