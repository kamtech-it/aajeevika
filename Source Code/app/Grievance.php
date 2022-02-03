<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;

class Grievance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id', 'user_id', 'type','issue_type_id','message','last_message_date','status'
    ];

    public function getIssue()
    {
        return $this->hasOne(GrievanceIssueType::class, 'id', 'issue_type_id');
    }
    public function getMessage()
    {
        return $this->hasMany(GrievanceMessage::class, 'grievance_id', 'id');
    }
    public function getUser()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
