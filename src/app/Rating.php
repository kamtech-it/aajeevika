<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    //
    protected $fillable = ['type', 'user_id', 'rating', 'review_msg', 'review_by_user', 'order_id'];

    public function getreviews() {
        return $this->hasOne(User::class, 'id', 'review_by_user');
        
    }
    
}
