<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PopularProduct extends Model
{
    //
    protected $fillable = [
        'product_id', 'admin_id', 'status',
    ];


    public function product(){
        return $this->hasOne(ProductMaster::class, 'id', 'product_id' );
    }
}
