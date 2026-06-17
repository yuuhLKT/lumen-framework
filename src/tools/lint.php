<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));
$count = 0;

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    passthru('php -l ' . escapeshellarg($path), $code);

    if ($code !== 0) {
        exit($code);
    }

    $count++;
}

echo "Lint OK: {$count} PHP files\n";
