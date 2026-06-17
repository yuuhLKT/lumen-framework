<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\HttpStatus;
use RuntimeException;

class HttpException extends RuntimeException
{
    /** @param array<string, mixed> $errors */
    public function __construct(
        string $message,
        private readonly int $status = HttpStatus::BAD_REQUEST,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function errors(): array
    {
        return $this->errors;
    }
}
