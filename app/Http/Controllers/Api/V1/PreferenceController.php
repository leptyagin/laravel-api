<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\PreferenceRequest;
use App\Services\PreferenceService;
use Illuminate\Http\JsonResponse;

final class PreferenceController extends ApiController
{
    public function store(PreferenceRequest $request, PreferenceService $service): JsonResponse
    {
        $preference = $service->upsert(
            user: $request->user(),
            dto: $request->getDto()
        );

        return $this->success($preference);
    }
}
