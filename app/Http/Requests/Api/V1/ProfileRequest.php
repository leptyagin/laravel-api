<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\DTOs\ProfileDTO;
use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rules\Enum;

final class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:4', 'max:40'],
            'birth_date' => ['required', 'date', 'before:today'],
            'city' => ['required', new Enum(City::class)],
            'gender' => ['required', new Enum(Gender::class)],
            'bio' => ['required', 'string', 'min:1', 'max:500'],
            'status' => ['required', new Enum(Status::class)],
        ];
    }

    public function getDto(): ProfileDTO
    {
        return new ProfileDTO(
            name: $this->input('name'),
            birthDate: Date::parse($this->input('birth_date')),
            city: City::from($this->input('city')),
            gender: Gender::from($this->input('gender')),
            bio: $this->input('bio'),
            status: Status::from($this->input('status'))
        );
    }
}
