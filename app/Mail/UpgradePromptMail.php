<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\User;
use App\Models\Subscription;

class UpgradePromptMail extends Mailable
{
    public function __construct(public User $user, public Subscription $subscription) {}

    public function build()
    {
        return $this->subject('🚀 Upgrade Your Plan for Better Performance')
            ->view('emails.upgrade-prompt')
            ->with([
                'user' => $this->user,
                'subscription' => $this->subscription,
                'currentUsage' => $this->subscription->current_usage,
                'planLimit' => $this->subscription->plan->limit,
                'planName' => $this->subscription->plan->name,
                'usagePercentage' => $this->subscription->plan->limit ? 
                    round(($this->subscription->current_usage / $this->subscription->plan->limit) * 100, 2) : 0,
            ]);
    }
}