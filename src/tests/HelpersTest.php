<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEnvReadsServerAndDefaultValues(): void
    {
        $_SERVER['HELPERS_TEST_KEY'] = 'server-value';

        self::assertSame('server-value', env('HELPERS_TEST_KEY'));
        self::assertSame('fallback', env('HELPERS_MISSING_KEY', 'fallback'));

        unset($_SERVER['HELPERS_TEST_KEY']);
    }

    public function testLoadEnvFileParsesSimpleKeyValueLines(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-env-' . uniqid('', true);
        file_put_contents($path, "# comment\nFOO=bar\nQUOTED=\"baz\"\nINVALID\n");

        load_env_file($path);

        self::assertSame('bar', env('FOO'));
        self::assertSame('baz', env('QUOTED'));
        self::assertSame('fallback', env('INVALID', 'fallback'));

        @unlink($path);
        unset($_ENV['FOO'], $_SERVER['FOO'], $_ENV['QUOTED'], $_SERVER['QUOTED']);
        putenv('FOO');
        putenv('QUOTED');
    }

    public function testBasePathAndPathFromBase(): void
    {
        self::assertDirectoryExists(base_path());
        self::assertStringEndsWith('storage/database.json', str_replace('\\', '/', path_from_base('storage/database.json')));
        self::assertSame('/tmp/file.txt', path_from_base('/tmp/file.txt'));
    }

    public function testDdFunctionIsAvailable(): void
    {
        self::assertTrue(function_exists('dd'));
    }
}
