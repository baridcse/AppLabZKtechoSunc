<?php
/**
 * Web UI for sync_to_cloud.php
 * Provides a user-friendly interface to trigger and monitor attendance sync operations
 */

// Basic Authentication - DISABLED for local network access
// Uncomment the lines below to re-enable authentication
/*
$authUser = 'admin';
$authPass = 'zkteco2024'; // Change this to a secure password

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
    $_SERVER['PHP_AUTH_USER'] !== $authUser || $_SERVER['PHP_AUTH_PW'] !== $authPass) {
    header('WWW-Authenticate: Basic realm="ZKTeco Sync Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Access Denied</h1><p>Authentication required to access this system.</p>';
    exit;
}
*/

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);

require __DIR__ . '/constants.php';
require __DIR__ . '/vendor/autoload.php';

use Rats\Zkteco\Lib\ZKTeco;

// Handle AJAX requests for sync operation
if (isset($_POST['action']) && $_POST['action'] === 'sync') {
    header('Content-Type: application/json');

    // Execute the sync script and capture output
    $output = [];
    $returnCode = 0;

    // Run sync_to_cloud.php and capture its output
    exec('php ' . __DIR__ . '/sync_to_cloud.php 2>&1', $output, $returnCode);

    echo json_encode([
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Handle AJAX requests for device connection test
if (isset($_POST['action']) && $_POST['action'] === 'test_connection') {
    header('Content-Type: application/json');

    $zk = new ZKTeco(DEFAULT_DEVICE_IP, DEFAULT_DEVICE_PORT);
    $connected = $zk->connect();

    if ($connected) {
        $zk->disconnect();
        echo json_encode(['success' => true, 'message' => 'Device connection successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not connect to device']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZKTeco Attendance Sync - Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sync-card { transition: all 0.3s ease; }
        .sync-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .status-online { background-color: #28a745; }
        .status-offline { background-color: #dc3545; }
        .status-syncing { background-color: #ffc107; animation: pulse 1s infinite; }
        .log-container { max-height: 400px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 0.9em; }
        .log-entry { padding: 5px 0; border-bottom: 1px solid #f8f9fa; }
        .log-error { color: #dc3545; }
        .log-success { color: #28a745; }
        .log-info { color: #17a2b8; }
        .log-warning { color: #ffc107; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        .config-table { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .config-table th { border-color: rgba(255,255,255,0.2); }
        .config-table td { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-0"><i class="fas fa-sync-alt text-primary me-2"></i>ZKTeco Attendance Sync</h1>
                        <p class="text-muted mb-0">Control Panel for Device Synchronization</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button id="testConnectionBtn" class="btn btn-outline-primary">
                            <i class="fas fa-plug"></i> Test Connection
                        </button>
                        <button id="syncBtn" class="btn btn-success">
                            <i class="fas fa-play"></i> Start Sync
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Section -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card sync-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>System Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered config-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-server me-1"></i>Setting</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Value</th>
                                        <th><i class="fas fa-tag me-1"></i>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Device IP</td>
                                        <td><code><?php echo DEFAULT_DEVICE_IP; ?></code></td>
                                        <td>ZKTeco F22 device IP address</td>
                                    </tr>
                                    <tr>
                                        <td>Device Port</td>
                                        <td><code><?php echo DEFAULT_DEVICE_PORT; ?></code></td>
                                        <td>ZKTeco communication port</td>
                                    </tr>
                                    <tr>
                                        <td>Cloud URL</td>
                                        <td><code><?php echo CLOUD_BASE_URL; ?></code></td>
                                        <td>Laravel API base URL</td>
                                    </tr>
                                    <tr>
                                        <td>API Endpoint</td>
                                        <td><code><?php echo str_replace(CLOUD_BASE_URL, '', CLOUD_URL); ?></code></td>
                                        <td>Complete API endpoint with authentication</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Panel -->
            <div class="col-lg-4">
                <div class="card sync-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="status-indicator status-offline" id="deviceStatus"></span>
                                <strong>Device Connection</strong>
                            </div>
                            <small class="text-muted" id="deviceStatusText">Checking...</small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="status-indicator status-offline" id="cloudStatus"></span>
                                <strong>Cloud API</strong>
                            </div>
                            <small class="text-muted" id="cloudStatusText">Not tested</small>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Last Sync</strong>
                            </div>
                            <small class="text-muted" id="lastSyncTime">Never</small>
                        </div>
                        <hr>
                        <div class="text-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="refreshStatus()">
                                <i class="fas fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Progress and Logs -->
        <div class="row">
            <div class="col-12">
                <div class="card sync-card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-terminal me-2"></i>Sync Progress & Logs</h5>
                        <div>
                            <button class="btn btn-sm btn-light" onclick="clearLogs()">
                                <i class="fas fa-trash"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="progressContainer" class="mb-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: 0%" id="syncProgress"></div>
                            </div>
                            <small class="text-muted mt-1" id="progressText">Initializing...</small>
                        </div>

                        <div class="log-container bg-dark text-light p-3 rounded" id="logContainer">
                            <div class="log-entry">
                                <span class="text-info">[<?php echo date('H:i:s'); ?>]</span>
                                <span class="log-info">System ready. Click "Start Sync" to begin synchronization.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let isSyncing = false;
        let syncStartTime = null;

        // Initialize status on page load
        document.addEventListener('DOMContentLoaded', function() {
            refreshStatus();
        });

        // Test device connection
        document.getElementById('testConnectionBtn').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
            btn.disabled = true;

            fetch('sync_ui.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_connection'
            })
            .then(response => response.json())
            .then(data => {
                addLogEntry(data.success ? 'success' : 'error',
                    data.success ? '✅ Device connection test successful' : '❌ ' + data.message);

                updateDeviceStatus(data.success);
            })
            .catch(error => {
                addLogEntry('error', '❌ Connection test failed: ' + error.message);
                updateDeviceStatus(false);
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

        // Start sync process
        document.getElementById('syncBtn').addEventListener('click', function() {
            if (isSyncing) return;

            const btn = this;
            const originalText = btn.innerHTML;

            isSyncing = true;
            syncStartTime = Date.now();
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            btn.disabled = true;
            document.getElementById('syncBtn').classList.remove('btn-success');
            document.getElementById('syncBtn').classList.add('btn-warning');

            // Show progress bar
            document.getElementById('progressContainer').style.display = 'block';
            document.getElementById('syncProgress').style.width = '10%';
            document.getElementById('progressText').textContent = 'Initializing sync process...';

            addLogEntry('info', '🚀 Starting attendance synchronization...');

            fetch('sync_ui.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=sync'
            })
            .then(response => response.json())
            .then(data => {
                // Process the output line by line
                const lines = data.output.split('\n');
                let delay = 0;

                lines.forEach((line, index) => {
                    if (line.trim()) {
                        setTimeout(() => {
                            const progress = Math.min(10 + (index / lines.length) * 90, 100);
                            document.getElementById('syncProgress').style.width = progress + '%';

                            let logType = 'info';
                            if (line.includes('❌') || line.includes('error') || line.includes('Error')) {
                                logType = 'error';
                            } else if (line.includes('✅') || line.includes('success')) {
                                logType = 'success';
                            } else if (line.includes('⚠️') || line.includes('warning')) {
                                logType = 'warning';
                            }

                            addLogEntry(logType, line);

                            if (index === lines.length - 1) {
                                document.getElementById('syncProgress').style.width = '100%';
                                document.getElementById('progressText').textContent =
                                    data.success ? 'Sync completed successfully!' : 'Sync completed with errors';
                                document.getElementById('lastSyncTime').textContent = new Date().toLocaleString();
                            }
                        }, delay);
                        delay += 200; // Stagger log entries for better UX
                    }
                });
            })
            .catch(error => {
                addLogEntry('error', '❌ Sync failed: ' + error.message);
                document.getElementById('progressText').textContent = 'Sync failed';
            })
            .finally(() => {
                isSyncing = false;
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    document.getElementById('syncBtn').classList.remove('btn-warning');
                    document.getElementById('syncBtn').classList.add('btn-success');
                    document.getElementById('progressContainer').style.display = 'none';
                }, 2000);
            });
        });

        function addLogEntry(type, message) {
            const logContainer = document.getElementById('logContainer');
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';

            const timestamp = document.createElement('span');
            timestamp.className = 'text-info';
            timestamp.textContent = '[' + new Date().toLocaleTimeString() + ']';

            const content = document.createElement('span');
            content.className = 'log-' + type;
            content.textContent = ' ' + message;

            logEntry.appendChild(timestamp);
            logEntry.appendChild(content);

            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateDeviceStatus(connected) {
            const indicator = document.getElementById('deviceStatus');
            const text = document.getElementById('deviceStatusText');

            indicator.className = 'status-indicator ' + (connected ? 'status-online' : 'status-offline');
            text.textContent = connected ? 'Connected' : 'Disconnected';
        }

        function refreshStatus() {
            // Test device connection automatically
            fetch('sync_ui.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_connection'
            })
            .then(response => response.json())
            .then(data => updateDeviceStatus(data.success))
            .catch(() => updateDeviceStatus(false));

            // Update cloud status (simplified - just show configured)
            document.getElementById('cloudStatus').className = 'status-indicator status-online';
            document.getElementById('cloudStatusText').textContent = 'Configured';
        }

        function clearLogs() {
            const logContainer = document.getElementById('logContainer');
            logContainer.innerHTML = '<div class="log-entry"><span class="text-info">[' +
                new Date().toLocaleTimeString() + ']</span><span class="log-info"> Logs cleared</span></div>';
        }
    </script>
</body>
</html>
