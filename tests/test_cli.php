<?php
require_once __DIR__ . '/../src/DeadlineBeacon.php';

use DeadlineBeacon\Db;
use DeadlineBeacon\App;

$schema = __DIR__ . '/../migrations/001_init_sqlite.sql';
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
    'INSERT INTO deadline_beacon_deadlines
        (title, organization, application_url, deadline_date, timezone, status, notes, created_at, updated_at)
     VALUES
        (:title, :org, :url, :date, :tz, :status, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
    [
        ':title' => 'Community Momentum Award',
        ':org' => 'Momentum Partners',
        ':url' => 'https://example.org/momentum',
        ':date' => '2026-03-10',
        ':tz' => 'America/Chicago',
        ':status' => 'open',
        ':notes' => 'Requires community project proposal.',
    ]
);

$overdueDate = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

$db->execute(
    'INSERT INTO deadline_beacon_deadlines
        (title, organization, application_url, deadline_date, timezone, status, notes, created_at, updated_at)
     VALUES
        (:title, :org, :url, :date, :tz, :status, :notes, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
    [
        ':title' => 'Leadership Spark Award',
        ':org' => 'Spark Leadership Network',
        ':url' => 'https://example.org/spark',
        ':date' => $overdueDate,
        ':tz' => 'America/Los_Angeles',
        ':status' => 'open',
        ':notes' => 'Submit leadership impact statement.',
    ]
);

$oldSent = (new DateTimeImmutable('-20 days'))->format('Y-m-d H:i:s');
$recentSent = (new DateTimeImmutable('-3 days'))->format('Y-m-d H:i:s');

$db->execute(
    'INSERT INTO deadline_beacon_notifications
        (deadline_id, channel, sent_at, message)
     VALUES
        (:deadline_id, :channel, :sent_at, :message)',
    [
        ':deadline_id' => 1,
        ':channel' => 'slack',
        ':sent_at' => $oldSent,
        ':message' => 'Initial reminder scheduled for STEM Bridge Scholars.',
    ]
);

$db->execute(
    'INSERT INTO deadline_beacon_notifications
        (deadline_id, channel, sent_at, message)
     VALUES
        (:deadline_id, :channel, :sent_at, :message)',
    [
        ':deadline_id' => 2,
        ':channel' => 'email',
        ':sent_at' => $recentSent,
        ':message' => 'Community Momentum Award reminder sent.',
    ]
);

$db->execute(
    'INSERT INTO deadline_beacon_notifications
        (deadline_id, channel, sent_at, message)
     VALUES
        (:deadline_id, :channel, :sent_at, :message)',
    [
        ':deadline_id' => 3,
        ':channel' => 'sms',
        ':sent_at' => $recentSent,
        ':message' => 'Leadership Spark Award reminder sent.',
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

$output = run_app($app, ['cli', 'overdue', '--days=14']);
if (strpos($output, 'Leadership Spark Award') === false) {
    fwrite(STDERR, "Expected overdue output to include overdue deadlines.\n");
    exit(1);
}

$output = run_app($app, ['cli', 'nudge', '--within=60', '--stale-days=14']);
if (strpos($output, 'STEM Bridge Scholars') === false || strpos($output, 'Community Momentum Award') !== false) {
    fwrite(STDERR, "Expected nudge output to include only stale deadlines.\n");
    exit(1);
}

echo "OK\n";
