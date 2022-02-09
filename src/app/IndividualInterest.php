<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualInterest extends Model
{
    //
    protected $fillable = [
        'user_id','individual_interest_list_id', 'status' 
    ];
    public function indInterest(){
        return $this->hasOne(IndividualInterestList::class,'id','individual_interest_list_id');
    }
}
