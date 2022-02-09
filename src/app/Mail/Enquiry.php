<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Enquiry extends Mailable
{
    use Queueable, SerializesModels;

    public $userDetail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($userDetail)
    {
        $this->order = $userDetail;
       

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        // return $this->view('view.name');
        return $this->from('undp@undp.com')
                ->view('emails.enquiry')
                ->with([
                    'userdetail' => $this->order
                ]);
    }
}
