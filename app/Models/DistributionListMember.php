<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;

class DistributionListMember extends Model
{

    use HasFactory, UUID;

    protected $fillable = [
        'distribution_list_id',
        'member_id',
    ];


    public function distributionList()
    {
        return $this->belongsTo(DistributionList::class, 'distribution_list_id');
    }

  
    public function member()
    {
        return $this->belongsTo(Membership::class, 'member_id');
    }

}
