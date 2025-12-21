<?php
/**
 * sync_users_to_laravel.php
 *
 * 1) Connects to ZKTeco F22
 * 2) Fetches all users
 * 3) Sends them to Laravel API /api/zk/push-users
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

// 🔧 CONFIG
$deviceIp   = '192.168.1.253';                 // your F22 IP
$devicePort = 4370;                            // default port

$cloudBaseUrl = 'http://127.0.0.1:8000';
 //$cloudBaseUrl = 'https://hrm.applabltd.co';     // Laravel URL (change if on server)
$secretToken  = 'SECRET564FDG';      // same as .env ZK_SECRET_TOKEN

$pushUrl = $cloudBaseUrl . '/api/zk/push-users';

// 1) Connect to device
echo "Connecting to device {$deviceIp}:{$devicePort}...\n";

$zk = new ZKTeco($deviceIp, $devicePort);

if (!$zk->connect()) {
    die("❌ Could not connect to device.\n");
}

echo "✅ Connected. Fetching users...\n";

// 2) Fetch users from device
// Try getUser(); if error, switch to getUsers();
$users = $zk->getUser();
// $users = $zk->getUsers(); // use this if your version requires plural

if (empty($users)) {
    echo "ℹ️ No users found on device or function returned empty array.\n";
    $zk->disconnect();
    exit;
}

echo "📥 Got " . count($users) . " user(s) from device.\n";

// Optional: Show first few for debug
$sample = array_slice($users, 0, 3);
echo "Sample users:\n";
print_r($sample);

// 3) Send to Laravel
$payload = [
    'token' => $secretToken,
    'users' => $users,
];

$jsonPayload = json_encode($payload);

if ($jsonPayload === false) {
    $zk->disconnect();
    die("❌ Failed to encode users as JSON: " . json_last_error_msg() . "\n");
}

echo "🌐 Sending to Laravel: {$pushUrl}\n";

$ch = curl_init($pushUrl);
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

echo "🔁 HTTP status: {$httpCode}\n";
echo "🔁 Response: {$response}\n";

if (!empty($curlErr)) {
    echo "❌ cURL error: {$curlErr}\n";
}

$zk->disconnect();
echo "🔌 Disconnected from device.\n";
echo "🎉 Done.\n";
