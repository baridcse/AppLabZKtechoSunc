<?php
require 'vendor/autoload.php';

use rats\Zkteco\Lib\ZKTeco;

$ip   = "192.168.1.253";
$port = 4370;

echo "Connecting to $ip:$port ...<br>";

try {
    $zk = new ZKTeco($ip, $port);
    $ok = $zk->connect();

    if (!$ok) {
        echo "❌ Failed to connect<br>";
        exit;
    }

    echo "✅ Connected!<br>";

    $attendance = $zk->getAttendance();

    echo "<pre>";
    print_r($attendance);
    echo "</pre>";

    $zk->disconnect();
    echo "Disconnected<br>";

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
