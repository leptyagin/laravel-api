<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use App\ValueObjects\Age;

final readonly class ProfileDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public Age $age,
        public City $city,
        public string $bio,
        public Status $status,
        public ?string $photo,
        public Gender $gender,
        public Gender $lookingFor,
        public Age $partnerMaxAge,
        public Age $partnerMinAge,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age->value,
            'city' => $this->city->value,
            'bio' => $this->bio,
            'status' => $this->status->value,
            'photo' => $this->photo,
            'gender' => $this->gender->value,
            'lookingFor' => $this->lookingFor->value,
            'partnerMaxAge' => $this->partnerMaxAge->value,
            'partnerMinAge' => $this->partnerMinAge->value,
        ];
    }
}
