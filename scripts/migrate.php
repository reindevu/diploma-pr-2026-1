<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$files = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    db()->exec((string) file_get_contents($file));
    echo 'Applied migration: ' . basename($file) . PHP_EOL;
}

echo 'Done.' . PHP_EOL;
