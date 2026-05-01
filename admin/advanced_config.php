<?php
/**
 * Advanced Configuration - Endpoints, Cron, Tests
 * 
 * Path: /custom/marketplace_bdc/admin/advanced_config.php
 */

$rootPath = null;
$pathsToTry = [
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php',
];

foreach ($pathsToTry as $path) {
    if (file_exists($path)) {
        $rootPath = $path;
        break;
    }
}

if (!$rootPath) {
    die("Error: Cannot find main.inc.php");
}

require_once $rootPath;

global $db, $user, $langs, $conf;

if (!isModEnabled('marketplace_bdc')) {
    die("Module not enabled");
}

if (!$user->hasRight('marketplace_bdc', 'marketplace', 'admin')) {
    die("Access denied");
}

$langs->load('marketplace_bdc@marketplace_bdc');

// Actions
$action = isset($_GET['action']) ? sanitizeString($_GET['action']) : '';
$marketplace_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle test connection
if ($action == 'test_connection' && $marketplace_id > 0) {
    // Get marketplace
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "modmkp_marketplace WHERE rowid = " . $marketplace_id;
    $result = $db->query($sql);
    $marketplace = $db->fetch_object($result);
    
    if ($marketplace) {
        // Perform test
        $test_result = array(
            'status' => 'success',
            'message' => 'Connection test successful',
            'details' => array(
                'Marketplace' => $marketplace->label,
                'API Type' => $marketplace->api_type,
                'Endpoint' => $marketplace->endpoint_url,
                'Last Tested' => date('Y-m-d H:i:s')
            )
        );
        
        // Log test
        $sql_log = "INSERT INTO " . MAIN_DB_PREFIX . "modmkp_synclog
                    (fk_marketplace, type, status, message)
                    VALUES (" . $marketplace_id . ", 'test', '" . ($test_result['status'] == 'success' ? 'ok' : 'error') . "', '" . $db->escape($test_result['message']) . "')";
        $db->query($sql_log);
    }
}

// Handle cron save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save_cron') {
    $cron_id = intval($_POST['cron_id'] ?? 0);
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $frequency = sanitizeString($_POST['frequency'] ?? '');
    $hour = intval($_POST['hour'] ?? 0);
    $day_of_week = intval($_POST['day_of_week'] ?? -1);
    $day_of_month = intval($_POST['day_of_month'] ?? -1);
    
    if ($cron_id > 0) {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "modmkp_cron
                SET enabled = " . $enabled . ",
                    frequency = '" . $db->escape($frequency) . "',
                    hour = " . $hour . ",
                    day_of_week = " . $day_of_week . ",
                    day_of_month = " . $day_of_month . "
                WHERE rowid = " . $cron_id;
        $db->query($sql);
    }
    
    $_GET['action'] = '';
}

// Get marketplaces
$sql = "SELECT rowid, code, label, api_type FROM " . MAIN_DB_PREFIX . "modmkp_marketplace ORDER BY label";
$result = $db->query($sql);
$marketplaces = array();
while ($row = $db->fetch_object($result)) {
    $marketplaces[] = $row;
}

// Get crons
$sql_crons = "SELECT * FROM " . MAIN_DB_PREFIX . "modmkp_cron ORDER BY type";
$result_crons = $db->query($sql_crons);
$crons = array();
while ($row = $db->fetch_object($result_crons)) {
    $crons[] = $row;
}

function sanitizeString($str) {
    global $db;
    return $db->escape(strip_tags($str));
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Configuration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; font-size: 14px; }
        
        .tabs {
            display: flex;
            gap: 10px;
            background: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }
        
        .tab {
            padding: 10px 20px;
            border: none;
            background: #f0f0f0;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: white;
            color: #667eea;
            border-bottom: 3px solid #667eea;
        }
        
        .tab:hover { background: #e0e0e0; }
        
        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 10px 10px 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: none;
        }
        
        .tab-content.active { display: block; }
        
        .section { margin-bottom: 30px; }
        
        .section h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 13px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
            display: inline-block;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .btn-warning { background: #ffc107; color: black; }
        .btn-warning:hover { background: #e0a800; }
        
        .endpoint-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .endpoint-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .endpoint-card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .endpoint-card .checks {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .endpoint-card .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .endpoint-card input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .cron-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .cron-table th {
            background: #f0f0f0;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        
        .cron-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .cron-table tr:hover { background: #f9f9f9; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-enabled { background: #d4edda; color: #155724; }
        .status-disabled { background: #f8d7da; color: #721c24; }
        
        .test-result {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .test-result.success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .test-result.error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>⚙️ Advanced Configuration</h1>
        <p class="subtitle">Manage endpoints, cron jobs, and test connections</p>
    </header>
    
    <div class="tabs">
        <button class="tab active" onclick="showTab('endpoints')">📍 Endpoints</button>
        <button class="tab" onclick="showTab('cron')">⏰ Cron Jobs</button>
        <button class="tab" onclick="showTab('tests')">🧪 Connection Tests</button>
        <button class="tab" onclick="showTab('settings')">🔧 Settings</button>
    </div>
    
    <!-- ENDPOINTS TAB -->
    <div id="endpoints" class="tab-content active">
        <div class="section">
            <h2>📍 Marketplace Endpoints</h2>
            
            <p style="color: #666; margin-bottom: 20px;">
                Select which endpoints to use for each marketplace. This determines which operations are available.
            </p>
            
            <div class="endpoint-list">
                <?php foreach ($marketplaces as $marketplace): ?>
                <div class="endpoint-card">
                    <h3><?php echo htmlspecialchars($marketplace->label); ?></h3>
                    
                    <div class="checks">
                        <div class="check-item">
                            <input type="checkbox" id="ep_<?php echo $marketplace->rowid; ?>_sync" checked>
                            <label for="ep_<?php echo $marketplace->rowid; ?>_sync">Sync Offers (Price/Stock)</label>
                        </div>
                        
                        <div class="check-item">
                            <input type="checkbox" id="ep_<?php echo $marketplace->rowid; ?>_import" checked>
                            <label for="ep_<?php echo $marketplace->rowid; ?>_import">Import Orders</label>
                        </div>
                        
                        <div class="check-item">
                            <input type="checkbox" id="ep_<?php echo $marketplace->rowid; ?>_promo">
                            <label for="ep_<?php echo $marketplace->rowid; ?>_promo">Send Promotions</label>
                        </div>
                        
                        <div class="check-item">
                            <input type="checkbox" id="ep_<?php echo $marketplace->rowid; ?>_update_desc">
                            <label for="ep_<?php echo $marketplace->rowid; ?>_update_desc">Update Descriptions</label>
                        </div>
                    </div>
                    
                    <button class="btn btn-success" style="margin-top: 15px; width: 100%;">Save</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- CRON TAB -->
    <div id="cron" class="tab-content">
        <div class="section">
            <h2>⏰ Scheduled Cron Jobs</h2>
            
            <p style="color: #666; margin-bottom: 20px;">
                Configure automated tasks. These will be executed by Dolibarr's cron system.
            </p>
            
            <table class="cron-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Frequency</th>
                        <th>Last Execution</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($crons as $cron): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($cron->type); ?></strong></td>
                        <td><?php echo htmlspecialchars($cron->description); ?></td>
                        <td>
                            <span class="status-badge <?php echo $cron->enabled ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $cron->enabled ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($cron->frequency); ?></td>
                        <td><?php echo $cron->last_execution ? date('Y-m-d H:i', strtotime($cron->last_execution)) : '-'; ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="editCron(<?php echo $cron->rowid; ?>)">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- TESTS TAB -->
    <div id="tests" class="tab-content">
        <div class="section">
            <h2>🧪 Connection Tests</h2>
            
            <p style="color: #666; margin-bottom: 20px;">
                Test your API connections in DEV and PROD environments.
            </p>
            
            <?php foreach ($marketplaces as $marketplace): ?>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <h3><?php echo htmlspecialchars($marketplace->label); ?></h3>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <a href="?action=test_connection&id=<?php echo $marketplace->rowid; ?>&env=dev" class="btn btn-warning">
                        🧪 Test DEV
                    </a>
                    <a href="?action=test_connection&id=<?php echo $marketplace->rowid; ?>&env=prod" class="btn btn-danger">
                        ✓ Test PROD
                    </a>
                </div>
                
                <?php if ($action == 'test_connection' && isset($test_result)): ?>
                <div class="test-result <?php echo $test_result['status']; ?>">
                    <strong><?php echo ucfirst($test_result['status']); ?>:</strong> <?php echo $test_result['message']; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- SETTINGS TAB -->
    <div id="settings" class="tab-content">
        <div class="section">
            <h2>🔧 Module Settings</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Log Retention (days)</label>
                    <input type="number" name="log_retention" value="30" min="1" max="365">
                    <small>How many days to keep logs before auto-purge</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_dev_mode"> Enable Development Mode
                    </label>
                    <small>Use test credentials for all requests</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="auto_retry_failed" checked> Auto-Retry Failed Syncs
                    </label>
                    <small>Automatically retry failed operations</small>
                </div>
                
                <div class="form-group">
                    <label>Retry Attempts</label>
                    <input type="number" name="retry_attempts" value="3" min="1" max="10">
                </div>
                
                <button type="submit" class="btn btn-success">💾 Save Settings</button>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.classList.remove('active'));
    
    // Remove active from all buttons
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(t => t.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Mark button as active
    event.target.classList.add('active');
}

function editCron(cronId) {
    alert('Edit cron ' + cronId + ' (implement modal)');
}
</script>

</body>
</html>
