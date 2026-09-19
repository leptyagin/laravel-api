<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeedPageDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FeedPageDTO
 */
final class FeedPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'items' => FeedProfileResource::collection($this->items),
            'next_cursor' => $this->nextCursor,
        ];
    }
}
