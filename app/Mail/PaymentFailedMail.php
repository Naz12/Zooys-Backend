<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\User;

class PaymentFailedMail extends Mailable
{
    public function __construct(public User $user) {}

    public function build()
    {
        return $this->subject('⚠️ Payment Failed – Please Update Your Billing')
            ->view('emails.payment-failed');
    }
}