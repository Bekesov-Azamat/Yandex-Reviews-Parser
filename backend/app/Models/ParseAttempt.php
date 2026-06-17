<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ParseStatus;

class ParseAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'status',
        'reviews_requested_limit',
        'reviews_collected',
        'started_at',
        'finished_at',
        'error_message',
        'meta',
    ];
    protected $casts = [
        'status' => ParseStatus::class,
        'reviews_requested_limit' => 'integer',
        'reviews_collected' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
