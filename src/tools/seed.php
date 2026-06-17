<?php

declare(strict_types=1);

use App\Database\SeederRunner;

require_once __DIR__ . '/../bootstrap/app.php';

$runner = new SeederRunner(db());
$ran = $runner->run(base_path('database/seeders'));

echo $ran === []
    ? "Nenhum seeder encontrado.\n"
    : "Seeders executados:\n- " . implode("\n- ", $ran) . "\n";
