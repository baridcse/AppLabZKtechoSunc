<?php
/**

 *
 * Runs on your local Mac/XAMPP.
 * 1) Connects to ZKTeco F22 device
 * 2) Reads attendance logs
 * 3) Sends them to your Laravel server (App Lab Ltd – Attendance & HR Portal)
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/constants.php';

use Rats\Zkteco\Lib\ZKTeco;

/* -------------------------------------------------
 *  CONFIGURATION – CHANGE THESE FOR YOUR SETUP
 * ------------------------------------------------- */

// ZKTeco F22 device config
$deviceIp   = DEFAULT_DEVICE_IP;  // your F22 IP
$devicePort = DEFAULT_DEVICE_PORT; // default ZKTeco port

// Laravel API config - now using constants from constants.php
$cloudBaseUrl = CLOUD_BASE_URL;
$secretToken  = SECRET_TOKEN;       // same as ZK_SECRET_TOKEN in Laravel .env

// Final Laravel API URL - now using constant from constants.php
$cloudUrl = CLOUD_URL;

/* -------------------------------------------------
 *  1) CONNECT TO DEVICE
 * ------------------------------------------------- */

echo "Connecting to device {$deviceIp}:{$devicePort}...\n";

$zk = new ZKTeco($deviceIp, $devicePort);

if (!$zk->connect()) {
    die("❌ Could not connect to device. Check IP/port and network.\n");
}

echo "✅ Connected! Fetching attendance logs...\n";

/* -------------------------------------------------
 *  2) GET ATTENDANCE LOGS
 * ------------------------------------------------- */

$logs = $zk->getAttendance();

if (empty($logs)) {
    echo "ℹ️ No attendance logs found on device.\n";
    $zk->disconnect();
    exit;
}

echo "📥 Got " . count($logs) . " attendance records.\n";

/* Optional: show first few logs for debugging
foreach (array_slice($logs, 0, 5) as $i => $row) {
    echo "Log $i: " . print_r($row, true) . "\n";
}
*/

/* -------------------------------------------------
 *  3) PREPARE JSON PAYLOAD FOR LARAVEL
 * ------------------------------------------------- */

$payload = [
    'device_ip' => $deviceIp,
    'token'     => $secretToken,
    'data'      => $logs,
];

$jsonPayload = json_encode($payload);

if ($jsonPayload === false) {
    $zk->disconnect();
    die("❌ Failed to JSON-encode logs: " . json_last_error_msg() . "\n");
}

/* -------------------------------------------------
 *  4) SEND TO LARAVEL VIA CURL
 * ------------------------------------------------- */

echo "🌐 Sending logs to Laravel API: {$cloudUrl}\n";

$ch = curl_init($cloudUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);

curl_close($ch);

echo "🔁 Laravel HTTP status: {$httpCode}\n";
echo "🔁 Laravel response: {$response}\n";

if (!empty($curlErr)) {
    $zk->disconnect();
    die("❌ cURL error: {$curlErr}\n");
}

/* -------------------------------------------------
 *  5) HANDLE LARAVEL RESPONSE
 * ------------------------------------------------- */

$respData = json_decode($response, true);

if ($httpCode !== 200 || !is_array($respData) || ($respData['status'] ?? '') !== 'success') {
    $zk->disconnect();
    die("❌ Laravel did not confirm success. Not clearing logs.\n");
}

echo "✅ Laravel accepted logs. Records received: " . ($respData['received'] ?? 'N/A') .
     ", saved: " . ($respData['saved'] ?? 'N/A') . "\n";

/* -------------------------------------------------
 *  6) CLEAR DEVICE LOGS (OPTIONAL BUT RECOMMENDED)
 * ------------------------------------------------- */

echo "🧹 Clearing attendance logs from device...\n";

// // reconnect (some libraries require fresh connection)
// $zk2 = new ZKTeco($deviceIp, $devicePort);
// if ($zk2->connect()) {
//     $zk2->clearAttendance();
//     $zk2->disconnect();
//     echo "✅ Device logs cleared.\n";
// } else {
//     echo "⚠️ Could not reconnect to device to clear logs. Logs may still be on device.\n";
// }

// Disconnect original connection just in case
$zk->disconnect();

echo "🎉 sync_to_cloud.php finished.\n";
