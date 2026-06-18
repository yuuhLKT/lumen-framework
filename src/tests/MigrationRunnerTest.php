<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Database\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testStatusReturnsPendingAndExecutedMigrations(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-migrations-' . uniqid('', true);
        mkdir($path, 0777, true);

        $migrationPath = $path . DIRECTORY_SEPARATOR . '2026_01_01_000000_create_users.php';
        file_put_contents($migrationPath, '<?php return fn () => null;');

        $dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-migrations-db-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($dbPath);

        $runner = new MigrationRunner($connection);
        $status = $runner->status($path);

        self::assertCount(1, $status['all']);
        self::assertCount(1, $status['pending']);
        self::assertCount(0, $status['executed']);
        self::assertSame('2026_01_01_000000_create_users.php', $status['pending'][0]);

        $runner->run($path);
        $status = $runner->status($path);

        self::assertCount(0, $status['pending']);
        self::assertCount(1, $status['executed']);
        self::assertSame('2026_01_01_000000_create_users.php', $status['lastExecuted'][0] ?? null);

        @unlink($migrationPath);
        @rmdir($path);
        @unlink($dbPath);
    }

    public function testRollbackRemovesExecutedMigration(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-migrations-rollback-' . uniqid('', true);
        mkdir($path, 0777, true);

        $migrationPath = $path . DIRECTORY_SEPARATOR . '2026_01_01_000001_create_posts.php';
        file_put_contents($migrationPath, <<<'PHP'
<?php

return [
    'up' => fn () => null,
    'down' => fn () => null,
];
PHP);

        $dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-migrations-rollback-db-' . uniqid('', true) . '.json';
        $runner = new MigrationRunner(new JsonConnection($dbPath));

        $runner->run($path);
        $reverted = $runner->rollback($path);
        $status = $runner->status($path);

        self::assertSame(['2026_01_01_000001_create_posts.php'], $reverted);
        self::assertCount(0, $status['executed']);
        self::assertCount(1, $status['pending']);

        @unlink($migrationPath);
        @rmdir($path);
        @unlink($dbPath);
    }
}
