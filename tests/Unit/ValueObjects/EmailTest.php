<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_valid_email(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', $email->toString());
    }

    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('invalid-email');
    }

    public function test_email_with_subdomain(): void
    {
        $email = new Email('user@mail.example.com');

        $this->assertEquals('user@mail.example.com', $email->toString());
    }

    public function test_email_with_plus_alias(): void
    {
        $email = new Email('user+alias@example.com');

        $this->assertEquals('user+alias@example.com', $email->toString());
    }
}
