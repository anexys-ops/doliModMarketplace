<?php
/**
 * Product Tab - MarketPlace Offers
 * 
 * Integrated as a Dolibarr product tab
 * Called from: modMarketPlace_BDC.class.php
 * 
 * This file displays ONLY the tab content (no HTML/HEAD/BODY tags)
 */

// Global context is already loaded by Dolibarr
global $db, $user, $langs, $conf, $object;

// Load required classes if not already loaded
if (!class_exists('Product')) {
    require_once DOL_DOCUMENT_ROOT . '/products/class/product.class.php';
}

// Get product ID
$product_id = isset($object->id) ? $object->id : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if (!$product_id) {
    echo '<!-- DEBUG: No product ID -->';
    return;
}

// Load product if not already loaded
if (!isset($object) || !is_object($object) || $object->id != $product_id) {
    try {
        $product = new Product($db);
        if ($product->fetch($product_id) <= 0) {
            echo '<!-- DEBUG: Product not found -->';
            return;
        }
    } catch (Exception $e) {
        echo '<!-- DEBUG: Product fetch error: ' . htmlspecialchars($e->getMessage()) . ' -->';
        return;
    }
} else {
    $product = $object;
}

// Check permission
if (!$user->hasRight('marketplace_bdc', 'marketplace', 'read')) {
    echo '<!-- DEBUG: Permission denied -->';
    return;
}

// Handle form submissions
$action = isset($_GET['action']) ? sanitizeString($_GET['action']) : '';
$offer_id = isset($_GET['offer_id']) ? intval($_GET['offer_id']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save') {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        return;
    }
    
    $offer_id = intval($_POST['offer_id'] ?? 0);
    $sku = sanitizeString($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity_modifier = sanitizeString($_POST['quantity_modifier'] ?? '');
    $description_sync = isset($_POST['description_sync']) ? 1 : 0;
    
    if ($offer_id > 0) {
        $sql_update = "UPDATE " . MAIN_DB_PREFIX . "modmkp_offer 
                       SET product_sku = '" . $db->escape($sku) . "',
                           product_price = " . $price . ",
                           quantity_modifier = '" . $db->escape($quantity_modifier) . "',
                           description_sync = " . $description_sync . "
                       WHERE rowid = " . $offer_id;
        $db->query($sql_update);
        $action = '';
        $offer_id = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'add') {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        return;
    }
    
    $fk_marketplace = intval($_POST['fk_marketplace'] ?? 0);
    $sku = sanitizeString($_POST['sku'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity_modifier = sanitizeString($_POST['quantity_modifier'] ?? '');
    $description_sync = isset($_POST['description_sync']) ? 1 : 0;
    
    if ($fk_marketplace > 0) {
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
    $action = '';
}

// Handle delete
if ($action == 'delete' && $offer_id > 0) {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
        return;
    }
    
    $db->query("DELETE FROM " . MAIN_DB_PREFIX . "modmkp_offer WHERE rowid = " . $offer_id);
    $action = '';
}

// Handle sync
if ($action == 'sync' && $offer_id > 0) {
    if (!$user->hasRight('marketplace_bdc', 'marketplace', 'sync')) {
        return;
    }
    
    $db->query("UPDATE " . MAIN_DB_PREFIX . "modmkp_offer 
               SET sync_status = 'pending', last_sync_date = NOW()
               WHERE rowid = " . $offer_id);
    $action = '';
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

// Get all available marketplaces
$sql_markets = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "modmkp_marketplace 
                WHERE active = 1 ORDER BY label ASC";
$result_markets = $db->query($sql_markets);
$all_marketplaces = [];
while ($row = $db->fetch_object($result_markets)) {
    $all_marketplaces[] = $row;
}

function sanitizeString($str) {
    global $db;
    return $db->escape(strip_tags($str));
}

?>

<style>
.marketplace-tab {
    padding: 15px 0;
}

.marketplace-info {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    border-left: 3px solid #007bff;
}

.marketplace-info p {
    margin: 8px 0;
    font-size: 13px;
}

.marketplace-label {
    font-weight: bold;
    color: #333;
    min-width: 150px;
    display: inline-block;
}

.marketplace-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.marketplace-table th {
    background: #f0f0f0;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
    font-size: 13px;
    color: #333;
}

.marketplace-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

.marketplace-table tr:last-child td {
    border-bottom: none;
}

.marketplace-table tr:hover {
    background: #f5f5f5;
}

.status-ok {
    color: #28a745;
    font-weight: bold;
}

.status-error {
    color: #dc3545;
    font-weight: bold;
}

.status-pending {
    color: #ffc107;
    font-weight: bold;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.marketplace-actions {
    display: flex;
    gap: 5px;
}

.btn-marketplace {
    padding: 5px 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-edit {
    background: #007bff;
    color: white;
}

.btn-edit:hover {
    background: #0056b3;
}

.btn-sync {
    background: #28a745;
    color: white;
}

.btn-sync:hover {
    background: #218838;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.form-add-offer {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
    flex-wrap: wrap;
}

.form-add-offer select,
.form-add-offer input {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
}

.form-add-offer select {
    min-width: 150px;
}

.form-add-offer input[type="text"],
.form-add-offer input[type="number"] {
    min-width: 120px;
}

.form-add-offer label {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0;
}

.form-add-offer button {
    padding: 8px 15px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-weight: bold;
}

.form-add-offer button:hover {
    background: #218838;
}

.alert-empty {
    padding: 15px;
    background: #e7f3ff;
    color: #004085;
    border: 1px solid #b3d9ff;
    border-radius: 3px;
    margin: 15px 0;
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: white;
    border-radius: 5px;
    padding: 25px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 15px;
}

.modal-body {
    margin-bottom: 20px;
}

.modal-form-group {
    margin-bottom: 12px;
}

.modal-form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 12px;
}

.modal-form-group input,
.modal-form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    font-family: inherit;
}

.modal-form-group textarea {
    resize: vertical;
    min-height: 60px;
}

.modal-form-group input:focus,
.modal-form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 3px rgba(0,123,255,0.3);
}

.modal-form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-form-check input[type="checkbox"] {
    width: auto;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.modal-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-weight: bold;
    font-size: 12px;
}

.modal-btn-cancel {
    background: #6c757d;
    color: white;
}

.modal-btn-cancel:hover {
    background: #5a6268;
}

.modal-btn-save {
    background: #007bff;
    color: white;
}

.modal-btn-save:hover {
    background: #0056b3;
}
</style>

<div class="marketplace-tab">
    
    <div class="marketplace-info">
        <p><span class="marketplace-label">Product SKU:</span> <strong><?php echo htmlspecialchars($product->ref); ?></strong></p>
        <p><span class="marketplace-label">Stock Available:</span> <strong><?php echo $product->stock_reel; ?></strong> units</p>
        <p><span class="marketplace-label">Base Price:</span> <strong><?php echo number_format($product->price, 2, '.', ' '); ?> €</strong></p>
    </div>

    <?php if (empty($offers)): ?>
        <div class="alert-empty">
            <strong>ℹ️ No marketplace offers configured yet.</strong> Add your first offer below.
        </div>
    <?php else: ?>
        <table class="marketplace-table">
            <thead>
                <tr>
                    <th>Marketplace</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Adjustment</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Last Sync</th>
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
                    <td><?php echo number_format($offer->product_price, 2, '.', ' '); ?> €</td>
                    <td><?php echo $stock_display; ?></td>
                    <td><?php echo htmlspecialchars($offer->quantity_modifier ?: '-'); ?></td>
                    <td>
                        <?php 
                        $sync_label = $offer->description_sync ? '✓ Yes' : '✗ No';
                        $sync_class = $offer->description_sync ? 'badge-success' : 'badge-warning';
                        ?>
                        <span class="badge <?php echo $sync_class; ?>"><?php echo $sync_label; ?></span>
                    </td>
                    <td class="<?php echo $status_class; ?>"><?php echo ucfirst($offer->sync_status); ?></td>
                    <td><?php echo $offer->last_sync_date ? date('d/m/Y H:i', strtotime($offer->last_sync_date)) : '-'; ?></td>
                    <td>
                        <div class="marketplace-actions">
                            <button type="button" class="btn-marketplace btn-edit" onclick="editOffer(<?php echo $offer->rowid; ?>, '<?php echo htmlspecialchars($offer->product_sku); ?>', <?php echo $offer->product_price; ?>, '<?php echo htmlspecialchars($offer->quantity_modifier); ?>', <?php echo $offer->description_sync; ?>)">Edit</button>
                            <button type="button" class="btn-marketplace btn-sync" onclick="syncOffer(<?php echo $offer->rowid; ?>)">Sync</button>
                            <button type="button" class="btn-marketplace btn-delete" onclick="deleteOffer(<?php echo $offer->rowid; ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($all_marketplaces)): ?>
        <form method="POST" class="form-add-offer" onsubmit="return validateAddForm()">
            <input type="hidden" name="action" value="add">
            <select name="fk_marketplace" required>
                <option value="">+ Add Marketplace</option>
                <?php foreach ($all_marketplaces as $market): 
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
            
            <input type="text" name="sku" placeholder="SKU" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <input type="text" name="quantity_modifier" placeholder="-10 or +5">
            
            <label><input type="checkbox" name="description_sync"> Sync Description</label>
            <button type="submit">+ Add</button>
        </form>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">Edit Marketplace Offer</div>
        
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="offer_id" id="editOfferId">
            
            <div class="modal-body">
                <div class="modal-form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" id="editSku" required>
                </div>
                
                <div class="modal-form-group">
                    <label>Price (€)</label>
                    <input type="number" step="0.01" name="price" id="editPrice" required>
                </div>
                
                <div class="modal-form-group">
                    <label>Quantity Adjustment</label>
                    <input type="text" name="quantity_modifier" id="editQuantityModifier" placeholder="-10 or +5">
                    <small>Leave empty for exact stock</small>
                </div>
                
                <div class="modal-form-group">
                    <div class="modal-form-check">
                        <input type="checkbox" name="description_sync" id="editDescriptionSync">
                        <label for="editDescriptionSync">Synchronize description</label>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="modal-btn modal-btn-save">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editOffer(offerId, sku, price, modifier, syncDesc) {
    document.getElementById('editOfferId').value = offerId;
    document.getElementById('editSku').value = sku;
    document.getElementById('editPrice').value = price;
    document.getElementById('editQuantityModifier').value = modifier;
    document.getElementById('editDescriptionSync').checked = syncDesc ? true : false;
    
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function syncOffer(offerId) {
    if (confirm('Sync this offer to marketplace?')) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.innerHTML = '<input type="hidden" name="action" value="sync"><input type="hidden" name="offer_id" value="' + offerId + '">';
        form.style.display = 'none';
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteOffer(offerId) {
    if (confirm('Delete this offer?')) {
        const form = document.createElement('form');
        form.method = 'GET';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="offer_id" value="' + offerId + '">';
        form.style.display = 'none';
        document.body.appendChild(form);
        form.submit();
    }
}

function validateAddForm() {
    const marketplace = document.querySelector('select[name="fk_marketplace"]').value;
    if (!marketplace) {
        alert('Please select a marketplace');
        return false;
    }
    return true;
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>
