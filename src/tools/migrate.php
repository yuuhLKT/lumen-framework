<?php

declare(strict_types=1);

use App\Database\MigrationRunner;

require_once __DIR__ . '/../bootstrap/app.php';

$runner = new MigrationRunner(db());
$ran = $runner->run(base_path('database/migrations'));

echo $ran === []
    ? "Nenhuma migration pendente.\n"
    : "Migrations executadas:\n- " . implode("\n- ", $ran) . "\n";
