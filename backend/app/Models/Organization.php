<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ParseStatus;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'yandex_url',
        'name',
        'rating',
        'ratings_count',
        'reviews_count',
        'parse_status',
        'parse_error',
        'last_parsed_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'ratings_count' => 'integer',
        'reviews_count' => 'integer',
        'last_parsed_at' => 'datetime',
        'parse_status' => ParseStatus::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parseAttempts(): HasMany
    {
        return $this->hasMany(ParseAttempt::class);
    }
}
