<?php
/**
 * Onglet Marketplaces — Fiche produit
 * Page complète Dolibarr (avec header/footer)
 */

// ── Chargement Dolibarr ───────────────────────────────────────────────────────
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->loadLangs(array('products', 'marketplace_bdc@marketplace_bdc'));

// ── Paramètres ────────────────────────────────────────────────────────────────
$id     = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

if (empty($id)) { accessforbidden(); exit; }
if (!isModEnabled('marketplace_bdc')) { accessforbidden(); exit; }

// ── Chargement produit ────────────────────────────────────────────────────────
$object = new Product($db);
if ($object->fetch($id) <= 0) {
    dol_print_error($db);
    exit;
}
$object->load_stock();

// ── Sécurité ──────────────────────────────────────────────────────────────────
restrictedArea($user, 'produit', $object->id, 'product&product');

// ── Configs Marketplaces ──────────────────────────────────────────────────────
$all_mkt_raw = isset($conf->global->MARKETPLACE_BDC_MARKETPLACES) ? $conf->global->MARKETPLACE_BDC_MARKETPLACES : '{}';
$all_mkt     = json_decode($all_mkt_raw, true);
if (!is_array($all_mkt)) { $all_mkt = array(); }

// Marketplaces par défaut si pas encore configurées
$defaults = array('cdiscount' => 'Cdiscount', 'mirakl_adeo' => 'Mirakl ADEO', 'amazon' => 'Amazon SP-API');
foreach ($defaults as $kid => $kname) {
    if (!isset($all_mkt[$kid])) {
        $all_mkt[$kid] = array('name' => $kname, 'enabled' => 0);
    }
}

$prod_key    = 'MARKETPLACE_BDC_PRODUCT_'.$id;
$prod_raw    = isset($conf->global->$prod_key) ? $conf->global->$prod_key : '{}';
$prod_config = json_decode($prod_raw, true);
if (!is_array($prod_config)) { $prod_config = array(); }

// ── ACTION : Sauvegarde ───────────────────────────────────────────────────────
if ($action == 'storeprodmkt' && $user->hasRight('marketplace_bdc', 'marketplace', 'write')) {
    $new_cfg = array();
    foreach ($all_mkt as $mkt_id => $mkt_info) {
        $adj_type = GETPOST('adj_type_'.$mkt_id, 'alpha') ?: 'none';
        $adj_val  = str_replace(',', '.', GETPOST('adj_val_'.$mkt_id, 'alpha'));
        $adj_val  = is_numeric($adj_val) ? (float) $adj_val : 0;

        $new_cfg[$mkt_id] = array(
            'synced'     => GETPOST('synced_'.$mkt_id) ? 1 : 0,
            'sync_desc'  => GETPOST('sync_desc_'.$mkt_id) ? 1 : 0,
            'sync_price' => GETPOST('sync_price_'.$mkt_id) ? 1 : 0,
            'sync_stock' => GETPOST('sync_stock_'.$mkt_id) ? 1 : 0,
            'adj_type'   => $adj_type,
            'adj_val'    => $adj_val,
            'stock_buf'  => (int) GETPOST('stock_buf_'.$mkt_id, 'int'),
        );
        if (isset($prod_config[$mkt_id]['last_sync']))   { $new_cfg[$mkt_id]['last_sync']   = $prod_config[$mkt_id]['last_sync']; }
        if (isset($prod_config[$mkt_id]['last_status'])) { $new_cfg[$mkt_id]['last_status'] = $prod_config[$mkt_id]['last_status']; }
        if (isset($prod_config[$mkt_id]['last_sku']))    { $new_cfg[$mkt_id]['last_sku']    = $prod_config[$mkt_id]['last_sku']; }
    }
    dolibarr_set_const($db, $prod_key, json_encode($new_cfg), 'chaine', 0, '', $conf->entity);
    $prod_config = $new_cfg;
    setEventMessages('Configuration marketplace sauvegardée.', null, 'mesgs');
    header('Location: '.dol_buildpath('/custom/marketplace_bdc/marketplace/product_tab.php', 1).'?id='.$id);
    exit;
}

// ── AFFICHAGE ─────────────────────────────────────────────────────────────────
$page_name = 'Marketplaces';
llxHeader('', $page_name, '', '', 0, 0, '', '', '', 'mod-marketplace_bdc');

// Onglets produit (mêmes que product/card.php)
$head = product_prepare_head($object);
print dol_get_fiche_head($head, 'marketplaces', $langs->trans('Product'), -1, $object->picto);

// Bandeau produit identique à card.php
$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans('BackToList').'</a>';
$object->next_prev_filter = 'te.fk_product_type = '.$object->type;

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// ── Helpers ───────────────────────────────────────────────────────────────────
function mkt_cfg($prod_config, $mkt_id, $key, $default = 0) {
    return isset($prod_config[$mkt_id][$key]) ? $prod_config[$mkt_id][$key] : $default;
}

?>
<style>
.mkt-panel { border:1px solid #ddd; border-radius:6px; margin-bottom:14px; overflow:hidden; }
.mkt-header { display:flex; align-items:center; justify-content:space-between;
              padding:10px 16px; background:#f5f7fa; border-bottom:1px solid #ddd; }
.mkt-header h4 { margin:0; font-size:14px; }
.mkt-body { padding:14px 16px; }
.mkt-row { display:flex; align-items:center; gap:16px; margin-bottom:10px; flex-wrap:wrap; }
.mkt-label { min-width:130px; font-weight:600; font-size:12px; color:#555; }
.mkt-sync-info { font-size:11px; color:#888; font-style:italic; }
.badge-ok  { background:#28a745; color:#fff; border-radius:10px; padding:2px 8px; font-size:11px; }
.badge-err { background:#dc3545; color:#fff; border-radius:10px; padding:2px 8px; font-size:11px; }
.badge-na  { background:#aaa;    color:#fff; border-radius:10px; padding:2px 8px; font-size:11px; }
.mkt-disabled { opacity:.45; pointer-events:none; }
</style>

<form method="POST" action="<?php echo dol_buildpath('/custom/marketplace_bdc/marketplace/product_tab.php', 1).'?id='.$id; ?>">
<input type="hidden" name="token"  value="<?php echo newToken(); ?>">
<input type="hidden" name="action" value="storeprodmkt">
<input type="hidden" name="id"     value="<?php echo $id; ?>">

<?php if (empty($all_mkt)): ?>
    <div class="info" style="padding:14px">
        Aucune marketplace configurée.
        <a href="<?php echo dol_buildpath('/custom/marketplace_bdc/admin/setup.php', 1); ?>">Configurer le module</a>
    </div>
<?php else: ?>
    <?php foreach ($all_mkt as $mkt_id => $mkt_info): ?>
    <?php
        $mkt_name    = $mkt_info['name'] ?? $mkt_id;
        $mkt_enabled = !empty($mkt_info['enabled']);
        $synced      = (int) mkt_cfg($prod_config, $mkt_id, 'synced');
        $sync_desc   = (int) mkt_cfg($prod_config, $mkt_id, 'sync_desc',  1);
        $sync_price  = (int) mkt_cfg($prod_config, $mkt_id, 'sync_price', 1);
        $sync_stock  = (int) mkt_cfg($prod_config, $mkt_id, 'sync_stock', 1);
        $adj_type    =       mkt_cfg($prod_config, $mkt_id, 'adj_type',  'none');
        $adj_val     = (float) mkt_cfg($prod_config, $mkt_id, 'adj_val', 0);
        $stock_buf   = (int) mkt_cfg($prod_config, $mkt_id, 'stock_buf', 0);
        $last_sync   =       mkt_cfg($prod_config, $mkt_id, 'last_sync',  '');
        $last_status =       mkt_cfg($prod_config, $mkt_id, 'last_status', '');
        $last_sku    =       mkt_cfg($prod_config, $mkt_id, 'last_sku',  '');

        $icons = array('cdiscount' => '🛒', 'mirakl_adeo' => '🔨', 'amazon' => '📦');
        $icon  = $icons[$mkt_id] ?? '🌐';

        if (!$mkt_enabled) {
            $mod_badge = '<span class="badge-na">Module désactivé</span>';
        } elseif ($synced) {
            $mod_badge = '<span class="badge-ok">✓ Sync activée</span>';
        } else {
            $mod_badge = '<span class="badge-na">Sync désactivée</span>';
        }

        if ($last_sync) {
            $diff = time() - strtotime($last_sync);
            if ($diff < 60)        $age = 'il y a '.$diff.'s';
            elseif ($diff < 3600)  $age = 'il y a '.round($diff/60).'min';
            elseif ($diff < 86400) $age = 'il y a '.round($diff/3600).'h';
            else                   $age = date('d/m/Y H:i', strtotime($last_sync));
            $sync_badge = $last_status === 'ok'
                ? '<span class="badge-ok">✓ '.$age.'</span>'
                : '<span class="badge-err">✗ '.$age.' — '.$last_status.'</span>';
            if ($last_sku) $sync_badge .= ' <span class="mkt-sync-info">SKU: '.$last_sku.'</span>';
        } else {
            $sync_badge = '<span class="badge-na">Jamais synchronisé</span>';
        }
    ?>
    <div class="mkt-panel">
        <div class="mkt-header">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:20px"><?php echo $icon; ?></span>
                <h4><?php echo htmlspecialchars($mkt_name); ?></h4>
                <?php echo $mod_badge; ?>&nbsp;<?php echo $sync_badge; ?>
            </div>
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px;">
                <input type="checkbox" name="synced_<?php echo $mkt_id; ?>" value="1"
                       <?php echo $synced ? 'checked' : ''; ?>
                       onchange="toggleMktPanel('<?php echo $mkt_id; ?>', this.checked)"
                       <?php echo $mkt_enabled ? '' : 'disabled'; ?>>
                Synchroniser ce produit
            </label>
        </div>

        <div class="mkt-body<?php echo ($synced && $mkt_enabled) ? '' : ' mkt-disabled'; ?>" id="body_<?php echo $mkt_id; ?>">

            <div class="mkt-row">
                <span class="mkt-label">Infos à envoyer</span>
                <label><input type="checkbox" name="sync_desc_<?php echo $mkt_id; ?>" value="1" <?php echo $sync_desc ? 'checked' : ''; ?>>
                    📝 Description</label>
                <label><input type="checkbox" name="sync_price_<?php echo $mkt_id; ?>" value="1" <?php echo $sync_price ? 'checked' : ''; ?>
                              onchange="togglePriceAdj('<?php echo $mkt_id; ?>', this.checked)">
                    💶 Prix</label>
                <label><input type="checkbox" name="sync_stock_<?php echo $mkt_id; ?>" value="1" <?php echo $sync_stock ? 'checked' : ''; ?>
                              onchange="toggleStockBuf('<?php echo $mkt_id; ?>', this.checked)">
                    🏭 Stock</label>
            </div>

            <div class="mkt-row" id="price_adj_<?php echo $mkt_id; ?>"<?php echo $sync_price ? '' : ' style="display:none"'; ?>>
                <span class="mkt-label">Ajustement prix</span>
                <select name="adj_type_<?php echo $mkt_id; ?>" onchange="toggleAdjVal('<?php echo $mkt_id; ?>', this.value)" style="width:180px">
                    <option value="none"    <?php echo $adj_type=='none'    ?'selected':''; ?>>Aucun (prix catalogue)</option>
                    <option value="add_pct" <?php echo $adj_type=='add_pct' ?'selected':''; ?>>+ % majoration</option>
                    <option value="sub_pct" <?php echo $adj_type=='sub_pct' ?'selected':''; ?>>- % remise</option>
                    <option value="add_eur" <?php echo $adj_type=='add_eur' ?'selected':''; ?>>+ € montant fixe</option>
                    <option value="sub_eur" <?php echo $adj_type=='sub_eur' ?'selected':''; ?>>- € montant fixe</option>
                </select>
                <div id="adj_val_<?php echo $mkt_id; ?>"<?php echo $adj_type=='none' ?' style="display:none"':''; ?>>
                    <input type="number" name="adj_val_<?php echo $mkt_id; ?>" value="<?php echo $adj_val; ?>"
                           step="0.01" min="0" style="width:90px">
                    <span class="mkt-sync-info"><?php echo in_array($adj_type, array('add_pct','sub_pct')) ? '%' : '€'; ?></span>
                    <span class="mkt-sync-info" style="margin-left:8px">
                        Prix TTC catalogue : <strong><?php echo price($object->price_ttc); ?></strong>
                    </span>
                </div>
            </div>

            <div class="mkt-row" id="stock_buf_<?php echo $mkt_id; ?>"<?php echo $sync_stock ? '' : ' style="display:none"'; ?>>
                <span class="mkt-label">Réserve stock</span>
                <input type="number" name="stock_buf_<?php echo $mkt_id; ?>" value="<?php echo $stock_buf; ?>" min="0" style="width:80px">
                <span class="mkt-sync-info">
                    unités retenues — Stock réel : <strong><?php echo (int) $object->stock_reel; ?></strong>
                    → Envoyé : <strong><?php echo max(0, (int)$object->stock_reel - $stock_buf); ?></strong>
                </span>
            </div>

        </div><!-- /mkt-body -->
    </div><!-- /mkt-panel -->
    <?php endforeach; ?>

    <?php if ($user->hasRight('marketplace_bdc', 'marketplace', 'write')): ?>
    <div class="tabsAction">
        <button type="submit" class="button button-primary">💾 Sauvegarder la configuration</button>
    </div>
    <?php endif; ?>

<?php endif; ?>
</form>

<?php
// ── Section synchronisation forcée ────────────────────────────────────────────
if ($user->hasRight('marketplace_bdc', 'marketplace', 'sync') && !empty($all_mkt)):
    $mkt_enabled_all = array();
    foreach ($all_mkt as $kid => $kinfo) {
        if (!empty($kinfo['enabled'])) {
            $mkt_enabled_all[$kid] = $kinfo['name'] ?? $kid;
        }
    }
    $sync_candidates = array();
    foreach ($mkt_enabled_all as $kid => $kname) {
        if (!empty($prod_config[$kid]['synced'])) {
            $sync_candidates[$kid] = $kname;
        }
    }
?>
<hr style="margin:24px 0">
<div style="background:#f0f7ff; border:1px solid #b8d4f0; border-radius:8px; padding:18px 20px;">
    <h4 style="margin:0 0 14px; font-size:14px; color:#0056b3;">🚀 Synchronisation forcée</h4>

    <?php if (empty($mkt_enabled_all)): ?>
        <div class="info">Aucune marketplace activée dans la
            <a href="<?php echo dol_buildpath('/custom/marketplace_bdc/admin/setup.php', 1); ?>">configuration du module</a>.
        </div>
    <?php else: ?>
    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <label style="font-size:13px; font-weight:600;">Marketplace :</label>
        <select id="mkt_sync_target" style="padding:6px 10px; border-radius:4px; border:1px solid #ccc; font-size:13px; min-width:220px;">
            <?php if (!empty($sync_candidates)): ?>
                <option value="all">— Toutes (sync activée sur ce produit) —</option>
            <?php endif; ?>
            <?php foreach ($mkt_enabled_all as $kid => $kname):
                $has = !empty($sync_candidates[$kid]); ?>
                <option value="<?php echo htmlspecialchars($kid); ?>"<?php echo $has ? '' : ' style="color:#999"'; ?>>
                    <?php echo htmlspecialchars($kname).($has ? '' : ' ⚠ sync non activée'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="button button-action" id="btn_force_sync"
                onclick="forceSyncProduct(<?php echo $id; ?>)">
            <span id="sync_spinner" style="display:none">⏳</span>
            ⚡ Forcer la synchronisation
        </button>
    </div>
    <div id="sync_result_box" style="display:none; margin-top:14px;"></div>
    <p style="margin:10px 0 0; font-size:11px; color:#777;">
        ℹ️ Sauvegardez d'abord la configuration avant de synchroniser.
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /fichecenter -->

<?php print dol_get_fiche_end(); ?>

<script>
function toggleMktPanel(mkt_id, enabled) {
    var body = document.getElementById('body_' + mkt_id);
    if (body) body.className = enabled ? 'mkt-body' : 'mkt-body mkt-disabled';
}
function togglePriceAdj(mkt_id, show) {
    var el = document.getElementById('price_adj_' + mkt_id);
    if (el) el.style.display = show ? '' : 'none';
}
function toggleStockBuf(mkt_id, show) {
    var el = document.getElementById('stock_buf_' + mkt_id);
    if (el) el.style.display = show ? '' : 'none';
}
function toggleAdjVal(mkt_id, type) {
    var el = document.getElementById('adj_val_' + mkt_id);
    if (!el) return;
    el.style.display = (type === 'none') ? 'none' : '';
    var span = el.querySelector('.mkt-sync-info');
    if (span) span.textContent = (type.indexOf('pct') >= 0) ? '%' : '€';
}
function forceSyncProduct(productId) {
    var mktId   = document.getElementById('mkt_sync_target').value;
    var btn     = document.getElementById('btn_force_sync');
    var spinner = document.getElementById('sync_spinner');
    var box     = document.getElementById('sync_result_box');
    btn.disabled = true; spinner.style.display = ''; box.style.display = 'none';
    fetch('<?php echo dol_buildpath('/custom/marketplace_bdc/marketplace/sync_product.php', 1); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId + '&mkt_id=' + encodeURIComponent(mktId)
              + '&token=<?php echo currentToken(); ?>'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false; spinner.style.display = 'none'; box.style.display = '';
        if (!data.ok && !data.results) {
            box.innerHTML = '<div style="background:#fde;border:1px solid #f99;border-radius:4px;padding:10px 14px;color:#c00;">❌ ' + esc(data.msg) + '</div>';
            return;
        }
        var html = '<div style="font-size:12px;color:#555;margin-bottom:8px;">Synchronisé à ' + (data.at||'') + '</div>';
        html += '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
        html += '<tr style="background:#e8f4fd;"><th style="padding:6px 10px;text-align:left">Marketplace</th><th style="padding:6px 10px;text-align:left">Statut</th><th style="padding:6px 10px;text-align:left">Prix</th><th style="padding:6px 10px;text-align:left">Stock</th></tr>';
        for (var id in data.results) {
            var r = data.results[id];
            html += '<tr style="background:' + (r.ok ? '#f0fff4' : '#fff0f0') + ';">'
                  + '<td style="padding:6px 10px"><strong>' + esc(r.name) + '</strong></td>'
                  + '<td style="padding:6px 10px">' + (r.ok ? '✅' : '❌') + ' ' + esc(r.msg) + '</td>'
                  + '<td style="padding:6px 10px">' + (r.price ? r.price.toFixed(2) + ' € TTC' : '—') + '</td>'
                  + '<td style="padding:6px 10px">' + (r.stock !== undefined ? r.stock + ' unités' : '—') + '</td>'
                  + '</tr>';
        }
        html += '</table>';
        box.innerHTML = html;
        setTimeout(function() { location.reload(); }, 3000);
    })
    .catch(function(err) {
        btn.disabled = false; spinner.style.display = 'none'; box.style.display = '';
        box.innerHTML = '<div style="background:#fde;border:1px solid #f99;border-radius:4px;padding:10px;color:#c00;">❌ ' + esc(err.toString()) + '</div>';
    });
}
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>

<?php
llxFooter();
