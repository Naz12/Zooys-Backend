<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'file_upload_id', 'doc_id', 'tool_type', 'title', 'description',
        'input_data', 'result_data', 'metadata', 'status'
    ];

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
        'metadata' => 'array'
    ];

    protected $appends = ['file_url'];

    /**
     * Get the user that owns the result
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file upload that was used for this result
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }

    /**
     * Scope for filtering by tool type
     */
    public function scopeByToolType($query, $toolType)
    {
        return $query->where('tool_type', $toolType);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for user's results
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the file URL if associated file exists
     */
    public function getFileUrlAttribute()
    {
        if ($this->fileUpload) {
            return $this->fileUpload->file_url;
        }
        return null;
    }

    /**
     * Delete associated file when result is deleted
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($aiResult) {
            // If this result has an associated file and no other results use it, delete the file
            if ($aiResult->file_upload_id) {
                $fileUpload = $aiResult->fileUpload;
                if ($fileUpload && $fileUpload->aiResults()->count() <= 1) {
                    $fileUpload->delete();
                }
            }
        });
    }
}
