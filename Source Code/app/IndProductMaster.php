<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndProductMaster extends Model
{

    protected $fillable = [
         'cat_id', 'name_hi','price_unit', 'name_en','image','status','length','width','height','weight','vol','no_measurement','length_unit','width_unit',
         'height_unit','weight_unit','vol_unit'
    ];

   




}
