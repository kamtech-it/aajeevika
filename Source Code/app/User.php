<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email','profileImage', 'password','collection_center_id','block', 'mobile','country_id','api_token', 'role_id', 'district', 'state_id', 'language','is_otp_verified','is_document_added', 'is_document_verified', 'is_address_added', 'is_promotional_mail', 'is_email_verified','isActive','is_blocked_byadmin','devicetoken', 'member_id', 'organization_name', 'member_designation'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo('App\Documents', 'id', 'user_id');
    }

    public function address()
    {
        return $this->hasMany('App\Address', 'user_id', 'id');
    }


    public function address_registerd()
    {
        return $this->hasOne('App\Address', 'user_id', 'id')->where('address_type', 'registered');
    }

    public function address_personal()
    {
        return $this->hasOne('App\Address', 'user_id', 'id')->where('address_type', 'personal')->latest();;
    }


    public function roles()
    {
        return $this->hasOne('App\Role', 'role_id', 'id');
    }



    public function state()
    {
        return $this->hasOne('App\State', 'id', 'state_id');
    }

    public function country()
    {
        return $this->hasOne('App\Country', 'id', 'country_id');
    }


    //Has issues
    public function district()
    {
        return $this->hasOne('App\City', 'id', 'district');
    }

    public function userdistrict()
    {
        return $this->hasOne('App\City', 'id', 'district');
    }
    public function userBlock()
    {
        return $this->hasOne('App\Block', 'id', 'block');
    }
    public function city()
    {
        return $this->hasOne('App\City', 'id', 'district');
    }

    // public function docs()
    // {
    //     return $this->hasOne('App\Documents', 'user_id', 'id');
    // }




    public function userRole()
    {
        return $this->hasOne('App\Role', 'id', 'role_id');
    }

    public function docs()
    {
        return $this->hasMany('App\Documents', 'user_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(ProductMaster::class)->where(['is_active'=>1])->orderBy('id', 'DESC');
    }

    public function homeshgprods()
    {
        return $this->hasMany(ProductMaster::class)->where(['is_active'=> 1,'is_draft' => 0])->limit(3);
    }
    // User Home Page Load More
    public function shgproduct()
    {
        $data = $this->hasMany(ProductMaster::class)->where(['is_active'=> 1,'is_draft' => 0])->take(5);

        return $data;
    }
}
