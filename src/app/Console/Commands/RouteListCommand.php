<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Core\Router;

final class RouteListCommand implements Command
{
    public function name(): string
    {
        return 'route:list';
    }

    public function description(): string
    {
        return 'Lista as rotas registradas.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $router = $this->loadRouter();

        if ($router === null) {
            fwrite(STDERR, "Nao foi possivel carregar as rotas.\n");

            return 1;
        }

        $routes = $router->routes();

        if ($routes === []) {
            echo "Nenhuma rota registrada.\n";

            return 0;
        }

        echo "Rotas registradas:\n\n";
        echo sprintf("%-8s %-30s %s\n", 'METHOD', 'PATH', 'HANDLER');
        echo str_repeat('-', 70) . "\n";

        foreach ($routes as $method => $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $handler = $route['handler'];

                if (is_array($handler)) {
                    /** @var array{0: string, 1: string} $handler */
                    $handler = $handler;
                    $handler = $handler[0] . '::' . $handler[1];
                }

                if (!is_string($handler)) {
                    $handler = 'Closure';
                }

                echo sprintf("%-8s %-30s %s\n", $method, $route['path'], $handler);
            }
        }

        return 0;
    }

    private function loadRouter(): ?Router
    {
        $routesFile = base_path('routes/web.php');

        if (!is_file($routesFile)) {
            return null;
        }

        $router = new Router();
        require $routesFile;

        return $router;
    }
}
