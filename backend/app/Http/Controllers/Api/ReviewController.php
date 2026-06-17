<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Organization;
use App\Models\Review;
use App\Support\Http\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $reviews = Review::query()
            ->where('organization_id', $organization?->id ?? 0)
            ->latest('reviewed_at')
            ->paginate(50);

        return response()->json([
            'data' => ReviewResource::collection($reviews)->resolve(),
            'meta' => ApiPagination::meta($reviews),
        ]);
    }
}
