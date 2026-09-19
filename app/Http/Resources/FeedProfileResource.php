<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\ProfileDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfileDTO
 */
final class FeedProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age->value,
            'city' => $this->city->value,
            'bio' => $this->bio,
            'photo' => $this->photo,
            'gender' => $this->gender->value,
        ];
    }
}
