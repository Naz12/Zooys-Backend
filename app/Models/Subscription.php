<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    // Allow mass assignment
    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_id',
        'stripe_customer_id',
        'active',
        'starts_at',
        'ends_at',
        'current_usage',
        'usage_reset_date',
        'billing_cycle_start',
        'grace_period_ends_at',
        'last_alert_sent_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_reset_date' => 'datetime',
        'billing_cycle_start' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'last_alert_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function histories()
    {
        return $this->hasMany(History::class, 'user_id', 'user_id');
    }

    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function isInGracePeriod(): bool
    {
        return $this->grace_period_ends_at && $this->grace_period_ends_at->isFuture();
    }

    public function shouldResetUsage(): bool
    {
        return $this->usage_reset_date && $this->usage_reset_date->isPast();
    }

    public function getRemainingUsage(): int
    {
        if (!$this->plan || !$this->plan->limit) {
            return PHP_INT_MAX; // Unlimited
        }
        
        return max(0, $this->plan->limit - $this->current_usage);
    }
}