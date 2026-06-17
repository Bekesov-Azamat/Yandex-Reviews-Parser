<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OrganizationReadService
{
    public function getForUser(User $user): ?Organization
    {
        return Cache::remember(
            key: $this->cacheKey($user),
            ttl: now()->addMinutes(10),
            callback: fn () => Organization::query()
                ->where('user_id', $user->id)
                ->first()
        );
    }

    public function forgetForUser(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return 'users:'.$user->id.':organization';
    }
}
