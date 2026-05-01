<?php
/**
 * Tools - Marketplace Logs & Monitoring
 * 
 * Admin interface for viewing and managing logs
 * Path: /custom/marketplace_bdc/admin/tools.php
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

// Get filters
$filter_marketplace = isset($_GET['marketplace_id']) ? intval($_GET['marketplace_id']) : 0;
$filter_status = isset($_GET['status']) ? sanitizeString($_GET['status']) : '';
$filter_type = isset($_GET['type']) ? sanitizeString($_GET['type']) : '';
$filter_date_from = isset($_GET['date_from']) ? sanitizeString($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? sanitizeString($_GET['date_to']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 0;

// Get actions
$action = isset($_GET['action']) ? sanitizeString($_GET['action']) : '';

// Handle purge action
if ($action == 'purge' && isset($_GET['days'])) {
    $days = intval($_GET['days']);
    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "modmkp_synclog 
            WHERE date_created < DATE_SUB(NOW(), INTERVAL " . $days . " DAY)";
    $db->query($sql);
    $success = "Logs purged";
}

// Handle export
if ($action == 'export') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="marketplace_logs_' . date('Y-m-d') . '.csv"');
    
    $filters = array(
        'marketplace_id' => $filter_marketplace,
        'status' => $filter_status,
        'type' => $filter_type,
        'date_from' => $filter_date_from,
        'date_to' => $filter_date_to
    );
    
    $sql = "SELECT rowid, fk_marketplace, type, status, message, date_created
            FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE 1=1";
    
    if ($filter_marketplace) {
        $sql .= " AND fk_marketplace = " . $filter_marketplace;
    }
    if ($filter_status) {
        $sql .= " AND status = '" . $db->escape($filter_status) . "'";
    }
    if ($filter_type) {
        $sql .= " AND type = '" . $db->escape($filter_type) . "'";
    }
    if ($filter_date_from) {
        $sql .= " AND date_created >= '" . $db->escape($filter_date_from) . "'";
    }
    if ($filter_date_to) {
        $sql .= " AND date_created <= '" . $db->escape($filter_date_to) . "'";
    }
    
    $sql .= " ORDER BY date_created DESC LIMIT 10000";
    $result = $db->query($sql);
    
    echo "Date,Marketplace,Type,Status,Message\n";
    while ($row = $db->fetch_object($result)) {
        $date = date('Y-m-d H:i:s', strtotime($row->date_created));
        echo "\"" . $date . "\",";
        echo "\"" . $row->fk_marketplace . "\",";
        echo "\"" . $row->type . "\",";
        echo "\"" . $row->status . "\",";
        echo "\"" . addslashes($row->message) . "\"\n";
    }
    exit;
}

// Get marketplaces
$sql_markets = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "modmkp_marketplace ORDER BY label";
$result_markets = $db->query($sql_markets);
$marketplaces = [];
while ($row = $db->fetch_object($result_markets)) {
    $marketplaces[] = $row;
}

// Get logs with filters
$sql = "SELECT rowid, fk_marketplace, type, status, message, date_created
        FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE 1=1";

if ($filter_marketplace) {
    $sql .= " AND fk_marketplace = " . $filter_marketplace;
}
if ($filter_status) {
    $sql .= " AND status = '" . $db->escape($filter_status) . "'";
}
if ($filter_type) {
    $sql .= " AND type = '" . $db->escape($filter_type) . "'";
}
if ($filter_date_from) {
    $sql .= " AND date_created >= '" . $db->escape($filter_date_from) . "'";
}
if ($filter_date_to) {
    $sql .= " AND date_created <= '" . $db->escape($filter_date_to) . "'";
}

$sql_count = str_replace('rowid, fk_marketplace, type, status, message, date_created', 'COUNT(*) as cnt', $sql);
$result_count = $db->query($sql_count);
$row_count = $db->fetch_object($result_count);
$total_logs = $row_count->cnt;

$sql .= " ORDER BY date_created DESC LIMIT 50 OFFSET " . ($page * 50);
$result = $db->query($sql);
$logs = [];
while ($row = $db->fetch_object($result)) {
    $logs[] = $row;
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
    <title>Marketplace Logs & Monitoring</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        
        .filters {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            min-width: 150px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: black;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th {
            background: #f0f0f0;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: bold;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: bold;
        }
        
        .status-pending {
            color: #17a2b8;
            font-weight: bold;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .stat-card h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 20px 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
            color: #007bff;
        }
        
        .pagination a:hover {
            background: #f0f0f0;
        }
        
        .pagination .active {
            background: #007bff;
            color: white;
        }
        
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📊 Marketplace Logs & Monitoring</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">✓ <?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="filters">
        <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; width: 100%;">
            <div class="filter-group">
                <label>Marketplace</label>
                <select name="marketplace_id">
                    <option value="">All</option>
                    <?php foreach ($marketplaces as $m): ?>
                    <option value="<?php echo $m->rowid; ?>" <?php echo $filter_marketplace == $m->rowid ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($m->label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">All</option>
                    <option value="test" <?php echo $filter_type == 'test' ? 'selected' : ''; ?>>Test</option>
                    <option value="sync" <?php echo $filter_type == 'sync' ? 'selected' : ''; ?>>Sync</option>
                    <option value="import" <?php echo $filter_type == 'import' ? 'selected' : ''; ?>>Import</option>
                    <option value="export" <?php echo $filter_type == 'export' ? 'selected' : ''; ?>>Export</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="ok" <?php echo $filter_status == 'ok' ? 'selected' : ''; ?>>OK</option>
                    <option value="error" <?php echo $filter_status == 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="warning" <?php echo $filter_status == 'warning' ? 'selected' : ''; ?>>Warning</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?" class="btn btn-primary">Reset</a>
            <a href="?action=export&marketplace_id=<?php echo $filter_marketplace; ?>&status=<?php echo $filter_status; ?>&type=<?php echo $filter_type; ?>&date_from=<?php echo $filter_date_from; ?>&date_to=<?php echo $filter_date_to; ?>" class="btn btn-success">📥 Export CSV</a>
        </form>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>Total Logs</h3>
            <div class="number"><?php echo $total_logs; ?></div>
        </div>
        <div class="stat-card" style="border-left-color: #28a745;">
            <h3>Success (OK)</h3>
            <div class="number" style="color: #28a745;">
                <?php 
                $sql_ok = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='ok'";
                $r = $db->query($sql_ok);
                $ro = $db->fetch_object($r);
                echo $ro->cnt;
                ?>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #dc3545;">
            <h3>Errors</h3>
            <div class="number" style="color: #dc3545;">
                <?php 
                $sql_err = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='error'";
                $r = $db->query($sql_err);
                $ro = $db->fetch_object($r);
                echo $ro->cnt;
                ?>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: #ffc107;">
            <h3>Warnings</h3>
            <div class="number" style="color: #ffc107;">
                <?php 
                $sql_warn = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='warning'";
                $r = $db->query($sql_warn);
                $ro = $db->fetch_object($r);
                echo $ro->cnt;
                ?>
            </div>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px; margin: 20px 0;">
        <a href="?action=purge&days=7" onclick="return confirm('Delete logs older than 7 days?');" class="btn btn-warning">🗑️ Purge Old (7 days)</a>
        <a href="?action=purge&days=30" onclick="return confirm('Delete logs older than 30 days?');" class="btn btn-warning">🗑️ Purge Old (30 days)</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Marketplace</th>
                <th>Type</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): 
                $status_class = 'status-' . $log->status;
            ?>
            <tr>
                <td><?php echo date('Y-m-d H:i:s', strtotime($log->date_created)); ?></td>
                <td>
                    <?php 
                    $name = '';
                    foreach ($marketplaces as $m) {
                        if ($m->rowid == $log->fk_marketplace) {
                            $name = $m->label;
                            break;
                        }
                    }
                    echo htmlspecialchars($name);
                    ?>
                </td>
                <td><?php echo htmlspecialchars($log->type); ?></td>
                <td class="<?php echo $status_class; ?>"><?php echo ucfirst($log->status); ?></td>
                <td><?php echo htmlspecialchars($log->message); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (empty($logs)): ?>
        <p style="text-align: center; color: #999;">No logs found</p>
    <?php endif; ?>
    
    <?php if ($total_logs > 50): ?>
        <div class="pagination">
            <?php for ($i = 0; $i < ceil($total_logs / 50); $i++): ?>
                <a href="?page=<?php echo $i; ?>&marketplace_id=<?php echo $filter_marketplace; ?>&status=<?php echo $filter_status; ?>" 
                   class="<?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i + 1; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
