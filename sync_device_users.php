<?php
/**
 * sync_device_users.php
 *
 * Runs on your local Mac/XAMPP.
 * 1) Calls Laravel to get pending ZK jobs (create_user).
 * 2) Connects to ZKTeco F22 and executes them.
 * 3) Reports success/failed back to Laravel.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

/* -------------------------------------------------
 *  CONFIGURATION – CHANGE THESE FOR YOUR SETUP
 * ------------------------------------------------- */

// ZKTeco F22 device config
$deviceIp   = '192.168.1.253';  // your F22 IP
$devicePort = 4370;             // default ZKTeco port

// Laravel API config
$cloudBaseUrl = 'https://hrm.applabltd.co';           // CHANGE if Laravel is on server
$secretToken  = 'SERECT564FDG';        // same as ZK_SECRET_TOKEN in Laravel .env

// Built URLs
$jobsUrlTemplate     = $cloudBaseUrl . '/api/zk/jobs?token=' . urlencode($secretToken);
$completeUrlTemplate = $cloudBaseUrl . '/api/zk/jobs/{id}/complete?token=' . urlencode($secretToken);

/* -------------------------------------------------
 *  1) FETCH PENDING JOBS FROM LARAVEL
 * ------------------------------------------------- */

echo "🔍 Fetching pending jobs from Laravel: {$jobsUrlTemplate}\n";

$ch = curl_init($jobsUrlTemplate);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if (!empty($curlErr)) {
    die("❌ cURL error while getting jobs: {$curlErr}\n");
}

if ($httpCode !== 200) {
    die("❌ Unexpected HTTP status while getting jobs: {$httpCode}\nResponse: {$response}\n");
}

$data = json_decode($response, true);
if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
    die("❌ Invalid jobs response from Laravel: {$response}\n");
}

$jobs = $data['jobs'] ?? [];

if (empty($jobs)) {
    echo "ℹ️ No pending jobs.\n";
    exit;
}

echo "📥 Received " . count($jobs) . " job(s) from Laravel.\n";

/* -------------------------------------------------
 *  2) CONNECT TO ZKTECO DEVICE ONCE
 * ------------------------------------------------- */

echo "🔌 Connecting to device {$deviceIp}:{$devicePort}...\n";

$zk = new ZKTeco($deviceIp, $devicePort);

if (!$zk->connect()) {
    die("❌ Could not connect to device. Check IP/port and network.\n");
}

echo "✅ Connected to device.\n";

/* -------------------------------------------------
 *  3) PROCESS EACH JOB
 * ------------------------------------------------- */

foreach ($jobs as $job) {
    $jobId   = $job['id'];
    $jobType = $job['type'];

    echo "➡️ Processing job ID {$jobId} (type: {$jobType})...\n";

    // Payload may be JSON string or already array
    $payload = $job['payload'];
    if (is_string($payload)) {
        $payload = json_decode($payload, true);
    }
    if (!is_array($payload)) {
        echo "⚠️ Job {$jobId} has invalid payload. Marking as failed.\n";
        reportJobResult($completeUrlTemplate, $jobId, 'failed', 'Invalid payload format');
        continue;
    }

    $status = 'success';
    $error  = null;

    try {
        if ($jobType === 'create_user') {
            /**
             * NOTE: The exact parameters of setUser() depend on the rats/zkteco version.
             * Common signature: setUser($uid, $userid, $name, $password, $role, $cardNumber)
             */

            $uid        = (int)($payload['device_user_id'] ?? 0);         // internal ID on device
            $userid     = (string)($payload['device_user_id'] ?? '');     // user ID string (what you see on device)
            $name       = (string)($payload['name'] ?? $userid);          // display name
            $password   = (string)($payload['password'] ?? '');           // usually empty
            $role       = (int)($payload['role'] ?? 0);                   // 0 = user, 14 = admin (depends on device)
            $cardNumber = (int)($payload['card_number'] ?? 0);            // if you use cards; else 0

            echo "   ➕ Creating user on device: uid={$uid}, userid={$userid}, name={$name}\n";

            $ok = $zk->setUser($uid, $userid, $name, $password, $role, $cardNumber);
            if (!$ok) {
                throw new Exception('setUser() returned false');
            }

            echo "   ✅ User created successfully on device for job {$jobId}.\n";

        } elseif ($jobType === 'update_user') {
            // TODO: similar to create_user but maybe different data
            echo "   ℹ️ update_user not implemented yet. Marking as failed.\n";
            throw new Exception('update_user not implemented in script');

        } elseif ($jobType === 'delete_user') {
            // TODO: implement delete user if needed, e.g. $zk->removeUser($uid);
            echo "   ℹ️ delete_user not implemented yet. Marking as failed.\n";
            throw new Exception('delete_user not implemented in script');

        } else {
            throw new Exception("Unknown job type: {$jobType}");
        }

    } catch (\Throwable $e) {
        $status = 'failed';
        $error  = $e->getMessage();
        echo "   ❌ Error processing job {$jobId}: {$error}\n";
    }

    // 4) Report result back to Laravel
    reportJobResult($completeUrlTemplate, $jobId, $status, $error);
}

/* -------------------------------------------------
 *  4) CLEANUP
 * ------------------------------------------------- */

$zk->disconnect();
echo "🔌 Disconnected from device.\n";
echo "🎉 sync_device_users.php finished.\n";

/* -------------------------------------------------
 *  HELPER: REPORT RESULT TO LARAVEL
 * ------------------------------------------------- */

/**
 * @param string      $urlTemplate e.g. "http://127.0.0.1:8000/api/zk/jobs/{id}/complete?token=..."
 * @param int|string  $jobId
 * @param string      $status      "success" or "failed"
 * @param string|null $error
 * @return void
 */
function reportJobResult(string $urlTemplate, $jobId, string $status, ?string $error): void
{
    $url = str_replace('{id}', $jobId, $urlTemplate);

    $payload = json_encode([
        'status'        => $status,
        'error_message' => $error,
    ]);

    echo "   📡 Reporting job {$jobId} as {$status} to Laravel: {$url}\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if (!empty($curlErr)) {
        echo "   ⚠️ cURL error reporting job {$jobId}: {$curlErr}\n";
        return;
    }

    echo "   🔁 Laravel response for job {$jobId}: HTTP {$httpCode} – {$response}\n";
}
