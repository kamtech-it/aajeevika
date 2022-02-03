<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    //
    protected $fillable = [
        'title', 'body','role_id','image','status' ,'language'
    ];

    public function role(){
        return $this->hasOne(Role::class, 'id','role_id');
    }
}
