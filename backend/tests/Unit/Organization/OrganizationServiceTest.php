<?php

namespace Tests\Unit\Organization;

use App\Enums\ParseStatus;
use App\Models\Organization;
use App\Models\Review;
use App\Services\Organization\OrganizationService;
use App\Services\YandexMaps\Dto\ParsedOrganizationData;
use App\Services\YandexMaps\Dto\ParsedReviewData;
use App\Services\YandexMaps\YandexMapsParserInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrganizationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parse_updates_organization_and_saves_reviews(): void
    {
        $organization = Organization::factory()->create([
            'parse_status' => ParseStatus::Pending,
        ]);

        $this->mock(YandexMapsParserInterface::class)
            ->shouldReceive('parse')
            ->once()
            ->with($organization->yandex_url, 600)
            ->andReturn(new ParsedOrganizationData(
                name: 'Parsed Cafe',
                rating: 4.8,
                ratingsCount: 100,
                reviewsCount: 2,
                reviews: [
                    new ParsedReviewData('review-1', 'Alice', '2026-06-19 10:00:00', 'Great', 5),
                    new ParsedReviewData('review-2', 'Bob', '2026-06-18 10:00:00', 'Good', 4),
                ],
            ));

        $result = app(OrganizationService::class)->parse($organization);

        $this->assertEquals('Parsed Cafe', $result->name);
        $this->assertEquals(ParseStatus::Success, $result->parse_status);
        $this->assertEquals(2, $result->reviews_count);
        $this->assertEquals(2, $result->reviews()->count());

        $this->assertDatabaseHas('reviews', [
            'organization_id' => $organization->id,
            'external_id' => 'review-1',
            'author_name' => 'Alice',
            'rating' => 5,
        ]);
    }

    public function test_parse_replaces_old_reviews(): void
    {
        $organization = Organization::factory()->create();

        Review::factory()->create([
            'organization_id' => $organization->id,
            'external_id' => 'old-review',
            'author_name' => 'Old Author',
        ]);

        $this->mock(YandexMapsParserInterface::class)
            ->shouldReceive('parse')
            ->once()
            ->andReturn(new ParsedOrganizationData(
                name: 'Fresh Cafe',
                rating: 5.0,
                ratingsCount: 10,
                reviewsCount: 1,
                reviews: [
                    new ParsedReviewData('new-review', 'New Author', null, 'Fresh text', 5),
                ],
            ));

        app(OrganizationService::class)->parse($organization);

        $this->assertDatabaseMissing('reviews', [
            'organization_id' => $organization->id,
            'external_id' => 'old-review',
        ]);

        $this->assertDatabaseHas('reviews', [
            'organization_id' => $organization->id,
            'external_id' => 'new-review',
            'author_name' => 'New Author',
        ]);
    }

    public function test_parse_marks_organization_as_partial_when_parser_returns_partial_result(): void
    {
        $organization = Organization::factory()->create();

        $this->mock(YandexMapsParserInterface::class)
            ->shouldReceive('parse')
            ->once()
            ->andReturn(new ParsedOrganizationData(
                name: 'Partial Cafe',
                rating: 4.5,
                ratingsCount: 1000,
                reviewsCount: 600,
                reviews: [
                    new ParsedReviewData('review-1', 'Alice', null, 'Text', 5),
                ],
                isPartial: true,
                warning: 'Loaded 1 of 600 available reviews.',
            ));

        $result = app(OrganizationService::class)->parse($organization);

        $this->assertEquals(ParseStatus::Partial, $result->parse_status);
        $this->assertEquals('Loaded 1 of 600 available reviews.', $result->parse_error);
    }

    public function test_parse_marks_organization_as_failed_when_parser_fails(): void
    {
        $organization = Organization::factory()->create();

        $this->mock(YandexMapsParserInterface::class)
            ->shouldReceive('parse')
            ->once()
            ->andThrow(new RuntimeException('Parser failed.'));

        $this->expectException(RuntimeException::class);

        try {
            app(OrganizationService::class)->parse($organization);
        } finally {
            $organization->refresh();

            $this->assertEquals(ParseStatus::Failed, $organization->parse_status);
            $this->assertEquals('Parser failed.', $organization->parse_error);
        }
    }
}
