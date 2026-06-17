<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Support\HttpStatus;
use Closure;

final class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check($request)) {
            return Response::json(['error' => 'Token de autenticação inválido ou ausente.'], HttpStatus::UNAUTHORIZED);
        }

        return $next($request);
    }
}
