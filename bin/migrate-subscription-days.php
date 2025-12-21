#!/usr/bin/env php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Persistence\Sql\Connection\PdoConnectionFactory;

$root = \dirname(__DIR__);

$offerPath = getenv('JSON_SUBSCRIPTION_OFFER_STORAGE') ?: 'storage/subscription_offers.json';
if (!preg_match('#^([A-Za-z]:\\\\|/)#', $offerPath)) {
    $offerPath = $root . '/' . ltrim($offerPath, '/\\');
}

if (is_file($offerPath)) {
    $records = json_decode((string) file_get_contents($offerPath), true);
    if (is_array($records)) {
        foreach ($records as $id => $record) {
            if (!isset($record['weekly_time_slots']) || !is_array($record['weekly_time_slots'])) {
                continue;
            }

            $updated = [];
            foreach ($record['weekly_time_slots'] as $slot) {
                if (isset($slot['start_day'], $slot['end_day'], $slot['start_time'], $slot['end_time'])) {
                    $slot['start_day'] = ((int) $slot['start_day']) % 7;
                    $slot['end_day'] = ((int) $slot['end_day']) % 7;
                    $updated[] = $slot;
                    continue;
                }

                if (!isset($slot['day'])) {
                    $updated[] = $slot;
                    continue;
                }

                $day = (int) $slot['day'];
                $updated[] = [
                    'start_day' => $day % 7,
                    'end_day' => $day % 7,
                    'start_time' => $slot['start'] ?? '00:00',
                    'end_time' => $slot['end'] ?? '24:00',
                ];
            }
            $record['weekly_time_slots'] = $updated;
            $records[$id] = $record;
        }

        file_put_contents($offerPath, json_encode($records, JSON_PRETTY_PRINT));
        echo "JSON updated: {$offerPath}\n";
    }
}

try {
    $pdo = (new PdoConnectionFactory())->create();
    $pdo->exec('UPDATE subscription_offer_slots SET start_day_of_week = MOD(start_day_of_week, 7)');
    $pdo->exec('UPDATE subscription_offer_slots SET end_day_of_week = MOD(end_day_of_week, 7)');
    $pdo->exec('UPDATE opening_hours SET end_day_of_week = COALESCE(end_day_of_week, day_of_week)');
    echo "SQL updated: subscription_offer_slots + opening_hours normalized.\n";
} catch (\Throwable $e) {
    echo "SQL skipped: " . $e->getMessage() . "\n";
}
