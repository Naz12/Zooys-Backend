<?php

// app/Mail/UsageWarningMail.php
namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\User;
use App\Models\Subscription;

class UsageWarningMail extends Mailable
{
    public function __construct(public User $user, public Subscription $subscription) {}

    public function build()
    {
        return $this->subject('⚠️ You are nearing your usage limit')
            ->view('emails.usage-warning');
    }
}