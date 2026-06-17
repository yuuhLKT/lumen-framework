<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

interface Middleware
{
    /**
     * Processa a requisicao e delega para o proximo middleware/handler.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response;
}
