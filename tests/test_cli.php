<?php
require_once __DIR__ . '/../src/DeadlineBeacon.php';

use DeadlineBeacon\Db;
use DeadlineBeacon\App;

$schema = __DIR__ . '/../migrations/001_init.sql';
$dsn = 'sqlite::memory:';
$db = Db::fromDsn($dsn);

$schemaSql = file_get_contents($schema);
$db->execSql($schemaSql);

$db->execute(
    'INSERT INTO deadline_beacon_deadlines
        (title, organization, application_url, deadline_date, timezone, status, notes, created_at, updated_at)
     VALUES
        (:title, :org, :url, :date, :tz, :status, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
    [
        ':title' => 'STEM Bridge Scholars',
        ':org' => 'STEM Bridge Fund',
        ':url' => 'https://example.org/stem-bridge',
        ':date' => '2026-03-01',
        ':tz' => 'America/New_York',
        ':status' => 'open',
        ':notes' => 'Requires two letters of recommendation.',
    ]
);

$db->execute(
    'INSERT INTO deadline_beacon_notifications
        (deadline_id, channel, sent_at, message)
     VALUES
        (:deadline_id, :channel, CURRENT_TIMESTAMP, :message)',
    [
        ':deadline_id' => 1,
        ':channel' => 'slack',
        ':message' => 'Initial reminder scheduled for STEM Bridge Scholars.',
    ]
);

$app = new App($db);

function run_app(App $app, array $args): string {
    ob_start();
    $app->run($args);
    return ob_get_clean();
}

$output = run_app($app, ['cli', 'list', '--within=60']);
if (strpos($output, 'STEM Bridge Scholars') === false) {
    fwrite(STDERR, "Expected seed data in list output.\n");
    exit(1);
}

$output = run_app($app, ['cli', 'report', '--within=30']);
if (strpos($output, 'Deadlines in next') === false) {
    fwrite(STDERR, "Expected report output.\n");
    exit(1);
}

echo "OK\n";
