<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\FeedPageParamsDTO;
use Illuminate\Foundation\Http\FormRequest;

final class FeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.config('feed.max_limit')],
        ];
    }

    public function getDto(): FeedPageParamsDTO
    {
        return new FeedPageParamsDTO(
            cursor: $this->filled('cursor') ? $this->integer('cursor') : null,
            limit: $this->integer('limit', (int) config('feed.default_limit')),
        );
    }
}
