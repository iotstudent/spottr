<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\UUID;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,UUID,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_name',
        'role',
        'email',
        'phone',
        'pic',
        'email_verified_at',
        'is_active',
        'fiat_wallet',
        'clique_token_wallet',
        'usdc_wallet',
        'transaction_pin',
        'transaction_pin_otp',
        'created_by_admin',
        'password',
        'verification_code',
        'verification_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'verification_expiry',
        'individualProfile',
        'corporateProfile',
        'transaction_pin',
        'transaction_pin_otp',
        'created_by_admin'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'  => 'boolean',
        'created_by_admin'  => 'boolean',
        'password' => 'hashed',
    ];

    protected $appends =[
        'profile'
    ];



    protected $dates = ['deleted_at', 'deletion_scheduled_at'];

    public function getProfileAttribute()
    {
        return $this->role === 'corporate' ? $this->corporateProfile : $this->individualProfile;
    }

    public function getPicAttribute($value)
    {
        return $value ? url('storage/' . $value) : null;
    }


    public function individualProfile()
    {
        return $this->hasOne(IndividualProfile::class);
    }

    public function corporateProfile()
    {
        return $this->hasOne(CorporateProfile::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }


    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function productListings()
    {
        return $this->hasMany(ProductListing::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('is_active', true);
    }

    public function bankaccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function defaultBankaccount()
    {
        return $this->hasMany(BankAccount::class)->where('is_default', true);
    }



    public function isCorporate()
    {
        return $this->role === 'corporate';
    }

    public function isIndividual()
    {
        return $this->role === 'individual';
    }
}
