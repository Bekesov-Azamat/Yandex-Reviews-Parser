<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Dto\ParsedOrganizationData;
use App\Services\YandexMaps\Dto\ParsedReviewData;

class FakeYandexMapsParser implements YandexMapsParserInterface
{
    public function parse(string $url, int $reviewsLimit = 600): ParsedOrganizationData
    {
        $reviews = [];

        for ($i = 1; $i <= min($reviewsLimit, 137); $i++) {
            $reviews[] = new ParsedReviewData(
                externalId: 'fake-review-'.$i,
                authorName: 'Автор '.$i,
                reviewedAt: now()->subDays($i)->toDateTimeString(),
                text: 'Тестовый отзыв '.$i.' для проверки пагинации и сохранения данных.',
                rating: ($i % 5) + 1,
            );
        }

        return new ParsedOrganizationData(
            name: 'Demo Yandex Organization',
            rating: 4.32,
            ratingsCount: 245,
            reviewsCount: count($reviews),
            reviews: $reviews,
            isPartial: false,
            warning: null,
        );
    }
}
