<?php
/**
 * DIAGNOSTIC SCRIPT - Product Tab Issue
 * 
 * Place this file in: /var/www/dolibarr/htdocs/custom/marketplace_bdc/diagnose_product_tab.php
 * Access: https://dlbp150r58.edicloud.app/custom/marketplace_bdc/diagnose_product_tab.php
 */

// Load Dolibarr
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
    die("Cannot find main.inc.php");
}

require_once $rootPath;

?>
<!DOCTYPE html>
<html>
<head>
    <title>MarketPlace Module - Diagnostic</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #ffff00; }
        .section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-left: 3px solid #00ff00; }
        .ok { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffff00; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #00ff00; }
        th { background: #003300; }
        code { background: #1a1a1a; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 MarketPlace Module Diagnostic</h1>
    <p>Diagnostic de la page product_tab.php</p>

    <?php
    global $db, $user, $conf, $langs;
    
    echo '<div class="section">';
    echo '<h2>1. Module Status</h2>';
    
    if (!empty($conf->global->MAIN_MODULE_MARKETPLACE_BDC)) {
        echo '<p class="ok">✓ Module marketplace_bdc est activé</p>';
    } else {
        echo '<p class="error">✗ Module marketplace_bdc n\'est PAS activé</p>';
    }
    
    echo '</div>';
    
    // Database tables
    echo '<div class="section">';
    echo '<h2>2. Database Tables</h2>';
    
    $tables_to_check = [
        MAIN_DB_PREFIX . 'modmkp_marketplace',
        MAIN_DB_PREFIX . 'modmkp_offer',
        MAIN_DB_PREFIX . 'product'
    ];
    
    foreach ($tables_to_check as $table) {
        $sql = "SELECT COUNT(*) as cnt FROM " . $table;
        try {
            $result = $db->query($sql);
            if ($result) {
                $row = $db->fetch_object($result);
                echo '<p class="ok">✓ Table <code>' . $table . '</code> existe (' . $row->cnt . ' rows)</p>';
            } else {
                echo '<p class="error">✗ Table <code>' . $table . '</code> - Erreur: ' . $db->lasterror() . '</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">✗ Table <code>' . $table . '</code> n\'existe pas</p>';
        }
    }
    
    echo '</div>';
    
    // User permissions
    echo '<div class="section">';
    echo '<h2>3. User Permissions</h2>';
    
    echo '<p>User: <strong>' . $user->login . '</strong></p>';
    echo '<table>';
    echo '<tr><th>Permission</th><th>Status</th></tr>';
    
    $perms = [
        'read' => $user->hasRight('marketplace_bdc', 'marketplace', 'read'),
        'write' => $user->hasRight('marketplace_bdc', 'marketplace', 'write'),
        'sync' => $user->hasRight('marketplace_bdc', 'marketplace', 'sync'),
        'admin' => $user->hasRight('marketplace_bdc', 'marketplace', 'admin'),
    ];
    
    foreach ($perms as $perm => $has) {
        $status = $has ? '<span class="ok">✓ Yes</span>' : '<span class="warning">✗ No</span>';
        echo '<tr><td>' . $perm . '</td><td>' . $status . '</td></tr>';
    }
    
    echo '</table>';
    echo '</div>';
    
    // Files check
    echo '<div class="section">';
    echo '<h2>4. File Status</h2>';
    
    $files_to_check = [
        '/class/marketplace.class.php',
        '/class/marketplaceoffer.class.php',
        '/marketplace/product_tab.php',
        '/admin/setup.php',
        '/marketplace/dashboard.php',
    ];
    
    $base_path = '/var/www/dolibarr/htdocs/custom/marketplace_bdc';
    
    foreach ($files_to_check as $file) {
        $full_path = $base_path . $file;
        if (file_exists($full_path)) {
            $size = filesize($full_path);
            echo '<p class="ok">✓ <code>' . $file . '</code> (' . $size . ' bytes)</p>';
        } else {
            echo '<p class="error">✗ <code>' . $file . '</code> NOT FOUND</p>';
        }
    }
    
    echo '</div>';
    
    // Test SQL query for product tab
    echo '<div class="section">';
    echo '<h2>5. Test Data</h2>';
    
    $product_id = 155; // From your URL
    
    $sql = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "modmkp_offer 
            WHERE fk_product = " . $product_id;
    
    try {
        $result = $db->query($sql);
        if ($result) {
            $row = $db->fetch_object($result);
            echo '<p class="ok">✓ Product ' . $product_id . ' has ' . $row->cnt . ' offers</p>';
        }
    } catch (Exception $e) {
        echo '<p class="warning">⚠ Could not query offers: ' . $e->getMessage() . '</p>';
    }
    
    // Check if product exists
    $sql_product = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE rowid = " . $product_id;
    try {
        $result = $db->query($sql_product);
        if ($result) {
            $row = $db->fetch_object($result);
            if ($row) {
                echo '<p class="ok">✓ Product ' . $product_id . ': ' . $row->label . ' (' . $row->ref . ')</p>';
            } else {
                echo '<p class="error">✗ Product ' . $product_id . ' NOT FOUND</p>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Marketplace config
    echo '<div class="section">';
    echo '<h2>6. Marketplaces Configured</h2>';
    
    $sql_markets = "SELECT rowid, code, label, active FROM " . MAIN_DB_PREFIX . "modmkp_marketplace ORDER BY label";
    
    try {
        $result = $db->query($sql_markets);
        if ($result) {
            $count = 0;
            echo '<table>';
            echo '<tr><th>ID</th><th>Code</th><th>Label</th><th>Active</th></tr>';
            
            while ($row = $db->fetch_object($result)) {
                $active = $row->active ? '<span class="ok">Yes</span>' : '<span class="warning">No</span>';
                echo '<tr><td>' . $row->rowid . '</td><td>' . $row->code . '</td><td>' . $row->label . '</td><td>' . $active . '</td></tr>';
                $count++;
            }
            
            echo '</table>';
            
            if ($count == 0) {
                echo '<p class="warning">⚠ No marketplaces configured</p>';
            } else {
                echo '<p class="ok">✓ ' . $count . ' marketplace(s) configured</p>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // PHP Info
    echo '<div class="section">';
    echo '<h2>7. PHP Info</h2>';
    echo '<p>PHP Version: <code>' . phpversion() . '</code></p>';
    echo '<p>Dolibarr Version: <code>' . DOL_VERSION . '</code></p>';
    echo '<p>Database: <code>' . $conf->db->type . '</code></p>';
    echo '</div>';
    
    ?>
</div>

</body>
</html>
