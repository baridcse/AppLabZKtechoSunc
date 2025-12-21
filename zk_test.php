<?php
// Show all important errors, but ignore deprecated notices from the library
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

$ip   = '192.168.1.253';
$port = 4370;

echo "Connecting to $ip:$port<br>";

$zk = new ZKTeco($ip, $port);

if (! $zk->connect()) {
    echo "❌ connect() returned false – device not reachable or port blocked.<br>";
    exit;
}

echo "✅ Connected!<br>Getting attendance...<br>";

$attendance = $zk->getAttendance();

echo "<pre>";
var_dump($attendance);
echo "</pre>";

$zk->disconnect();
echo "Disconnected.<br>";
