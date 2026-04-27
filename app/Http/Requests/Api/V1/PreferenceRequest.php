<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\PreferenceDTO;
use App\Enums\Gender;
use App\ValueObjects\Age;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class PreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gender' => ['required', new Enum(Gender::class)],
            'min_age' => ['required', 'integer', 'min:18', 'max:100'],
            'max_age' => ['required', 'integer', 'min:18', 'max:100', 'gte:min_age'],
        ];
    }

    public function getDto(): PreferenceDTO
    {
        return new PreferenceDTO(
            gender: Gender::from($this->validated('gender')),
            minAge: new Age($this->validated('min_age')),
            maxAge: new Age($this->validated('max_age')),
        );
    }
}
