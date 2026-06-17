<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'yandex_url' => $this->yandex_url,
            'name' => $this->name,
            'rating' => $this->rating !== null ? (float) $this->rating : null,
            'ratings_count' => $this->ratings_count,
            'reviews_count' => $this->reviews_count,
            'parse_status' => $this->parse_status?->value,
            'parse_error' => $this->parse_error,
            'last_parsed_at' => $this->last_parsed_at?->toISOString(),
        ];
    }
}
