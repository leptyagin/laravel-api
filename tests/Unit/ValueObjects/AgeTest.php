<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Age;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AgeTest extends TestCase
{
    public function test_valid_age(): void
    {
        $birthDate = Date::now()->subYears(25);

        $age = Age::fromBirthDate($birthDate);

        $this->assertEquals(25, $age->value);
    }

    public function test_age_less_than_18_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $birthDate = Date::now()->subYears(17);

        Age::fromBirthDate($birthDate);
    }

    public function test_age_greater_than_100_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $birthDate = Date::now()->subYears(101);

        Age::fromBirthDate($birthDate);
    }

    public function test_age_exactly_18_is_valid(): void
    {
        $birthDate = Date::now()->subYears(18);

        $age = Age::fromBirthDate($birthDate);

        $this->assertEquals(18, $age->value);
    }

    public function test_age_exactly_100_is_valid(): void
    {
        $birthDate = Date::now()->subYears(100);

        $age = Age::fromBirthDate($birthDate);

        $this->assertEquals(100, $age->value);
    }
}
