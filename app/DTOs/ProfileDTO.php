<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\City;
use App\Enums\Gender;
use App\Enums\Status;
use Carbon\Carbon;

final readonly class ProfileDTO
{
    public function __construct(
        public string $name,
        public Carbon $birthDate,
        public City $city,
        public Gender $gender,
        public string $bio,
        public Status $status,
    ) {}
}
