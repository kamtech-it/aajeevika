<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Documents extends Model
{
    protected $fillable = [
        'user_id', 'adhar_card_no', 'adhar_name', 'adhar_card_front_file', 'adhar_card_back_file', 'is_adhar_verify', 'adhar_dob', 'pancard_name', 'pancard_no','pancard_file', 'pancard_dob', 'is_pan_verify', 'brn_no', 'brn_name', 'brn_file','is_brn_verify','is_aadhar_added','is_pan_added','is_brn_added'
    ];

    public function user() {
        return $this->belongsTo('App\User','user_id', 'id');
    }

    public function get_role() {
        return $this->belongsTo('App\Role', 'role_id', 'id');
    }
}
