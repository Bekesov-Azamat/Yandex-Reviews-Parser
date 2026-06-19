<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Dto\ParsedOrganizationData;
use App\Services\YandexMaps\Dto\ParsedReviewData;
use App\Services\YandexMaps\Exceptions\InvalidYandexMapsUrlException;
use App\Services\YandexMaps\Exceptions\YandexMapsParserException;
use App\Services\YandexMaps\Exceptions\YandexMapsUnavailableException;
use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class RealYandexMapsParser implements YandexMapsParserInterface
{
    private const BASE_URL = 'https://yandex.kz';
    private const PAGE_SIZE = 50;

    private CookieJar $cookieJar;

    public function parse(string $url, int $reviewsLimit = 600): ParsedOrganizationData
    {
        $businessId = $this->extractBusinessId($url);
        $normalizedUrl = $this->normalizeUrl($url, $businessId);

        $this->cookieJar = new CookieJar();

        $pageResponse = $this->fetchPage($normalizedUrl);
        $html = $pageResponse->body();

        $csrfToken = $this->extractJsonString($html, 'csrfToken');
        $sessionId = $this->extractJsonString($html, 'sessionId');
        $requestId = $this->extractAddrsRequestId($html);

        if (! $csrfToken || ! $sessionId || ! $requestId) {
            throw new YandexMapsParserException('Could not extract required Yandex Maps context from organization page.');
        }

        $organization = $this->extractOrganizationFromHtml($html, $businessId);
        $reviews = $this->fetchReviews(
            businessId: $businessId,
            csrfToken: $csrfToken,
            sessionId: $sessionId,
            requestId: $requestId,
            referer: $normalizedUrl,
            reviewsLimit: $reviewsLimit,
        );

        $reviewsCount = $organization['reviews_count'] ?: count($reviews);
        $isPartial = count($reviews) < $reviewsCount;

        return new ParsedOrganizationData(
            name: $organization['name'],
            rating: $organization['rating'],
            ratingsCount: $organization['ratings_count'],
            reviewsCount: $reviewsCount,
            reviews: $reviews,
            isPartial: $isPartial,
            warning: $isPartial
                ? "Loaded " . count($reviews) . " of {$reviewsCount} available reviews."
                : null,
        );
    }

    private function extractBusinessId(string $url): string
    {
        if (preg_match('~/org/[^/]+/(\d+)~', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('~oid=(\d+)~', $url, $matches)) {
            return $matches[1];
        }

        throw new InvalidYandexMapsUrlException('Could not extract organization id from Yandex Maps URL.');
    }

    private function normalizeUrl(string $url, string $businessId): string
    {
        $baseUrl = strtok($url, '?');

        if (is_string($baseUrl) && preg_match('~^https?://[^/]+/maps/org/[^/]+/\d+~', $baseUrl)) {
            $baseUrl = rtrim($baseUrl, '/');

            if (str_ends_with($baseUrl, '/reviews')) {
                return $baseUrl . '/';
            }

            return $baseUrl . '/reviews/';
        }

        return self::BASE_URL . '/maps/org/org/' . $businessId . '/reviews/';
    }

    private function fetchPage(string $url, array $headers = []): Response
    {
        $response = $this->http($headers)->get($url);

        if ($response->failed()) {
            throw new YandexMapsUnavailableException(
                'Yandex Maps page returned HTTP ' . $response->status()
            );
        }

        $body = $response->body();

        if (
            str_contains($body, 'showcaptcha')
            || str_contains($body, 'smartcaptcha')
            || str_contains($body, 'Подтвердите, что запросы отправляли вы')
            || str_contains($body, 'Confirm that you are not a robot')
        ) {
            throw new YandexMapsUnavailableException('Yandex Maps returned bot protection page.');
        }

        return $response;
    }

    /**
     * @return array{name: string, rating: ?float, ratings_count: int, reviews_count: int}
     */
    private function extractOrganizationFromHtml(string $html, string $businessId): array
    {
        $name = $this->extractOgTitle($html) ?? 'Yandex organization ' . $businessId;
        $rating = $this->extractMetaFloat($html, 'ratingValue');
        $ratingsCount = $this->extractMetaInteger($html, 'ratingCount');
        $reviewsCount = $this->extractMetaInteger($html, 'reviewCount');

        if (($ratingsCount === 0 || $reviewsCount === 0) && preg_match('/<meta[^>]+property="og:description"[^>]+content="(.*?)"/u', $html, $matches)) {
            $description = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($ratingsCount === 0 && preg_match('/(\d+)\s+(?:оценок|оценки|оценка)/u', $description, $ratingMatches)) {
                $ratingsCount = (int) $ratingMatches[1];
            }

            if ($reviewsCount === 0 && preg_match('/(\d+)\s+(?:отзывов|отзыва|отзыв)/u', $description, $reviewMatches)) {
                $reviewsCount = (int) $reviewMatches[1];
            }
        }

        return [
            'name' => $name,
            'rating' => $rating,
            'ratings_count' => $ratingsCount,
            'reviews_count' => $reviewsCount,
        ];
    }

    private function extractOgTitle(string $html): ?string
    {
        if (! preg_match('/<meta[^>]+property="og:title"[^>]+content="(.*?)"/u', $html, $matches)) {
            return null;
        }

        $title = html_entity_decode(
            $matches[1],
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if (preg_match('/«([^»]+)»/u', $title, $nameMatch)) {
            return trim($nameMatch[1]);
        }

        return trim($title);
    }
    private function extractMetaFloat(string $html, string $itemprop): ?float
    {
        $value = $this->extractMetaContent($html, $itemprop);

        if ($value === null) {
            return null;
        }

        return round((float) str_replace(',', '.', $value), 2);
    }

    private function extractMetaInteger(string $html, string $itemprop): int
    {
        $value = $this->extractMetaContent($html, $itemprop);

        return $value !== null ? (int) preg_replace('/\D/', '', $value) : 0;
    }

    private function extractMetaContent(string $html, string $itemprop): ?string
    {
        $quotedItemprop = preg_quote($itemprop, '/');

        if (preg_match('/<meta[^>]+itemprop=["\']' . $quotedItemprop . '["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $matches)) {
            return $matches[1];
        }

        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+itemprop=["\']' . $quotedItemprop . '["\']/iu', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return array<int, ParsedReviewData>
     */
    private function fetchReviews(
        string $businessId,
        string $csrfToken,
        string $sessionId,
        string $requestId,
        string $referer,
        int $reviewsLimit,
    ): array {
        $reviews = [];
        $page = 1;
        $maxPages = max(1, (int) ceil($reviewsLimit / self::PAGE_SIZE));

        while ($page <= $maxPages && count($reviews) < $reviewsLimit) {
            $query = [
                'ajax' => 1,
                'businessId' => $businessId,
                'csrfToken' => $csrfToken,
                'locale' => 'ru_RU',
                'page' => $page,
                'pageSize' => self::PAGE_SIZE,
                'ranking' => 'by_relevance_org',
                'reqId' => $requestId,
                'sessionId' => $sessionId,
            ];

            $query['s'] = $this->signYandexQuery($query);

            $apiBaseUrl = $this->baseUrlFromReferer($referer);

            $response = $this->http([
                'Referer' => $referer,
                'X-Retpath-Y' => $referer,
            ])->get($apiBaseUrl . '/maps/api/business/fetchReviews', $query);

            if ($response->failed()) {
                throw new YandexMapsUnavailableException(
                    'Yandex Maps reviews endpoint returned HTTP ' . $response->status()
                );
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new YandexMapsParserException('Yandex Maps reviews endpoint returned non-json response.');
            }

            $items = data_get($payload, 'data.reviews', []);

            if (! is_array($items)) {
                throw new YandexMapsParserException('Yandex Maps reviews response does not contain reviews list.');
            }

            foreach ($items as $item) {
                if (count($reviews) >= $reviewsLimit) {
                    break;
                }

                $reviews[] = new ParsedReviewData(
                    externalId: data_get($item, 'reviewId'),
                    authorName: (string) data_get($item, 'author.name', 'Unknown author'),
                    reviewedAt: $this->normalizeDate(data_get($item, 'updatedTime')),
                    text: data_get($item, 'text'),
                    rating: data_get($item, 'rating') !== null
                        ? (int) data_get($item, 'rating')
                        : null,
                );
            }

            $totalPages = (int) data_get($payload, 'data.params.totalPages', $page);
            $reviewsRemained = (int) data_get($payload, 'data.params.reviewsRemained', 0);

            if ($page >= $totalPages || $reviewsRemained <= 0 || count($items) === 0) {
                break;
            }

            $page++;
        }

        return $reviews;
    }

    private function extractJsonString(string $text, string $key): ?string
    {
        $quotedKey = preg_quote($key, '/');

        if (! preg_match('/"' . $quotedKey . '":"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/u', $text, $matches)) {
            return null;
        }

        $decoded = json_decode('"' . $matches[1] . '"');

        return is_string($decoded) ? $decoded : $matches[1];
    }

    private function extractAddrsRequestId(string $html): ?string
    {
        preg_match_all('/"requestId":"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"/u', $html, $matches);

        foreach ($matches[1] ?? [] as $requestId) {
            $decoded = json_decode('"' . $requestId . '"');
            $requestId = is_string($decoded) ? $decoded : $requestId;

            if (str_contains($requestId, 'addrs-upper')) {
                return $requestId;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function signYandexQuery(array $query): string
    {
        uksort($query, fn(string $left, string $right): int => strtolower($left) <=> strtolower($right));

        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $hash = 5381;

        for ($index = 0, $length = strlen($queryString); $index < $length; $index++) {
            $hash = ((33 * $hash) ^ ord($queryString[$index])) & 0xFFFFFFFF;
        }

        return (string) $hash;
    }

    private function normalizeDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function baseUrlFromReferer(string $referer): string
    {
        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return self::BASE_URL;
        }

        return 'https://' . $host;
    }

    /**
     * @param array<string, string> $headers
     */
    private function http(array $headers = []): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->cookieJar,
        ])
            ->timeout(30)
            ->retry(3, 400, throw: false)
            ->withHeaders(array_merge([
                'Accept' => '*/*',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ], $headers));
    }
}
