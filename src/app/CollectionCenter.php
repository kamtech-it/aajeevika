<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\State;

class CollectionCenter extends Model
{

    protected $fillable = [
         'name_en','state_id','city_id','block_id', 'name_hi', 'status'
    ];

    
    public function state()
    {
        $this->belongsTo(State::class,'state_id');
    }




}
