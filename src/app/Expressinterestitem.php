<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expressinterestitem extends Model
{
     protected $fillable = [
        'express_id', 'product_id','quantity'
    ];

    public function product()
    {
        return $this->hasOne(ProductMaster::class, 'id', 'product_id');
    }
}
