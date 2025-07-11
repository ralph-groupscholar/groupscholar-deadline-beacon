#!/usr/bin/env php
<?php
require_once __DIR__ . '/../src/DeadlineBeacon.php';

use DeadlineBeacon\App;

$app = new App();
$app->run($argv);
