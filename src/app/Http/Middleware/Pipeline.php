<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

final class Pipeline
{
    /** @var array<int, class-string<Middleware>> */
    private array $middlewares;

    /**
     * @param array<int, class-string<Middleware>> $middlewares
     */
    public function __construct(array $middlewares)
    {
        $this->middlewares = array_values($middlewares);
    }

    /**
     * Executa o pipeline e retorna a resposta final.
     *
     * @param Closure(Request): Response $destination
     */
    public function then(Closure $destination): Closure
    {
        return function (Request $request) use ($destination): Response {
            $stack = $destination;

            foreach (array_reverse($this->middlewares) as $middleware) {
                $stack = function (Request $request) use ($middleware, $stack): Response {
                    return (new $middleware())->handle($request, $stack);
                };
            }

            return $stack($request);
        };
    }
}
