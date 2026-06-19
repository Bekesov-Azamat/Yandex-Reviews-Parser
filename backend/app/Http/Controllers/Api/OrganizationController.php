<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\Organization\Exceptions\OrganizationParsingAlreadyRunningException;
use App\Services\YandexMaps\Exceptions\YandexMapsParserException;
use App\Support\Http\ApiError;
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
    public function index(Request $request): JsonResponse
    {
        $organizations = Organization::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => OrganizationResource::collection($organizations),
        ]);
    }
    public function showById(Request $request, Organization $organization): OrganizationResource|JsonResponse
    {
        if ($organization->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Organization not found.',
            ], 404);
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
        } catch (OrganizationParsingAlreadyRunningException $exception) {
            return ApiError::response(
                code: 'PARSING_ALREADY_RUNNING',
                message: $exception->getMessage(),
                status: 409,
            );
        } catch (YandexMapsParserException $exception) {
            report($exception);

            return ApiError::response(
                code: $exception->errorCode(),
                message: $exception->getMessage(),
                status: 502,
            );
        } catch (Throwable $exception) {
            report($exception);

            return ApiError::response(
                code: 'UNEXPECTED_PARSER_ERROR',
                message: 'Unexpected parser error.',
                status: 500,
                meta: [
                    'exception' => $exception::class,
                ],
            );
        }
    }
}
