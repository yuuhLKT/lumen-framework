<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Database\SeederRunner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SeederRunnerTest extends TestCase
{
    public function testRunExecutesSeedersInOrder(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-seeders-' . uniqid('', true);
        mkdir($path, 0777, true);

        file_put_contents($path . DIRECTORY_SEPARATOR . '001_users.php', <<<'PHP'
<?php
return fn ($db) => $db->table('users')->insert(['name' => 'One']);
PHP);
        file_put_contents($path . DIRECTORY_SEPARATOR . '002_users.php', <<<'PHP'
<?php
return fn ($db) => $db->table('users')->insert(['name' => 'Two']);
PHP);

        $dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-seeders-db-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($dbPath);
        $ran = (new SeederRunner($connection))->run($path);

        self::assertSame(['001_users.php', '002_users.php'], $ran);
        self::assertSame(['One', 'Two'], array_column($connection->table('users')->all(), 'name'));

        foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($path);
        @unlink($dbPath);
    }

    public function testRunRejectsInvalidSeeder(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-seeders-invalid-' . uniqid('', true);
        mkdir($path, 0777, true);
        file_put_contents($path . DIRECTORY_SEPARATOR . 'invalid.php', '<?php return [];');

        $this->expectException(RuntimeException::class);

        try {
            (new SeederRunner(new JsonConnection(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-seeders-invalid-db-' . uniqid('', true) . '.json')))->run($path);
        } finally {
            @unlink($path . DIRECTORY_SEPARATOR . 'invalid.php');
            @rmdir($path);
        }
    }
}
