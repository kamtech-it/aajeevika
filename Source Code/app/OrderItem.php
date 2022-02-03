<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
     protected $fillable = [
        'order_id', 'product_id','product_price','quantity'
    ];
    public function product()
    {
        return $this->hasOne(ProductMaster::class, 'id', 'product_id');
    }
    
}
