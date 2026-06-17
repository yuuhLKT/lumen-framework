<?php

declare(strict_types=1);

namespace App\Database\Drivers\Json;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Contracts\Table;
use RuntimeException;

final class JsonConnection implements DatabaseConnection
{
    /** @var array<string, array<int, array<string, mixed>>>|null */
    private ?array $transactionData = null;

    public function __construct(private readonly string $path)
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Nao foi possivel criar a pasta [{$directory}].");
        }

        if (!is_file($this->path)) {
            file_put_contents($this->path, '{}');
        }
    }

    public function name(): string
    {
        return 'json';
    }

    public function table(string $name): Table
    {
        return new JsonTable($this, $name);
    }

    public function execute(string $sql): void
    {
        throw new RuntimeException('O driver JSON nao executa SQL. Use migrations em PHP ou um driver SQL.');
    }

    public function beginTransaction(): void
    {
        if ($this->transactionData !== null) {
            throw new RuntimeException('Ja existe uma transacao JSON em andamento.');
        }

        $this->transactionData = $this->readFromFile();
    }

    public function commit(): void
    {
        if ($this->transactionData === null) {
            throw new RuntimeException('Nao existe transacao JSON em andamento.');
        }

        $data = $this->transactionData;
        $this->transactionData = null;
        $this->writeToFile($data);
    }

    public function rollBack(): void
    {
        if ($this->transactionData === null) {
            throw new RuntimeException('Nao existe transacao JSON em andamento.');
        }

        $this->transactionData = null;
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function read(): array
    {
        return $this->transactionData ?? $this->readFromFile();
    }

    /** @param array<string, array<int, array<string, mixed>>> $data */
    public function write(array $data): void
    {
        if ($this->transactionData !== null) {
            $this->transactionData = $data;

            return;
        }

        $this->writeToFile($data);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function readFromFile(): array
    {
        $content = file_get_contents($this->path);
        $data = json_decode($content === false ? '{}' : $content, true);

        return is_array($data) ? $data : [];
    }

    /** @param array<string, array<int, array<string, mixed>>> $data */
    private function writeToFile(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false || file_put_contents($this->path, $json) === false) {
            throw new RuntimeException("Nao foi possivel salvar o banco JSON [{$this->path}].");
        }
    }
}
