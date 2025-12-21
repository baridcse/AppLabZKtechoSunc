<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$autoload = __DIR__ . '/vendor/autoload.php';   // or '../vendor/autoload.php'

echo "Looking for: $autoload<br>";

if (!file_exists($autoload)) {
    echo "❌ vendor/autoload.php NOT FOUND<br>";
    exit;
}

require $autoload;

echo "✅ autoload loaded<br>";

use rats\Zkteco\Lib\ZKTeco;

echo "✅ ZKTeco class is visible<br>";

