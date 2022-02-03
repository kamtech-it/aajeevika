<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndOrderItem extends Model
{
     protected $fillable = [
        'order_id', 'product_id','quantity'
    ];
    public function Indproduct()
    {
        return $this->hasOne(IndProductMaster::class, 'id', 'product_id');
    }
    
}
