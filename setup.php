<?php
/**
 * MarketPlace Module - Admin Setup Configuration
 * 
 * Display marketplaces with tiles/cards
 * Allow configuration of credentials and settings
 */

// Search for main.inc.php
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

$langs->load('marketplace_bdc@marketplace_bdc');

// Security
if (!isModEnabled('marketplace_bdc')) {
    die("Module not enabled");
}

if (!$user->hasRight('marketplace_bdc', 'marketplace', 'admin')) {
    die("Access denied - Admin rights required");
}

$action = isset($_GET['action']) ? sanitizeString($_GET['action']) : '';
$marketplace_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save' && $marketplace_id > 0) {
    $label = sanitizeString($_POST['label'] ?? '');
    $code = sanitizeString($_POST['code'] ?? '');
    $api_type = sanitizeString($_POST['api_type'] ?? '');
    $api_key = sanitizeString($_POST['api_key'] ?? '');
    $api_secret = sanitizeString($_POST['api_secret'] ?? '');
    $endpoint_url = sanitizeString($_POST['endpoint_url'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    $sql = "UPDATE " . MAIN_DB_PREFIX . "modmkp_marketplace 
            SET label = '" . $db->escape($label) . "',
                code = '" . $db->escape($code) . "',
                api_type = '" . $db->escape($api_type) . "',
                api_key = '" . $db->escape($api_key) . "',
                api_secret = '" . $db->escape($api_secret) . "',
                endpoint_url = '" . $db->escape($endpoint_url) . "',
                active = " . $active . "
            WHERE rowid = " . $marketplace_id;
    
    $db->query($sql);
    $success_message = "Marketplace updated successfully";
    $action = '';
    $marketplace_id = 0;
}

// Get all marketplaces
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "modmkp_marketplace ORDER BY label";
$result = $db->query($sql);
$marketplaces = [];
while ($row = $db->fetch_object($result)) {
    $marketplaces[] = $row;
}

// Get marketplace to edit
$current_marketplace = null;
if ($marketplace_id > 0 && $action == 'edit') {
    foreach ($marketplaces as $mp) {
        if ($mp->rowid == $marketplace_id) {
            $current_marketplace = $mp;
            break;
        }
    }
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
    <title>MarketPlace Configuration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h1::before { content: "⚙️"; font-size: 30px; }
        
        .subtitle { color: #666; font-size: 14px; }
        
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #17a2b8;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        /* Tiles Grid */
        .tiles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .tile {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .tile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tile-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tile-code {
            font-size: 12px;
            color: #999;
            background: #f5f5f5;
            padding: 4px 8px;
            border-radius: 3px;
            font-family: monospace;
        }
        
        .tile-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .tile-body {
            margin-bottom: 15px;
        }
        
        .tile-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: #555;
        }
        
        .tile-label { color: #999; }
        .tile-value { font-weight: bold; color: #333; }
        
        .tile-actions {
            display: flex;
            gap: 8px;
            border-top: 2px solid #f0f0f0;
            padding-top: 15px;
        }
        
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .btn-block { width: 100%; margin-top: 10px; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        
        .modal-footer {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-check input[type="checkbox"] {
            width: auto;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .tiles-grid {
                grid-template-columns: 1fr;
            }
            
            header {
                padding: 20px;
            }
            
            .modal-content {
                padding: 20px;
                width: 95%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>MarketPlace Configuration</h1>
        <p class="subtitle">Manage your marketplace integrations and settings</p>
    </header>
    
    <!-- Version Badge -->
    <?php
    $badge_file = __DIR__ . '/version_badge.html';
    if (file_exists($badge_file)) {
        include $badge_file;
    }
    ?>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            ✓ <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($marketplaces)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <p>No marketplaces configured yet</p>
            <p style="font-size: 12px; margin-top: 10px;">Marketplaces will appear here once they are created in the database.</p>
        </div>
    <?php else: ?>
        <div class="tiles-grid">
            <?php foreach ($marketplaces as $marketplace): ?>
                <div class="tile">
                    <div class="tile-header">
                        <div class="tile-title">
                            📱
                            <?php echo htmlspecialchars($marketplace->label); ?>
                        </div>
                        <span class="tile-status <?php echo $marketplace->active ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $marketplace->active ? '🟢 Active' : '🔴 Inactive'; ?>
                        </span>
                    </div>
                    
                    <div class="tile-body">
                        <div class="tile-row">
                            <span class="tile-label">Code:</span>
                            <span class="tile-code"><?php echo htmlspecialchars($marketplace->code); ?></span>
                        </div>
                        <div class="tile-row">
                            <span class="tile-label">API Type:</span>
                            <span class="tile-value"><?php echo htmlspecialchars($marketplace->api_type); ?></span>
                        </div>
                        <div class="tile-row">
                            <span class="tile-label">API Key:</span>
                            <span class="tile-value">
                                <?php 
                                $key = $marketplace->api_key;
                                if (strlen($key) > 20) {
                                    echo htmlspecialchars(substr($key, 0, 10)) . '****' . htmlspecialchars(substr($key, -10));
                                } else {
                                    echo htmlspecialchars($key) ? '✓ Set' : '✗ Not set';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="tile-actions">
                        <a href="?action=edit&id=<?php echo $marketplace->rowid; ?>" class="btn btn-primary">✏️ Edit</a>
                        <button class="btn btn-info" onclick="testConnection(<?php echo $marketplace->rowid; ?>)">🔗 Test</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<?php if ($current_marketplace): ?>
<div id="editModal" class="modal active">
    <div class="modal-content">
        <div class="modal-header">Edit Marketplace: <?php echo htmlspecialchars($current_marketplace->label); ?></div>
        
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $current_marketplace->rowid; ?>">
            
            <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" value="<?php echo htmlspecialchars($current_marketplace->label); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Code</label>
                <input type="text" name="code" value="<?php echo htmlspecialchars($current_marketplace->code); ?>" required readonly>
            </div>
            
            <div class="form-group">
                <label>API Type</label>
                <select name="api_type" required>
                    <option value="mirakl" <?php echo $current_marketplace->api_type == 'mirakl' ? 'selected' : ''; ?>>Mirakl (ADEO)</option>
                    <option value="octopia" <?php echo $current_marketplace->api_type == 'octopia' ? 'selected' : ''; ?>>Octopia (Cdiscount)</option>
                    <option value="amazon" <?php echo $current_marketplace->api_type == 'amazon' ? 'selected' : ''; ?>>Amazon SP-API</option>
                    <option value="woocommerce" <?php echo $current_marketplace->api_type == 'woocommerce' ? 'selected' : ''; ?>>WooCommerce</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>API Key</label>
                <input type="password" name="api_key" value="<?php echo htmlspecialchars($current_marketplace->api_key); ?>" required>
                <small style="color: #999;">Password field - leave empty to keep current</small>
            </div>
            
            <div class="form-group">
                <label>API Secret</label>
                <input type="password" name="api_secret" value="<?php echo htmlspecialchars($current_marketplace->api_secret); ?>">
                <small style="color: #999;">Optional, leave empty to keep current</small>
            </div>
            
            <div class="form-group">
                <label>Endpoint URL</label>
                <input type="url" name="endpoint_url" value="<?php echo htmlspecialchars($current_marketplace->endpoint_url); ?>" placeholder="https://api.marketplace.com">
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="active" id="active" <?php echo $current_marketplace->active ? 'checked' : ''; ?>>
                    <label for="active">Active</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <a href="?" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function testConnection(marketplaceId) {
    alert('Test connection feature coming soon!\nMarketplace ID: ' + marketplaceId);
}
</script>

</body>
</html>
