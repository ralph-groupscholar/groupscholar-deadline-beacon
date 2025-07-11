<?php
require_once __DIR__ . '/../src/DeadlineBeacon.php';

use DeadlineBeacon\Db;

$dsn = getenv('DEADLINE_BEACON_DSN');
if (!$dsn) {
    fwrite(STDERR, "Set DEADLINE_BEACON_DSN to run bootstrap.\n");
    exit(1);
}

$user = getenv('DEADLINE_BEACON_DB_USER') ?: null;
$pass = getenv('DEADLINE_BEACON_DB_PASS') ?: null;

$db = Db::fromDsn($dsn, $user, $pass);

$schemaSql = file_get_contents(__DIR__ . '/../migrations/001_init.sql');
$db->execSql($schemaSql);

$seedSql = file_get_contents(__DIR__ . '/seed.sql');
$db->execSql($seedSql);

echo "Bootstrap complete.\n";
