<?php

declare(strict_types=1);

namespace Tests;

use App\Exceptions\ValidationException;
use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidatesRequiredEmail(): void
    {
        $validated = Validator::validate([
            'email' => 'user@example.com',
        ], [
            'email' => 'required|email',
        ]);

        self::assertSame('user@example.com', $validated['email']);
    }

    public function testThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        Validator::validate(['email' => 'invalid'], ['email' => 'required|email']);
    }
}
