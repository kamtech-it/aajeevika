<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bulk extends Model
{
    //

    protected $fillable = [
        'role_id', 'message',
    ];

    public function role(){
        return $this->hasOne(Role::class, 'id','role_id');
    }
}
