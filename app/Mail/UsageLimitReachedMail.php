<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\User;
use App\Models\Subscription;

class UsageLimitReachedMail extends Mailable
{
    public function __construct(public User $user, public Subscription $subscription) {}

    public function build()
    {
        return $this->subject('🚫 Usage Limit Reached - Upgrade Required')
            ->view('emails.usage-limit-reached')
            ->with([
                'user' => $this->user,
                'subscription' => $this->subscription,
                'currentUsage' => $this->subscription->current_usage,
                'planLimit' => $this->subscription->plan->limit,
                'planName' => $this->subscription->plan->name,
                'resetDate' => $this->subscription->usage_reset_date,
            ]);
    }
}