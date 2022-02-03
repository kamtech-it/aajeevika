<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndOrder extends Model
{
    protected $fillable = [
        'order_id_d', 'user_id', 'seller_id', 'otp', 'mode_of_delivery', 'delivery_status', 'order_status','status','sale_date'
    ];

    public function indItems()
    {
        return $this->hasMany(IndOrderItem::class, 'order_id', 'id');
    }

    public function getClf()
    {
        return $this->hasOne(User::class, 'id', 'seller_id');
    }

    public function GetIndividual()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    
    public function clfRating()
    {
        return $this->hasOne(IndRating::class, 'order_id', 'id')->select('id','order_id','rating','review_msg','type')->where('type','individual');
    }
    public function indRating()
    {
        return $this->hasOne(indRating::class, 'order_id', 'id')->select('id','order_id','rating','review_msg','type')->where('type','clf');
    }
}
