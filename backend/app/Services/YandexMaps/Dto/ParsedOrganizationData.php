<?php

namespace App\Services\YandexMaps\Dto;

final readonly class ParsedOrganizationData
{
    /**
     * @param array<int, ParsedReviewData> $reviews
     */
    public function __construct(
        public string $name,
        public ?float $rating,
        public int $ratingsCount,
        public int $reviewsCount,
        public array $reviews,
        public bool $isPartial = false,
        public ?string $warning = null,
    ) {
    }
}
