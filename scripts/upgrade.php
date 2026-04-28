<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$files = array_filter(
    glob(__DIR__ . '/../database/migrations/*.sql') ?: [],
    static fn (string $file): bool => !str_starts_with(basename($file), '001_')
);
sort($files);

foreach ($files as $file) {
    db()->exec((string) file_get_contents($file));
    echo 'Applied upgrade: ' . basename($file) . PHP_EOL;
}

echo 'Done.' . PHP_EOL;
