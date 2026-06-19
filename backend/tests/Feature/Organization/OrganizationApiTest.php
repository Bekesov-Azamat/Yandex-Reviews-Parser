<?php

namespace Tests\Feature\Organization;

use App\Models\Organization;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_organization_endpoints(): void
    {
        $this->getJson('/api/organizations')->assertUnauthorized();
        $this->getJson('/api/organization')->assertUnauthorized();
        $this->getJson('/api/organization/reviews')->assertUnauthorized();
    }

    public function test_user_can_list_only_own_organizations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownOrganization = Organization::factory()->create([
            'user_id' => $user->id,
            'name' => 'Own Organization',
        ]);

        Organization::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Other Organization',
        ]);

        $this->actingAs($user)
            ->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownOrganization->id)
            ->assertJsonPath('data.0.name', 'Own Organization');
    }

    public function test_user_can_get_selected_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'user_id' => $user->id,
            'name' => 'Selected Organization',
        ]);

        $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id)
            ->assertJsonPath('data.name', 'Selected Organization')
            ->assertJsonPath('data.saved_reviews_count', 0);
    }

    public function test_user_cannot_get_other_user_organization(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $organization = Organization::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}")
            ->assertNotFound();
    }

    public function test_reviews_are_paginated_by_selected_organization(): void
    {
        $user = User::factory()->create();

        $organization = Organization::factory()->create([
            'user_id' => $user->id,
            'reviews_count' => 60,
        ]);

        $otherOrganization = Organization::factory()->create([
            'user_id' => $user->id,
        ]);

        Review::factory()->count(60)->create([
            'organization_id' => $organization->id,
        ]);

        Review::factory()->count(5)->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}/reviews")
            ->assertOk()
            ->assertJsonCount(50, 'data')
            ->assertJsonPath('meta.total', 60);

        $this->actingAs($user)
            ->getJson("/api/organizations/{$organization->id}/reviews?page=2")
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 60);
    }
}
