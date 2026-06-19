<?php

namespace App\Services\Organization;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\ParseAttempt;
use App\Models\Review;
use App\Models\User;
use App\Services\Organization\Exceptions\OrganizationParsingAlreadyRunningException;
use App\Services\YandexMaps\YandexMapsParserInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Organization\OrganizationReadService;
use Throwable;

class OrganizationService
{
    private const REVIEWS_LIMIT = 600;

    public function __construct(
        private readonly YandexMapsParserInterface $parser,
        private readonly OrganizationReadService $readService,
    ) {}

    public function saveAndParse(User $user, string $url): Organization
    {

        $organization = Organization::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'yandex_url' => $url,
            ],
            [
                'parse_status' => ParseStatus::Pending,
                'parse_error' => null,
            ]
        );

        $organization->update([
            'parse_status' => ParseStatus::Pending,
            'parse_error' => null,
        ]);

        $this->readService->forgetForUser($user);

        return $this->parse($organization);
    }

    public function parse(Organization $organization): Organization
    {
        if ($organization->parse_status === ParseStatus::Processing) {
            throw new OrganizationParsingAlreadyRunningException();
        }

        $startedAt = microtime(true);

        $attempt = ParseAttempt::query()->create([
            'organization_id' => $organization->id,
            'status' => ParseStatus::Processing,
            'reviews_requested_limit' => self::REVIEWS_LIMIT,
            'started_at' => now(),
            'meta' => [
                'parser' => $this->parser::class,
                'cache_used' => false,
            ],
        ]);

        $organization->update([
            'parse_status' => ParseStatus::Processing,
            'parse_error' => null,
        ]);

        try {
            $parsed = $this->parser->parse($organization->yandex_url, self::REVIEWS_LIMIT);
            $durationSeconds = round(microtime(true) - $startedAt, 3);

            DB::transaction(function () use ($organization, $attempt, $parsed, $durationSeconds): void {
                $finalStatus = $parsed->isPartial
                    ? ParseStatus::Partial
                    : ParseStatus::Success;

                $organization->update([
                    'name' => $parsed->name,
                    'rating' => $parsed->rating,
                    'ratings_count' => $parsed->ratingsCount,
                    'reviews_count' => $parsed->reviewsCount,
                    'parse_status' => $finalStatus,
                    'parse_error' => $parsed->warning,
                    'last_parsed_at' => now(),
                ]);
                Review::query()
                    ->where('organization_id', $organization->id)
                    ->delete();

                foreach ($parsed->reviews as $reviewData) {
                    $externalId = $reviewData->externalId ?: $this->makeReviewHash($reviewData);

                    Review::query()->updateOrCreate(
                        [
                            'organization_id' => $organization->id,
                            'external_id' => $externalId,
                        ],
                        [
                            'author_name' => $reviewData->authorName,
                            'reviewed_at' => $reviewData->reviewedAt,
                            'text' => $reviewData->text,
                            'rating' => $reviewData->rating,
                        ]
                    );
                }

                $attempt->update([
                    'status' => $finalStatus,
                    'reviews_collected' => count($parsed->reviews),
                    'finished_at' => now(),
                    'error_message' => $parsed->warning,
                    'meta' => [
                        'parser' => $this->parser::class,
                        'cache_used' => false,
                        'is_partial' => $parsed->isPartial,
                        'reviews_in_response' => count($parsed->reviews),
                        'duration_seconds' => $durationSeconds,
                    ],
                ]);
            });

            $this->readService->forgetForUser($organization->user);

            return $organization->refresh();
        } catch (Throwable $exception) {
            $durationSeconds = round(microtime(true) - $startedAt, 3);

            $this->markAsFailed($organization, $attempt, $exception, $durationSeconds);

            throw $exception;
        }
    }

    private function markAsFailed(
        Organization $organization,
        ParseAttempt $attempt,
        Throwable $exception,
        float $durationSeconds,
    ): void {
        Log::error('Organization parsing failed', [
            'organization_id' => $organization->id,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'duration_seconds' => $durationSeconds,
        ]);

        $organization->update([
            'parse_status' => ParseStatus::Failed,
            'parse_error' => $exception->getMessage(),
        ]);

        $attempt->update([
            'status' => ParseStatus::Failed,
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
            'meta' => [
                'parser' => $this->parser::class,
                'exception_class' => $exception::class,
                'duration_seconds' => $durationSeconds,
            ],
        ]);
    }
    private function extractBusinessId(string $url): string
    {
        if (preg_match('~/org/[^/]+/(\d+)~', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('~oid=(\d+)~', $url, $matches)) {
            return $matches[1];
        }

        return hash('sha256', $url);
    }

    private function makeReviewHash(object $reviewData): string
    {
        $payload = json_encode([
            'author' => $reviewData->authorName,
            'date' => $reviewData->reviewedAt,
            'text' => $reviewData->text,
            'rating' => $reviewData->rating,
        ], JSON_THROW_ON_ERROR);

        return 'hash-' . hash('sha256', $payload);
    }
}
