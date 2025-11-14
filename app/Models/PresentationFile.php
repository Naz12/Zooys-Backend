<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PresentationFile extends Model
{
    protected $fillable = [
        'user_id', 'title', 'filename', 'file_path', 'file_size',
        'template', 'color_scheme', 'font_style', 'slides_count',
        'metadata', 'content_data', 'expires_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'content_data' => 'array',
        'expires_at' => 'datetime',
        'file_size' => 'integer',
        'slides_count' => 'integer'
    ];

    /**
     * Get the user that owns the presentation file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full file URL
     */
    public function getFileUrlAttribute(): string
    {
        $baseUrl = request()->getSchemeAndHttpHost();
        $storagePath = 'storage/' . $this->file_path;
        return $baseUrl . '/' . $storagePath;
    }

    /**
     * Get human readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Delete the file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($presentationFile) {
            if (Storage::disk('public')->exists($presentationFile->file_path)) {
                Storage::disk('public')->delete($presentationFile->file_path);
            }
        });
    }
}
