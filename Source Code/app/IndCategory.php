<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class IndCategory extends Model
{

    protected $fillable = [
         'name_en', 'name_hi', 'status','image'
    ];

    public function indProducts()
    {
        return $this->hasMany(IndProductMaster::class, 'cat_id', 'id');

    }




}
