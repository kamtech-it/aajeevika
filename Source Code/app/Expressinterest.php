<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expressinterest extends Model
{
    protected $fillable = [
        'user_id', 'seller_id', 'status', 'message', 'otp', 'order_status', 'interest_Id', 'order_Id'
    ];

    public function items()
    {
        return $this->hasMany(Expressinterestitem::class, 'express_id', 'id');
    }

    public function seller()
    {
        return $this->hasOne(User::class, 'id', 'seller_id');
    }

    public function buyer()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
