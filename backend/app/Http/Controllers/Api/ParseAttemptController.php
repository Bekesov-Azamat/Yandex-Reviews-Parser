<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ParseAttemptResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ParseAttemptController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $attempts = $organization
            ? $organization->parseAttempts()->latest()->limit(10)->get()
            : collect();

        return ParseAttemptResource::collection($attempts);
    }
    public function indexByOrganization(Request $request, Organization $organization): AnonymousResourceCollection
    {
        if ($organization->user_id !== $request->user()->id) {
            abort(404);
        }

        $attempts = $organization->parseAttempts()
            ->latest()
            ->limit(10)
            ->get();

        return ParseAttemptResource::collection($attempts);
    }
}
