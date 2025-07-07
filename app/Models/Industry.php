<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\UUID;

class Industry extends Model
{
    use HasFactory,UUID;

    protected $fillable = [
        'name',
    ];


    public function corporateProfiles()
    {
        return $this->hasMany(CorporateProfile::class);
    }

}
