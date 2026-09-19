<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\FeedRequest;
use App\Http\Resources\FeedPageResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;

final class FeedController extends ApiController
{
    public function index(FeedRequest $request, FeedService $service): JsonResponse
    {
        $page = $service->getPage(
            user: $request->user(),
            params: $request->getDto(),
        );

        return $this->success(new FeedPageResource($page));
    }
}
