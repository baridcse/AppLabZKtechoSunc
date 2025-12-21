<?php
// get_users.php – list users from ZKTeco F22

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

// 🔧 CONFIG – change if needed
$deviceIp   = '192.168.1.253';
$devicePort = 4370;

echo "Connecting to {$deviceIp}:{$devicePort}...\n";

$zk = new ZKTeco($deviceIp, $devicePort);

if (!$zk->connect()) {
    die("❌ Could not connect to device.\n");
}

echo "✅ Connected.\n";
echo "Fetching users...\n";

/**
 * Depending on library version, it may be:
 *   $users = $zk->getUser();
 * or:
 *   $users = $zk->getUsers();
 *
 * Try getUser() first:
 */
$users = $zk->getUser();

if (empty($users)) {
    echo "ℹ️ No users returned (or function returned empty array).\n";
} else {
    echo "📥 Got " . count($users) . " user(s):\n\n";
    $i = 1;
    foreach ($users as $user) {
        // Typical keys (may vary): uid, userid, name, role, cardno, password
        echo "User #{$i}:\n";
        print_r($user);
        echo "------------------------\n";
        $i++;
    }
}

$zk->disconnect();
echo "🔌 Disconnected.\n";
