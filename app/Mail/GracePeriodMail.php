<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\User;
use App\Models\Subscription;

class GracePeriodMail extends Mailable
{
    public function __construct(public User $user, public Subscription $subscription) {}

    public function build()
    {
        return $this->subject('⏰ Payment Failed - Grace Period Active')
            ->view('emails.grace-period')
            ->with([
                'user' => $this->user,
                'subscription' => $this->subscription,
                'gracePeriodEndsAt' => $this->subscription->grace_period_ends_at,
                'planName' => $this->subscription->plan->name,
                'daysRemaining' => $this->subscription->grace_period_ends_at ? 
                    now()->diffInDays($this->subscription->grace_period_ends_at) : 0,
            ]);
    }
}