<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_id_d', 'interest_id', 'user_id', 'seller_id','collection_center_id', 'otp', 'mode_of_delivery', 'delivery_status', 'order_status','status','sale_date'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function seller()
    {
        return $this->hasOne(User::class, 'id', 'seller_id');
    }

    public function buyer()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function interest()
    {
        return $this->hasOne(Expressinterest::class, 'id', 'interest_id');
    }
    public function sellerRating()
    {
        return $this->hasOne(Rating::class, 'order_id', 'id')->select('id','order_id','rating','review_msg','type')->where('type','seller');
    }
    public function buyerRating()
    {
        return $this->hasOne(Rating::class, 'order_id', 'id')->select('id','order_id','rating','review_msg','type')->where('type','buyer');
    }
}
