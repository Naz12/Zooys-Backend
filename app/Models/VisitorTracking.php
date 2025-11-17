<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorTracking extends Model
{
    protected $table = 'visitor_tracking';

    protected $fillable = [
        'tool_id',
        'route_path',
        'user_id',
        'public_id',
        'session_id',
        'visited_at',
        'referrer',
        'location',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'location' => 'array',
    ];

    /**
     * Get the user that made the visit (if authenticated)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by public ID
     */
    public function scopeByPublicId($query, string $publicId)
    {
        return $query->where('public_id', $publicId);
    }

    /**
     * Scope to filter by session ID
     */
    public function scopeBySessionId($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to filter by tool ID
     */
    public function scopeByToolId($query, string $toolId)
    {
        return $query->where('tool_id', $toolId);
    }

    /**
     * Scope to filter by user ID
     */
    public function scopeByUserId($query, ?int $userId)
    {
        if ($userId === null) {
            return $query->whereNull('user_id');
        }
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('visited_at', [$startDate, $endDate]);
    }
}
