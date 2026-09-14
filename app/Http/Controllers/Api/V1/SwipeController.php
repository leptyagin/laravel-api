<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\SwipeDirection;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\SwipeRequest;
use App\Models\User;
use App\Services\SwipeService;
use Illuminate\Http\JsonResponse;

final class SwipeController extends ApiController
{
    public function like(SwipeRequest $request, User $profile, SwipeService $service): JsonResponse
    {
        $result = $service->swipe($request->user(), $profile, SwipeDirection::Like);

        return $this->success($result);
    }

    public function dislike(SwipeRequest $request, User $profile, SwipeService $service): JsonResponse
    {
        $result = $service->swipe($request->user(), $profile, SwipeDirection::Dislike);

        return $this->success($result);
    }
}
