<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\HttpStatus;

final class ValidationException extends HttpException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(array $errors)
    {
        parent::__construct('Dados invalidos.', HttpStatus::UNPROCESSABLE_ENTITY, $errors);
    }
}
