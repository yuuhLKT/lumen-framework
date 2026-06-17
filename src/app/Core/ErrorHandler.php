<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Support\HttpStatus;
use Throwable;

final class ErrorHandler
{
    public static function handle(Throwable $exception): Response
    {
        if ($exception instanceof HttpException) {
            $body = ['error' => $exception->getMessage()];

            if ($exception->errors() !== []) {
                $body['errors'] = $exception->errors();
            }

            return Response::json($body, $exception->status());
        }

        $config = require base_path('config/config.php');
        $body = [
            'error' => 'Erro interno do servidor.',
        ];

        if (($config['debug'] ?? false) === true) {
            $body['trace'] = $exception->getMessage();
        }

        return Response::json($body, HttpStatus::INTERNAL_SERVER_ERROR);
    }
}
