<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\PhotoRequest;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;

final class PhotoController extends ApiController
{
    public function store(PhotoRequest $request, PhotoService $service): JsonResponse
    {
        $photo = $service->upload(
            user: $request->user(),
            dto: $request->getDto()
        );

        return $this->success($photo);
    }
}
