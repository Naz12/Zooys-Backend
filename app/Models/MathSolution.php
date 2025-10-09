<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MathSolution extends Model
{
    protected $fillable = [
        'math_problem_id',
        'solution_method',
        'step_by_step_solution',
        'final_answer',
        'explanation',
        'verification',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function mathProblem(): BelongsTo
    {
        return $this->belongsTo(MathProblem::class);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('solution_method', $method);
    }
}
