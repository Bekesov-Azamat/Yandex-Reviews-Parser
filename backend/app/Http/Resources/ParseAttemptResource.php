<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParseAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'reviews_requested_limit' => $this->reviews_requested_limit,
            'reviews_collected' => $this->reviews_collected,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'error_message' => $this->error_message,
            'meta' => $this->meta,
        ];
    }
}
