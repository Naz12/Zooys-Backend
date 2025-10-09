<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MathProblem extends Model
{
    protected $fillable = [
        'user_id',
        'problem_text',
        'problem_image',
        'problem_type',
        'subject_area',
        'difficulty_level',
        'metadata',
        'file_upload_id'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function solutions(): HasMany
    {
        return $this->hasMany(MathSolution::class);
    }

    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySubject($query, $subject)
    {
        return $query->where('subject_area', $subject);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function getImageUrlAttribute()
    {
        if ($this->problem_image) {
            return asset('storage/' . $this->problem_image);
        }
        return null;
    }
}
