<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\PhotoDTO;
use Illuminate\Foundation\Http\FormRequest;

final class PhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    public function getDto(): PhotoDTO
    {
        return new PhotoDTO(
            file: $this->file('photo')
        );
    }
}
