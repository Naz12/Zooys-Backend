<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'provider',
        'is_active',
        'status',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function histories()
    {
        return $this->hasMany(History::class);
    }

    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function visitorTracking()
    {
        return $this->hasMany(VisitorTracking::class);
    }

    public function getActiveSubscription()
    {
        return $this->subscription()->where('active', true)->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->getActiveSubscription() !== null;
    }

    public function getCurrentUsage(): int
    {
        $subscription = $this->getActiveSubscription();
        return $subscription ? $subscription->current_usage : 0;
    }

    public function getUsageLimit(): ?int
    {
        $subscription = $this->getActiveSubscription();
        return $subscription && $subscription->plan ? $subscription->plan->limit : null;
    }

    public function canMakeRequest(): bool
    {
        $subscription = $this->getActiveSubscription();
        
        if (!$subscription) {
            return false; // No subscription
        }

        // Check if in grace period
        if ($subscription->isInGracePeriod()) {
            return true;
        }

        // Check usage limit
        $limit = $subscription->plan->limit;
        if (!$limit) {
            return true; // Unlimited
        }

        return $subscription->current_usage < $limit;
    }
}