#!/usr/bin/env php
<?php declare(strict_types=1);

$root = \dirname(__DIR__);

$files = [
    getenv('JSON_USER_STORAGE') ?: 'storage/users.json',
    getenv('JSON_PARKING_STORAGE') ?: 'storage/parkings.json',
    getenv('JSON_RESERVATION_STORAGE') ?: 'storage/reservations.json',
];

foreach ($files as $file) {
    $path = $file;
    if (!preg_match('#^([A-Za-z]:\\\\|/)#', $path)) {
        $path = $root . '/' . ltrim($path, '/\\');
    }

    $dir = \dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($path, "{}\n");
    echo "Reset: {$path}\n";
}
