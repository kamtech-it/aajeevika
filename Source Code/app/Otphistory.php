<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Otphistory extends Model
{
    protected $fillable = [
        'mobile_no', 'otp'
    ];
}
