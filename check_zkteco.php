<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 🔍 Try these possible autoload paths:
$paths = [
    __DIR__ . '/vendor/autoload.php',     // if vendor is inside /test
    __DIR__ . '/../vendor/autoload.php',  // if vendor is in /htdocs
];

$used = null;

foreach ($paths as $p) {
    echo "Checking: $p<br>";
    if (file_exists($p)) {
        $used = $p;
        break;
    }
}

if (!$used) {
    echo "❌ vendor/autoload.php not found in any known path.<br>";
    echo "Run this in /Applications/XAMPP/xamppfiles/htdocs/test:<br>";
    echo "<pre>composer require rats/zkteco</pre>";
    exit;
}

echo "✅ Using autoload: $used<br>";
require $used;

// Now check if the class exists
$fullClass = 'rats\\Zkteco\\Lib\\ZKTeco';

if (class_exists($fullClass)) {
    echo "✅ Class $fullClass is available.<br>";
} else {
    echo "❌ Class $fullClass is NOT found even after autoload.<br>";
    echo "This means rats/zkteco may not be installed correctly or has a different namespace.<br>";
}
