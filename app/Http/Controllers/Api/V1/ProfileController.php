<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\ProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

final class ProfileController extends ApiController
{
    public function store(ProfileRequest $request, ProfileService $service): JsonResponse
    {
        $preference = $service->upsert(
            user: $request->user(),
            dto: $request->getDto()
        );

        return $this->success($preference);
    }
}
