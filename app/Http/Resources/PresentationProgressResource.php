<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresentationProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'status' => $this->resource['status'] ?? 'unknown',
            'progress' => [
                'percentage' => $this->resource['percentage'] ?? 0,
                'current_step' => $this->resource['current_step'] ?? 'Processing',
                'steps_completed' => $this->resource['steps_completed'] ?? 0,
                'total_steps' => $this->resource['total_steps'] ?? 1,
                'estimated_time_remaining' => $this->resource['estimated_time_remaining'] ?? 0
            ],
            'ai_result_id' => $this->resource['ai_result_id'] ?? null,
            'operation_id' => $this->resource['operation_id'] ?? null,
            'started_at' => $this->resource['started_at'] ?? null,
            'completed_at' => $this->resource['completed_at'] ?? null,
            'error_message' => $this->resource['error_message'] ?? null,
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'service' => 'presentation_microservice'
            ]
        ];
    }
}
