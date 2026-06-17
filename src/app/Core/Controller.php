<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;
use App\Support\HttpStatus;
use App\Validation\Validator;

abstract class Controller
{
    /** @param mixed $data */
    protected function json(mixed $data, int $status = HttpStatus::OK): Response
    {
        return Response::json($data, $status);
    }

    protected function html(string $content, int $status = HttpStatus::OK): Response
    {
        return Response::html($content, $status);
    }

    /** @param mixed $data */
    protected function ok(mixed $data = ['success' => true]): Response
    {
        return $this->json($data, HttpStatus::OK);
    }

    /** @param mixed $data */
    protected function created(mixed $data): Response
    {
        return $this->json($data, HttpStatus::CREATED);
    }

    protected function noContent(): Response
    {
        return Response::text('', HttpStatus::NO_CONTENT);
    }

    /** @param array<string, string|array<int, string>> $rules */
    protected function validate(Request $request, array $rules): array
    {
        return Validator::validate($request->input(), $rules);
    }

    /** @return never */
    protected function abort(int $status, string $message, array $errors = []): void
    {
        throw new HttpException($message, $status, $errors);
    }
}
