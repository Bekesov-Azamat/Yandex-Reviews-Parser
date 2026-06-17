<?php

namespace App\Services\YandexMaps\Dto;

final readonly class ParsedReviewData
{
    public function __construct(
        public ?string $externalId,
        public string $authorName,
        public ?string $reviewedAt,
        public ?string $text,
        public ?int $rating,
    ) {
    }
}
