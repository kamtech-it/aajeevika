<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PopupManager extends Model
{
    protected $fillable = [

        'role_id',
        'status',
        'title',
        'description',
        'background_image',
        'action',
        'language',
        'admin_id',
    ];
}
