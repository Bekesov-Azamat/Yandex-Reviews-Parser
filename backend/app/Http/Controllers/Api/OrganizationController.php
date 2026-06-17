<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrganizationController extends Controller
{
    public function show(Request $request): OrganizationResource|JsonResponse
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $organization) {
            return response()->json([
                'data' => null,
            ]);
        }

        return OrganizationResource::make($organization);
    }

    public function store(
        StoreOrganizationRequest $request,
        OrganizationService $service,
    ): OrganizationResource|JsonResponse {
        try {
            $organization = $service->saveAndParse(
                user: $request->user(),
                url: $request->validated('url'),
            );

            return OrganizationResource::make($organization);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Failed to parse Yandex Maps organization.',
                'error' => $exception->getMessage(),
            ], 502);
        }
    }
}
