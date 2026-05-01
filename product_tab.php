<?php
/**
 * Product Marketplaces Tab
 * 
 * Display and manage product offers across marketplaces
 * Shows: Marketplace | SKU | Price | Stock | Description | Sync Status | Actions
 */

// Search for main.inc.php with various possible paths
$rootPath = null;
$pathsToTry = [
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php',
    dirname(__FILE__) . '/../../main.inc.php',
    dirname(__FILE__) . '/../../../main.inc.php',
];

foreach ($pathsToTry as $path) {
    if (file_exists($path)) {
        $rootPath = $path;
        break;
    }
}

if (!$rootPath) {
    die("Error: Cannot find main.inc.php. Paths tried:<br>" . implode("<br>", $pathsToTry));
}

require_once $rootPath;

// Security check
if (!isModEnabled('marketplace_bdc')) {
    die("Module not enabled");
}

global $db, $user, $langs, $conf;

$langs->load('marketplace_bdc@marketplace_bdc');

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    die("Product ID required");
}

// Load product
$product = new Product($db);
if ($product->fetch($product_id) <= 0) {
    die("Product not found");
}

// Check permission
if (!$user->hasRight('marketplace_bdc', 'marketplace', 'read')) {
    die("Access denied");
}

// Get marketplace offers
$sql = "SELECT mo.rowid, mo.fk_marketplace, mo.product_sku, mo.product_price, 
               mo.product_quantity, mo.quantity_modifier, mo.description_sync, 
               mo.last_sync_date, mo.sync_status,
               m.code as marketplace_code, m.label as marketplace_label
        FROM " . MAIN_DB_PREFIX . "modmkp_offer mo
        LEFT JOIN " . MAIN_DB_PREFIX . "modmkp_marketplace m ON mo.fk_marketplace = m.rowid
        WHERE mo.fk_product = " . $product_id . "
        ORDER BY m.label ASC";

$result = $db->query($sql);
$offers = [];
if ($result) {
    while ($row = $db->fetch_object($result)) {
        $offers[] = $row;
    }
}

// Get all available marketplaces for adding new offers
$sql_markets = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "modmkp_marketplace 
                WHERE active = 1 ORDER BY label ASC";
$result_markets = $db->query($sql_markets);
$all_marketplaces = [];
while ($row = $db->fetch_object($result_markets)) {
    $all_marketplaces[] = $row;
}

// Handle form submissions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$offer_id = isset($_GET['offer_id']) ? intval($_GET['offer_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save') {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        die("Access denied");
    }
    
    $offer_id = intval($_POST['offer_id'] ?? 0);
    $sku = sanitizeString($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity_modifier = sanitizeString($_POST['quantity_modifier'] ?? '');
    $description_sync = isset($_POST['description_sync']) ? 1 : 0;
    
    if ($offer_id > 0) {
        // Update existing offer
        $sql_update = "UPDATE " . MAIN_DB_PREFIX . "modmkp_offer 
                       SET product_sku = '" . $db->escape($sku) . "',
                           product_price = " . $price . ",
                           quantity_modifier = '" . $db->escape($quantity_modifier) . "',
                           description_sync = " . $description_sync . "
                       WHERE rowid = " . $offer_id;
        $db->query($sql_update);
    }
    
    $_GET['action'] = '';
    $_GET['offer_id'] = 0;
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add') {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        die("Access denied");
    }
    
    $fk_marketplace = intval($_POST['fk_marketplace'] ?? 0);
    $sku = sanitizeString($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity_modifier = sanitizeString($_POST['quantity_modifier'] ?? '');
    $description_sync = isset($_POST['description_sync']) ? 1 : 0;
    
    if ($fk_marketplace > 0) {
        // Get current product stock
        $current_stock = $product->stock_reel ?? 0;
        
        $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "modmkp_offer 
                       (fk_product, fk_marketplace, product_sku, product_price, 
                        product_quantity, quantity_modifier, description_sync, sync_status)
                       VALUES (" . $product_id . ", " . $fk_marketplace . ", 
                               '" . $db->escape($sku) . "', " . $price . ",
                               " . $current_stock . ", '" . $db->escape($quantity_modifier) . "',
                               " . $description_sync . ", 'pending')";
        $db->query($sql_insert);
    }
    
    $_GET['action'] = '';
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id);
    exit;
}

// Handle delete
if ($action == 'delete' && $offer_id > 0) {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        die("Access denied");
    }
    
    $db->query("DELETE FROM " . MAIN_DB_PREFIX . "modmkp_offer WHERE rowid = " . $offer_id);
    
    $_GET['action'] = '';
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id);
    exit;
}

// Handle sync
if ($action == 'sync' && $offer_id > 0) {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'sync')) {
        die("Access denied");
    }
    
    // Update sync status
    $db->query("UPDATE " . MAIN_DB_PREFIX . "modmkp_offer 
               SET sync_status = 'pending', last_sync_date = NOW()
               WHERE rowid = " . $offer_id);
    
    // TODO: Call actual API sync
    $_GET['action'] = '';
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $product_id);
    exit;
}

// Helper function
function sanitizeString($str) {
    global $db;
    return $db->escape(strip_tags($str));
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $product->label; ?> - Marketplaces</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .product-info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .product-info p { margin: 5px 0; }
        .label { font-weight: bold; color: #666; }
        
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        
        .status-ok { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
        
        .btn { padding: 8px 15px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-info:hover { background: #138496; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .form-group textarea { min-height: 100px; }
        
        .form-inline { display: flex; gap: 10px; align-items: center; }
        .form-inline input { flex: 1; }
        
        .alert { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .alert-info { background: #d1ecf1; color: #0c5460; }
        .alert-success { background: #d4edda; color: #155724; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; }
        .modal-header { font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        .modal-footer { margin-top: 20px; text-align: right; }
        
        .badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-size: 12px; }
        .badge-primary { background: #007bff; color: white; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>📦 <?php echo htmlspecialchars($product->label); ?> - Marketplaces</h1>
    
    <div class="product-info">
        <p><span class="label">SKU:</span> <?php echo htmlspecialchars($product->ref); ?></p>
        <p><span class="label">Stock Actuel:</span> <strong><?php echo $product->stock_reel; ?></strong> unités</p>
        <p><span class="label">Prix Dolibarr:</span> <strong><?php echo number_format($product->price, 2, ',', ' ') . ' €'; ?></strong></p>
    </div>

    <?php if (empty($offers)): ?>
        <div class="alert alert-info">
            ℹ️ Aucune offre marketplace configurée pour ce produit. Ajoutez une offre ci-dessous.
        </div>
    <?php else: ?>
        <h3>📊 Offres Configurées</h3>
        <table>
            <thead>
                <tr>
                    <th>Marketplace</th>
                    <th>SKU</th>
                    <th>Prix</th>
                    <th>Stock</th>
                    <th>Modification</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Dernier Sync</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offers as $offer): 
                    $stock_display = $product->stock_reel;
                    if ($offer->quantity_modifier) {
                        $stock_display .= ' ' . $offer->quantity_modifier;
                    }
                    
                    $status_class = $offer->sync_status == 'ok' ? 'status-ok' : 
                                   ($offer->sync_status == 'error' ? 'status-error' : 'status-pending');
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($offer->marketplace_label); ?></strong></td>
                    <td><?php echo htmlspecialchars($offer->product_sku); ?></td>
                    <td><?php echo number_format($offer->product_price, 2, ',', ' ') . ' €'; ?></td>
                    <td><?php echo $stock_display; ?></td>
                    <td><?php echo htmlspecialchars($offer->quantity_modifier ?: '-'); ?></td>
                    <td>
                        <?php 
                        $sync_label = $offer->description_sync ? '✓ Synchronisée' : '✗ Non synchronisée';
                        $sync_class = $offer->description_sync ? 'badge-success' : 'badge-warning';
                        ?>
                        <span class="badge <?php echo $sync_class; ?>"><?php echo $sync_label; ?></span>
                    </td>
                    <td class="<?php echo $status_class; ?>"><?php echo ucfirst($offer->sync_status); ?></td>
                    <td><?php echo $offer->last_sync_date ? date('d/m/Y H:i', strtotime($offer->last_sync_date)) : '-'; ?></td>
                    <td>
                        <button class="btn btn-info" onclick="editOffer(<?php echo $offer->rowid; ?>)">✏️ Éditer</button>
                        <button class="btn btn-success" onclick="syncOffer(<?php echo $offer->rowid; ?>)">🔄 Sync</button>
                        <button class="btn btn-danger" onclick="deleteOffer(<?php echo $offer->rowid; ?>)">🗑️ Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($all_marketplaces)): ?>
        <h3>➕ Ajouter une Offre</h3>
        <form method="POST" class="form-inline">
            <input type="hidden" name="action" value="add">
            <select name="fk_marketplace" required>
                <option value="">-- Sélectionner un marketplace --</option>
                <?php foreach ($all_marketplaces as $market): 
                    // Check if already exists
                    $exists = false;
                    foreach ($offers as $offer) {
                        if ($offer->fk_marketplace == $market->rowid) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists):
                ?>
                <option value="<?php echo $market->rowid; ?>"><?php echo htmlspecialchars($market->label); ?></option>
                <?php endif; endforeach; ?>
            </select>
            
            <input type="text" name="sku" placeholder="SKU marketplace" required>
            <input type="number" step="0.01" name="price" placeholder="Prix" required>
            <input type="text" name="quantity_modifier" placeholder="ex: -10, +5 ou vide">
            <label><input type="checkbox" name="description_sync"> Sync description</label>
            <button type="submit" class="btn btn-success">✓ Ajouter</button>
        </form>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Éditer l'Offre</div>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="offer_id" id="editOfferId">
            
            <div class="form-group">
                <label>SKU Marketplace</label>
                <input type="text" name="sku" id="editSku" required>
            </div>
            
            <div class="form-group">
                <label>Prix (€)</label>
                <input type="number" step="0.01" name="price" id="editPrice" required>
            </div>
            
            <div class="form-group">
                <label>Modification Quantité</label>
                <input type="text" name="quantity_modifier" id="editQuantityModifier" placeholder="ex: -10, +5">
                <small>Laissez vide pour utiliser le stock exact</small>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" name="description_sync" id="editDescriptionSync"> Synchroniser la description</label>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editOffer(offerId) {
    // Get offer data from table row
    const row = event.target.closest('tr');
    const cells = row.cells;
    
    document.getElementById('editOfferId').value = offerId;
    document.getElementById('editSku').value = cells[1].textContent;
    document.getElementById('editPrice').value = cells[2].textContent.replace(' €', '').replace(',', '.');
    
    const modText = cells[4].textContent;
    document.getElementById('editQuantityModifier').value = modText !== '-' ? modText : '';
    
    const syncText = cells[5].textContent;
    document.getElementById('editDescriptionSync').checked = syncText.includes('Synchronisée');
    
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function syncOffer(offerId) {
    if (confirm('Synchroniser cette offre vers le marketplace?')) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.innerHTML = '<input type="hidden" name="action" value="sync"><input type="hidden" name="offer_id" value="' + offerId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteOffer(offerId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette offre?')) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="offer_id" value="' + offerId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('editModal').onclick = function(event) {
    if (event.target === this) {
        closeEditModal();
    }
}
</script>

</body>
</html>
