<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class FileUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'original_name', 'stored_name', 'file_path', 
        'mime_type', 'file_size', 'file_type', 'metadata', 'is_processed'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_processed' => 'boolean',
        'file_size' => 'integer'
    ];

    /**
     * Get the user that owns the file
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI results that use this file
     */
    public function aiResults(): HasMany
    {
        return $this->hasMany(AIResult::class);
    }

    /**
     * Get the full file URL
     */
    public function getFileUrlAttribute(): string
    {
        // Use the current request URL to ensure correct port
        $baseUrl = request()->getSchemeAndHttpHost();
        
        // Build the storage URL manually to avoid duplication
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
     * Delete the file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($fileUpload) {
            if (Storage::exists($fileUpload->file_path)) {
                Storage::delete($fileUpload->file_path);
            }
        });
    }
}
