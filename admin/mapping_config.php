<?php
/**
 * Mapping Configuration Page
 * 
 * Configure field mappings for Products, Categories, Orders
 * with Extrafields support
 * 
 * Path: /custom/marketplace_bdc/admin/mapping_config.php
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

// Get marketplace ID
$marketplace_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$entity_type = isset($_GET['entity']) ? sanitizeString($_GET['entity']) : 'product';

// Get marketplace
$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "modmkp_marketplace WHERE rowid = " . $marketplace_id;
$result = $db->query($sql);
$marketplace = $db->fetch_object($result);

if (!$marketplace) {
    die("Marketplace not found");
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_mappings'])) {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        die("Access denied");
    }
    
    // Save field mappings
    foreach ($_POST['mappings'] as $dolibarr_field => $marketplace_field) {
        if (!empty($marketplace_field)) {
            $is_extrafield = isset($_POST['is_extrafield'][$dolibarr_field]) ? 1 : 0;
            $is_required = isset($_POST['is_required'][$dolibarr_field]) ? 1 : 0;
            
            // Get or create mapping
            $sql_m = "SELECT rowid FROM " . MAIN_DB_PREFIX . "modmkp_mapping
                      WHERE fk_marketplace = " . $marketplace_id . "
                      AND entity_type = '" . $db->escape($entity_type) . "'";
            $r = $db->query($sql_m);
            
            if ($db->num_rows($r) > 0) {
                $mapping_row = $db->fetch_object($r);
                $mapping_id = $mapping_row->rowid;
            } else {
                $sql_ins = "INSERT INTO " . MAIN_DB_PREFIX . "modmkp_mapping
                            (fk_marketplace, entity_type)
                            VALUES (" . $marketplace_id . ", '" . $db->escape($entity_type) . "')";
                $db->query($sql_ins);
                $mapping_id = $db->last_insert_id();
            }
            
            // Save field mapping
            $sql_f = "INSERT INTO " . MAIN_DB_PREFIX . "modmkp_mapping_fields
                      (fk_mapping, dolibarr_field, marketplace_field, is_extrafield, is_required)
                      VALUES (" . $mapping_id . ",
                              '" . $db->escape($dolibarr_field) . "',
                              '" . $db->escape($marketplace_field) . "',
                              " . $is_extrafield . ",
                              " . $is_required . ")
                      ON DUPLICATE KEY UPDATE
                      marketplace_field = '" . $db->escape($marketplace_field) . "',
                      is_required = " . $is_required;
            $db->query($sql_f);
        }
    }
    
    $success_message = "Mappings saved successfully";
}

// Get current mappings
$sql_m = "SELECT rowid FROM " . MAIN_DB_PREFIX . "modmkp_mapping
          WHERE fk_marketplace = " . $marketplace_id . "
          AND entity_type = '" . $db->escape($entity_type) . "'";
$r = $db->query($sql_m);
$mapping_id = 0;
$current_mappings = array();

if ($db->num_rows($r) > 0) {
    $mapping_row = $db->fetch_object($r);
    $mapping_id = $mapping_row->rowid;
    
    // Get field mappings
    $sql_f = "SELECT * FROM " . MAIN_DB_PREFIX . "modmkp_mapping_fields
              WHERE fk_mapping = " . $mapping_id;
    $rf = $db->query($sql_f);
    
    while ($field_row = $db->fetch_object($rf)) {
        $current_mappings[$field_row->dolibarr_field] = $field_row;
    }
}

// Get standard fields
require_once DOL_DOCUMENT_ROOT . '/custom/marketplace_bdc/class/MappingManager.class.php';
$mapping_manager = new MappingManager($db);
$standard_fields = $mapping_manager->getStandardFields($entity_type);
$extrafields = $mapping_manager->getExtrafields($entity_type);

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
    <title>Mapping Configuration - <?php echo htmlspecialchars($marketplace->label); ?></title>
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
        
        h1 { color: #333; margin-bottom: 10px; }
        .breadcrumb { color: #999; font-size: 14px; }
        
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
        
        .mapping-section { margin-bottom: 30px; }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            padding-bottom: 15px;
            border-bottom: 2px solid #667eea;
            margin-bottom: 20px;
        }
        
        .mapping-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .mapping-table th {
            background: #f0f0f0;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            font-size: 13px;
        }
        
        .mapping-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        
        .mapping-table tr:hover { background: #f9f9f9; }
        
        .field-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .field-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        .checkbox {
            width: auto;
            margin: 0;
        }
        
        .label {
            display: block;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .label-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .label-description {
            color: #999;
            font-size: 12px;
        }
        
        .extrafield-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .required-badge {
            display: inline-block;
            background: #ffebee;
            color: #c62828;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
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
        
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
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
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            color: #004085;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>🔗 Field Mapping Configuration</h1>
        <p class="breadcrumb">
            <?php echo htmlspecialchars($marketplace->label); ?> (<?php echo htmlspecialchars($marketplace->api_type); ?>) 
            / Mapping
        </p>
    </header>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">✓ <?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <div class="info-box">
        📌 Map Dolibarr fields (including extrafields) to marketplace fields. Check "Required" for mandatory fields.
    </div>
    
    <div class="tabs">
        <button class="tab <?php echo $entity_type == 'product' ? 'active' : ''; ?>" onclick="switchTab('product', <?php echo $marketplace_id; ?>)">📦 Products</button>
        <button class="tab <?php echo $entity_type == 'category' ? 'active' : ''; ?>" onclick="switchTab('category', <?php echo $marketplace_id; ?>)">📁 Categories</button>
        <button class="tab <?php echo $entity_type == 'order' ? 'active' : ''; ?>" onclick="switchTab('order', <?php echo $marketplace_id; ?>)">📋 Orders</button>
    </div>
    
    <div class="tab-content active">
        <form method="POST">
            <input type="hidden" name="save_mappings" value="1">
            
            <!-- Standard Fields Section -->
            <div class="mapping-section">
                <div class="section-title">Standard Fields</div>
                
                <table class="mapping-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Dolibarr Field</th>
                            <th style="width: 30%;">Marketplace Field</th>
                            <th style="width: 15%; text-align: center;">Required</th>
                            <th style="width: 25%; text-align: center;">Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standard_fields as $field_key => $field_label): 
                            $current = isset($current_mappings[$field_key]) ? $current_mappings[$field_key] : null;
                            $mapped_value = $current ? $current->marketplace_field : '';
                            $is_required = $current ? $current->is_required : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="label">
                                    <div class="label-name"><?php echo htmlspecialchars($field_label); ?></div>
                                    <div class="label-description">dolibarr.<?php echo htmlspecialchars($field_key); ?></div>
                                </div>
                            </td>
                            <td>
                                <input type="text" 
                                       name="mappings[<?php echo htmlspecialchars($field_key); ?>]"
                                       class="field-input"
                                       placeholder="e.g., marketplace_sku"
                                       value="<?php echo htmlspecialchars($mapped_value); ?>">
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" 
                                       name="is_required[<?php echo htmlspecialchars($field_key); ?>]"
                                       class="checkbox"
                                       <?php echo $is_required ? 'checked' : ''; ?>>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-secondary" onclick="showHelp('<?php echo htmlspecialchars($field_key); ?>')">
                                    ?
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Extrafields Section -->
            <?php if (!empty($extrafields)): ?>
            <div class="mapping-section">
                <div class="section-title">Extrafields (Custom Fields)</div>
                
                <table class="mapping-table">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Dolibarr Field</th>
                            <th style="width: 30%;">Marketplace Field</th>
                            <th style="width: 15%; text-align: center;">Required</th>
                            <th style="width: 25%; text-align: center;">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extrafields as $extra): 
                            $current = isset($current_mappings[$extra->attrname]) ? $current_mappings[$extra->attrname] : null;
                            $mapped_value = $current ? $current->marketplace_field : '';
                            $is_required = $current ? $current->is_required : 0;
                        ?>
                        <tr>
                            <td>
                                <div class="label">
                                    <div class="label-name">
                                        <?php echo htmlspecialchars($extra->label); ?>
                                        <span class="extrafield-badge">EXTRA</span>
                                    </div>
                                    <div class="label-description">dolibarr.<?php echo htmlspecialchars($extra->attrname); ?></div>
                                </div>
                            </td>
                            <td>
                                <input type="text" 
                                       name="mappings[<?php echo htmlspecialchars($extra->attrname); ?>]"
                                       class="field-input"
                                       placeholder="e.g., marketplace_extra_field"
                                       value="<?php echo htmlspecialchars($mapped_value); ?>">
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" 
                                       name="is_required[<?php echo htmlspecialchars($extra->attrname); ?>]"
                                       class="checkbox"
                                       <?php echo $is_required ? 'checked' : ''; ?>>
                            </td>
                            <td style="text-align: center;">
                                <span style="background: #f0f0f0; padding: 5px 10px; border-radius: 3px; font-size: 12px;">
                                    <?php echo htmlspecialchars($extra->type); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">💾 Save Mappings</button>
                <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(entityType, marketplaceId) {
    window.location.href = '?id=' + marketplaceId + '&entity=' + entityType;
}

function showHelp(field) {
    alert('Field: ' + field + '\n\nThis is a standard Dolibarr field. Map it to the corresponding marketplace field name.');
}
</script>

</body>
</html>
