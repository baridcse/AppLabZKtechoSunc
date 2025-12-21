<?php
// attendance.php – returns JSON of F22 logs

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0); // for production, hide errors in output

require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

header('Content-Type: application/json');

$ip   = '192.168.1.253';
$port = 4370;

try {
    $zk = new ZKTeco($ip, $port);

    if (! $zk->connect()) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Could not connect to device $ip:$port",
        ]);
        exit;
    }

    $logs = $zk->getAttendance();

    $zk->disconnect();

    echo json_encode([
        'status' => 'success',
        'count'  => is_array($logs) ? count($logs) : 0,
        'data'   => $logs,
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
