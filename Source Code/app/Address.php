<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'user_role_id','block','village', 'address_line_one', 'address_line_two', 'pincode', 'country', 'state', 'district','address_type'
    ];

    public function country() {
        return $this->hasMany('App\Country', 'id', 'country');
    }

    public function state() {
        return $this->hasMany('App\State', 'id', 'state');
    }

    public function district() {
        return $this->hasMany('App\City', 'id', 'state');
    }
    //new ...................
    public function getBlock() {
        return $this->hasOne('App\Block', 'id', 'block');
    }
    public function getDistrict() {
        return $this->hasOne('App\City', 'id', 'district');
    }
    public function getState() {
        return $this->hasOne('App\State', 'id', 'state');
    }
    
}
