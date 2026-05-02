<?php
/**
 * Setup Page - MarketPlace_BDC Module Configuration
 *
 * @package     marketplace_bdc
 * @subpackage  admin
 */

// Load Dolibarr environment - standard detection pattern
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array('admin', 'marketplace_bdc@marketplace_bdc'));

if (!$user->admin) { accessforbidden(); }

// ─── Paramètres URL ──────────────────────────────────────────────────────────
$action     = GETPOST('action', 'aZ09');
$tab        = GETPOST('tab', 'alpha') ?: 'marketplaces';
$mkt_id     = GETPOST('mkt', 'alpha');   // marketplace sélectionnée

// ─── Structure par défaut des marketplaces ───────────────────────────────────
$default_marketplaces = array(
    'cdiscount' => array(
        'name'        => 'Cdiscount',
        'enabled'     => 0,
        'auth_type'   => 'oauth2',
        'client_id'   => 'LuxGreenApiCdiscount',
        'client_secret' => 'YlXszv0hpB86bwZSXkyHYvL7RX3s0fIa',
        'endpoints'   => array(
            'api'  => 'https://api.cdiscount.com/api/1.0',
            'auth' => 'https://api.cdiscount.com/api/1.0/auth/GenerateToken',
        ),
        'mappings' => array(
            'product' => array(
                array('source' => 'ref',           'target' => 'SellerProductId'),
                array('source' => 'label',         'target' => 'LongLabel'),
                array('source' => 'description',   'target' => 'Description'),
                array('source' => 'barcode',       'target' => 'Ean'),
                array('source' => 'weight',        'target' => 'Weight'),
            ),
            'price'   => array(
                array('source' => 'price_ttc',     'target' => 'Price'),
                array('source' => 'price',         'target' => 'EcoTax'),
            ),
            'stock'   => array(
                array('source' => 'stock_reel',    'target' => 'StockQuantity'),
            ),
            'order'   => array(
                array('source' => 'ref_client',    'target' => 'CustomerOrderNumber'),
            ),
        ),
    ),
    'mirakl_adeo' => array(
        'name'      => 'Mirakl ADEO',
        'enabled'   => 0,
        'auth_type' => 'apikey',
        'api_key'   => 'd93a0347-3645-41ff-98d0-8837017a1bfa',
        'endpoints' => array(
            'api' => 'https://adeo-marketplace.mirakl.net/api',
        ),
        'mappings' => array(
            'product' => array(
                array('source' => 'ref',         'target' => 'sku'),
                array('source' => 'label',       'target' => 'title'),
                array('source' => 'description', 'target' => 'description'),
                array('source' => 'barcode',     'target' => 'ean'),
            ),
            'price'   => array(
                array('source' => 'price_ttc',   'target' => 'price'),
                array('source' => 'tva_tx',      'target' => 'tax_rate'),
            ),
            'stock'   => array(
                array('source' => 'stock_reel',  'target' => 'quantity'),
            ),
            'order'   => array(
                array('source' => 'ref',         'target' => 'order_line_id'),
            ),
        ),
    ),
    'amazon' => array(
        'name'          => 'Amazon SP-API (EU)',
        'enabled'       => 0,
        'auth_type'     => 'oauth2_lwa',
        'seller_id'     => 'A3EH3LRP5DO8KW',
        'marketplace_id'=> 'A13V1IB3VIYZZH',
        'client_id'     => 'amzn1.application-oa2-client.9d11c3172c03474090f53b3f127d8759',
        'client_secret' => '',
        'refresh_token' => '',
        'endpoints'     => array(
            'api'     => 'https://sellingpartnerapi-eu.amazon.com',
            'auth'    => 'https://api.amazon.com/auth/o2/token',
            'sandbox' => 'https://sandbox.sellingpartnerapi-eu.amazon.com',
        ),
        'mappings' => array(
            'product' => array(
                array('source' => 'ref',         'target' => 'sku'),
                array('source' => 'label',       'target' => 'item_name'),
                array('source' => 'barcode',     'target' => 'external_product_id'),
                array('source' => 'description', 'target' => 'product_description'),
            ),
            'price'   => array(
                array('source' => 'price_ttc',   'target' => 'standard_price'),
                array('source' => 'price',       'target' => 'business_price'),
            ),
            'stock'   => array(
                array('source' => 'stock_reel',  'target' => 'quantity'),
                array('source' => 'stock_min',   'target' => 'fulfillment_latency'),
            ),
            'order'   => array(
                array('source' => 'ref_client',  'target' => 'buyer_order_id'),
            ),
        ),
    ),
);

// ─── Champs disponibles pour le mapping (par flux) ────────────────────────────
function mkt_get_mapping_fields($db, $flow)
{
    // Champs standard produit (communs aux flux produit / prix / stock)
    $product_fields = array(
        'Identification'  => array(
            'rowid'          => 'ID interne',
            'ref'            => 'Référence (ref)',
            'ref_ext'        => 'Référence externe',
            'barcode'        => 'Code-barres',
            'label'          => 'Libellé',
        ),
        'Description'     => array(
            'description'    => 'Description',
            'note_public'    => 'Note publique',
            'url'            => 'URL publique',
        ),
        'Prix'            => array(
            'price'          => 'Prix HT',
            'price_ttc'      => 'Prix TTC',
            'price_min'      => 'Prix min HT',
            'price_min_ttc'  => 'Prix min TTC',
            'cost_price'     => 'Prix de revient',
            'tva_tx'         => 'Taux TVA (%)',
        ),
        'Stock / logistique' => array(
            'stock_reel'     => 'Stock réel',
            'desiredstock'   => 'Stock désiré',
            'seuil_stock_alerte' => 'Seuil alerte stock',
            'weight'         => 'Poids (kg)',
            'weight_units'   => 'Unité poids',
            'length'         => 'Longueur',
            'width'          => 'Largeur',
            'height'         => 'Hauteur',
            'surface'        => 'Surface',
            'volume'         => 'Volume',
        ),
        'Classification'  => array(
            'fk_product_type' => 'Type produit (0=produit, 1=service)',
            'finished'       => 'Nature (0=brut, 1=fini)',
            'duration'       => 'Durée (services)',
            'customcode'     => 'Code douanier',
            'fk_country'     => 'Pays origine',
            'packaging'      => 'Colisage',
        ),
    );

    // Champs standard commande
    $order_fields = array(
        'En-tête commande' => array(
            'rowid'          => 'ID commande',
            'ref'            => 'Référence commande',
            'ref_client'     => 'Référence client',
            'date_commande'  => 'Date commande',
            'date_valid'     => 'Date validation',
            'date_cloture'   => 'Date clôture',
            'total_ht'       => 'Total HT',
            'total_tva'      => 'Total TVA',
            'total_ttc'      => 'Total TTC',
            'source'         => 'Source/canal',
            'fk_statut'      => 'Statut',
            'note_private'   => 'Note privée',
            'note_public'    => 'Note publique',
        ),
        'Tiers'            => array(
            'fk_soc'         => 'ID tiers',
            'soc_nom'        => 'Nom tiers',
            'soc_email'      => 'Email tiers',
            'soc_phone'      => 'Téléphone tiers',
            'soc_address'    => 'Adresse livraison',
            'soc_zip'        => 'Code postal livraison',
            'soc_town'       => 'Ville livraison',
            'soc_country'    => 'Pays livraison',
        ),
        'Ligne de commande' => array(
            'line_label'     => 'Libellé ligne',
            'line_description' => 'Description ligne',
            'line_qty'       => 'Quantité',
            'line_subprice'  => 'Prix unitaire HT',
            'line_total_ht'  => 'Total ligne HT',
            'line_total_ttc' => 'Total ligne TTC',
            'line_tva_tx'    => 'TVA ligne (%)',
            'line_remise_percent' => 'Remise ligne (%)',
            'line_product_ref' => 'Référence produit ligne',
        ),
    );

    // Sélection selon le flux
    $fields = array();
    if (in_array($flow, array('product', 'price', 'stock'))) {
        $fields = $product_fields;
    } elseif ($flow === 'order') {
        $fields = $order_fields;
    }

    // Ajouter les champs perso (extrafields) depuis la DB
    $element_types = ($flow === 'order') ? array('commande', 'commandedet') : array('product');
    $extra_group = array();
    foreach ($element_types as $etype) {
        $sql = "SELECT name, label, type FROM ".MAIN_DB_PREFIX."extrafields"
             . " WHERE elementtype = '".$db->escape($etype)."'"
             . " AND entity IN (0, ".(int)$GLOBALS['conf']->entity.")"
             . " ORDER BY pos";
        $res = $db->query($sql);
        if ($res) {
            while ($obj = $db->fetch_object($res)) {
                $key   = 'options_'.$obj->name;
                $label = $obj->label.' ('.$obj->type.')';
                $extra_group[$key] = $label;
            }
        }
    }
    if (!empty($extra_group)) {
        $fields['Champs personnalisés'] = $extra_group;
    }

    return $fields;
}

// ─── Chargement config en DB ──────────────────────────────────────────────────
$db_config_raw = isset($conf->global->MARKETPLACE_BDC_MARKETPLACES) ? $conf->global->MARKETPLACE_BDC_MARKETPLACES : '{}';
$db_marketplaces = json_decode($db_config_raw, true);
if (!is_array($db_marketplaces)) { $db_marketplaces = array(); }

// Merge defaults avec DB (DB prioritaire sauf si la valeur DB est vide string)
$marketplaces = $default_marketplaces;
foreach ($db_marketplaces as $k => $v) {
    if (isset($marketplaces[$k])) {
        foreach ($v as $field => $val) {
            if ($val !== '' || !isset($marketplaces[$k][$field])) {
                $marketplaces[$k][$field] = $val;
            }
        }
    } else {
        $marketplaces[$k] = $v;
    }
}

// Paramètres généraux
$enable_sync    = isset($conf->global->MARKETPLACE_BDC_ENABLE_SYNC)    ? $conf->global->MARKETPLACE_BDC_ENABLE_SYNC    : '0';
$auto_sync_time = isset($conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME) ? $conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME : '3600';

// ─── ACTIONS ─────────────────────────────────────────────────────────────────

// Sauvegarde paramètres généraux
if ($action == 'savegeneral') {
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENABLE_SYNC',    GETPOST('enable_sync') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_AUTO_SYNC_TIME', (int) GETPOST('auto_sync_time'), 'chaine', 0, '', $conf->entity);
    setEventMessages('Configuration sauvegardée.', null, 'mesgs');
    $tab = 'general';
}

// Sauvegarde config d'une marketplace
if ($action == 'storemkt' && $mkt_id) {
    if (!isset($db_marketplaces[$mkt_id])) { $db_marketplaces[$mkt_id] = array(); }
    $new_name = GETPOST('mkt_name', 'string');
    if ($new_name !== '') {
        $db_marketplaces[$mkt_id]['name'] = $new_name;
    }
    $db_marketplaces[$mkt_id]['enabled']       = GETPOST('mkt_enabled') ? 1 : 0;
    $db_marketplaces[$mkt_id]['auth_type']     = GETPOST('mkt_auth_type', 'alpha');
    $db_marketplaces[$mkt_id]['client_id']     = GETPOST('mkt_client_id', 'string');
    $db_marketplaces[$mkt_id]['client_secret'] = GETPOST('mkt_client_secret', 'string');
    $db_marketplaces[$mkt_id]['api_key']       = GETPOST('mkt_api_key', 'string');
    $db_marketplaces[$mkt_id]['seller_id']     = GETPOST('mkt_seller_id', 'string');
    $db_marketplaces[$mkt_id]['marketplace_id']= GETPOST('mkt_marketplace_id', 'string');
    $db_marketplaces[$mkt_id]['refresh_token'] = GETPOST('mkt_refresh_token', 'string');

    // Endpoints
    $ep_keys  = GETPOST('ep_key',  'array');
    $ep_urls  = GETPOST('ep_url',  'array');
    $endpoints_save = array();
    if (is_array($ep_keys)) {
        foreach ($ep_keys as $idx => $epk) {
            $epk = trim($epk);
            if ($epk !== '' && isset($ep_urls[$idx]) && trim($ep_urls[$idx]) !== '') {
                $endpoints_save[$epk] = trim($ep_urls[$idx]);
            }
        }
    }
    $db_marketplaces[$mkt_id]['endpoints'] = $endpoints_save;

    dolibarr_set_const($db, 'MARKETPLACE_BDC_MARKETPLACES', json_encode($db_marketplaces), 'chaine', 0, '', $conf->entity);
    setEventMessages('Marketplace sauvegardée.', null, 'mesgs');
    $tab = 'marketplaces';
}

// Ajouter un mapping pour un flux
if ($action == 'newfield' && $mkt_id) {
    $flow     = GETPOST('flow',   'alpha');
    $src      = GETPOST('msrc',  'alphanohtml');
    $tgt      = GETPOST('mtgt',  'alpha');
    if ($flow && $src && $tgt) {
        if (!isset($db_marketplaces[$mkt_id])) { $db_marketplaces[$mkt_id] = array(); }
        if (!isset($db_marketplaces[$mkt_id]['mappings'])) { $db_marketplaces[$mkt_id]['mappings'] = array(); }
        if (!isset($db_marketplaces[$mkt_id]['mappings'][$flow])) { $db_marketplaces[$mkt_id]['mappings'][$flow] = array(); }
        // Ne pas dupliquer
        $db_marketplaces[$mkt_id]['mappings'][$flow][] = array('source' => $src, 'target' => $tgt);
        dolibarr_set_const($db, 'MARKETPLACE_BDC_MARKETPLACES', json_encode($db_marketplaces), 'chaine', 0, '', $conf->entity);
        setEventMessages('Champ de mapping ajouté.', null, 'mesgs');
    }
    $tab = 'marketplaces';
}

// Supprimer un mapping
if ($action == 'rmfield' && $mkt_id) {
    $flow = GETPOST('flow',  'alpha');
    $idx  = (int) GETPOST('fidx', 'int');
    if ($flow && isset($db_marketplaces[$mkt_id]['mappings'][$flow][$idx])) {
        array_splice($db_marketplaces[$mkt_id]['mappings'][$flow], $idx, 1);
        dolibarr_set_const($db, 'MARKETPLACE_BDC_MARKETPLACES', json_encode($db_marketplaces), 'chaine', 0, '', $conf->entity);
        setEventMessages('Champ supprimé.', null, 'mesgs');
    }
    $tab = 'marketplaces';
}

// Ajouter une nouvelle marketplace personnalisée
if ($action == 'newmkt') {
    $new_id   = preg_replace('/[^a-z0-9_]/', '', strtolower(GETPOST('new_mkt_id', 'alpha')));
    $new_name = GETPOST('new_mkt_name', 'string');
    if ($new_id && $new_name && !isset($marketplaces[$new_id])) {
        $db_marketplaces[$new_id] = array(
            'name'      => $new_name,
            'enabled'   => 0,
            'auth_type' => 'apikey',
            'endpoints' => array('api' => ''),
            'mappings'  => array('product' => array(), 'price' => array(), 'stock' => array(), 'order' => array()),
        );
        dolibarr_set_const($db, 'MARKETPLACE_BDC_MARKETPLACES', json_encode($db_marketplaces), 'chaine', 0, '', $conf->entity);
        setEventMessages('Marketplace créée.', null, 'mesgs');
        $mkt_id = $new_id;
    }
    $tab = 'marketplaces';
}

// Supprimer une marketplace personnalisée (pas celles par défaut)
if ($action == 'rmmkt' && $mkt_id && !isset($default_marketplaces[$mkt_id])) {
    unset($db_marketplaces[$mkt_id]);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_MARKETPLACES', json_encode($db_marketplaces), 'chaine', 0, '', $conf->entity);
    setEventMessages('Marketplace supprimée.', null, 'mesgs');
    $mkt_id = '';
    $tab    = 'marketplaces';
}

// Test connexion
$test_result = null;
if ($action == 'testconn' && $mkt_id) {
    $mkt_cfg = $marketplaces[$mkt_id] ?? array();
    $url     = isset($mkt_cfg['endpoints']['api']) ? $mkt_cfg['endpoints']['api'] : '';
    if ($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        if (($mkt_cfg['auth_type'] ?? '') === 'apikey') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: '.$mkt_cfg['api_key']));
        }
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($code > 0) {
            $test_result = array('ok' => ($code < 400), 'msg' => 'HTTP '.$code.($err ? ' — '.$err : ''));
        } else {
            $test_result = array('ok' => false, 'msg' => 'Erreur cURL: '.$err);
        }
    } else {
        $test_result = array('ok' => false, 'msg' => 'Aucun endpoint API configuré.');
    }
    $tab = 'marketplaces';
}

// Recharger après actions
$db_config_raw   = isset($conf->global->MARKETPLACE_BDC_MARKETPLACES) ? $conf->global->MARKETPLACE_BDC_MARKETPLACES : '{}';
$db_marketplaces = json_decode($db_config_raw, true);
if (!is_array($db_marketplaces)) { $db_marketplaces = array(); }
$marketplaces = $default_marketplaces;
foreach ($db_marketplaces as $k => $v) {
    if (isset($marketplaces[$k])) {
        foreach ($v as $field => $val) {
            if ($val !== '' || !isset($marketplaces[$k][$field])) {
                $marketplaces[$k][$field] = $val;
            }
        }
    } else {
        $marketplaces[$k] = $v;
    }
}
$enable_sync    = isset($conf->global->MARKETPLACE_BDC_ENABLE_SYNC)    ? $conf->global->MARKETPLACE_BDC_ENABLE_SYNC    : '0';
$auto_sync_time = isset($conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME) ? $conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME : '3600';

// Marketplace active par défaut
if (!$mkt_id || !isset($marketplaces[$mkt_id])) {
    reset($marketplaces);
    $mkt_id = key($marketplaces);
}
$mkt = $marketplaces[$mkt_id];

// ─── AFFICHAGE ────────────────────────────────────────────────────────────────
llxHeader('', 'Configuration Marketplaces');

$form = new Form($db);

print load_fiche_titre('Module Marketplace BDC — Configuration', '', 'setup');

// Tabs principaux
$head = array(
    array($_SERVER['PHP_SELF'].'?tab=marketplaces', 'Marketplaces',        'marketplaces'),
    array($_SERVER['PHP_SELF'].'?tab=general',      'Paramètres Généraux', 'general'),
);

print dol_get_fiche_head($head, $tab, '', -1);

// ════════════════════════════════════════════════════════════════════════════
// TAB 1 : PARAMÈTRES GÉNÉRAUX
// ════════════════════════════════════════════════════════════════════════════
if ($tab == 'general') {
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=general">';
    print '<input type="hidden" name="token"  value="'.newToken().'">';
    print '<input type="hidden" name="action" value="savegeneral">';

    print '<div class="div-table-responsive"><table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="3">Synchronisation automatique</th></tr>';

    print '<tr>';
    print '<td class="titlefield" style="width:300px">Activer la synchronisation automatique</td>';
    print '<td><input type="checkbox" name="enable_sync" value="1"'.($enable_sync ? ' checked' : '').'>
           <span class="opacitymedium">Synchronise automatiquement les données vers les marketplaces</span></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="titlefield">Intervalle de synchronisation (secondes)</td>';
    print '<td><input type="number" name="auto_sync_time" value="'.htmlspecialchars($auto_sync_time).'" min="60" max="86400" style="width:120px">
           <span class="opacitymedium"> (min: 60s — max: 86400s = 24h)</span></td>';
    print '</tr>';

    print '</table></div><br>';
    print '<div class="center"><input type="submit" class="button button-primary" value="Sauvegarder"></div>';
    print '</form>';
}

// ════════════════════════════════════════════════════════════════════════════
// TAB 2 : MARKETPLACES
// ════════════════════════════════════════════════════════════════════════════
elseif ($tab == 'marketplaces') {

    print '<div style="display:flex; gap:24px; align-items:flex-start;">';

    // ── Sidebar sélecteur ──────────────────────────────────────────────────
    print '<div style="min-width:200px; border-right:1px solid #ddd; padding-right:16px;">';
    print '<p style="font-weight:bold; margin-bottom:8px; font-size:13px; color:#555;">MARKETPLACES</p>';
    foreach ($marketplaces as $kid => $kmkt) {
        $active    = ($kid === $mkt_id) ? 'background:#e8f4fd; border-left:3px solid #0069d9; font-weight:bold;' : '';
        $enabled   = ($kmkt['enabled'] ?? 0) ? '🟢' : '🔴';
        $is_custom = !isset($default_marketplaces[$kid]);
        print '<a href="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($kid).'"
                  style="display:block; padding:8px 12px; margin-bottom:4px; text-decoration:none; color:#333; border-radius:4px; '.$active.'">';
        print $enabled.' '.htmlspecialchars($kmkt['name']);
        if ($is_custom) { print ' <small style="color:#999">[custom]</small>'; }
        print '</a>';
    }
    // Ajouter nouvelle marketplace
    print '<hr style="margin:12px 0">';
    print '<button type="button" onclick="document.getElementById(\'newmkt_panel\').style.display=\'block\'" class="button button-sm" style="width:100%;font-size:12px">+ Ajouter</button>';
    print '<div id="newmkt_panel" style="display:none; margin-top:8px;">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=marketplaces">';
    print '<input type="hidden" name="token"  value="'.newToken().'">';
    print '<input type="hidden" name="action" value="newmkt">';
    print '<input type="text" name="new_mkt_id"   placeholder="id (ex: leroy)" style="width:100%;margin-bottom:4px" required><br>';
    print '<input type="text" name="new_mkt_name" placeholder="Nom affiché"   style="width:100%;margin-bottom:4px" required><br>';
    print '<input type="submit" class="button" value="Créer" style="width:100%">';
    print '</form></div>';
    print '</div>'; // end sidebar

    // ── Panel principal de la marketplace sélectionnée ────────────────────
    print '<div style="flex:1; min-width:0;">';

    if ($test_result) {
        $c = $test_result['ok'] ? 'ok' : 'error';
        print '<div class="'.$c.'" style="padding:8px 12px; margin-bottom:12px; border-radius:4px;">'.htmlspecialchars($test_result['msg']).'</div>';
    }

    // Formulaire principal marketplace
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'">';
    print '<input type="hidden" name="token"  value="'.newToken().'">';
    print '<input type="hidden" name="action" value="storemkt">';
    print '<input type="hidden" name="mkt"    value="'.htmlspecialchars($mkt_id).'">';

    // --- Section Informations générales ---
    print '<table class="noborder centpercent" style="margin-bottom:16px">';
    print '<tr class="liste_titre"><th colspan="2">'.htmlspecialchars($mkt['name']).' — Informations générales</th></tr>';
    print '<tr><td class="titlefield" style="width:240px">Nom affiché</td>';
    print '<td><input type="text" name="mkt_name" value="'.htmlspecialchars($mkt['name']).'" style="width:300px"></td></tr>';
    print '<tr><td>Activé</td>';
    print '<td><input type="checkbox" name="mkt_enabled" value="1"'.($mkt['enabled'] ? ' checked' : '').'> <span class="opacitymedium">Activer la synchronisation pour cette marketplace</span></td></tr>';
    print '</table>';

    // --- Section Authentification ---
    $auth_type = $mkt['auth_type'] ?? 'apikey';
    print '<table class="noborder centpercent" style="margin-bottom:16px">';
    print '<tr class="liste_titre"><th colspan="2">Authentification</th></tr>';
    print '<tr><td class="titlefield">Type d\'authentification</td>';
    print '<td><select name="mkt_auth_type" onchange="updateAuthFields(this.value)">';
    foreach (array('apikey' => 'API Key', 'oauth2' => 'OAuth2 (Client Credentials)', 'oauth2_lwa' => 'OAuth2 LWA (Amazon)', 'basic' => 'HTTP Basic') as $v => $l) {
        print '<option value="'.$v.'"'.($auth_type === $v ? ' selected' : '').'>'.$l.'</option>';
    }
    print '</select></td></tr>';

    // Champs auth — affichage conditionnel JS
    $fields = array(
        'apikey'     => array('mkt_api_key'       => 'API Key'),
        'oauth2'     => array('mkt_client_id'     => 'Client ID',     'mkt_client_secret' => 'Client Secret'),
        'oauth2_lwa' => array('mkt_client_id'     => 'Client ID',     'mkt_client_secret' => 'Client Secret',
                              'mkt_seller_id'     => 'Seller ID',     'mkt_marketplace_id'=> 'Marketplace ID',
                              'mkt_refresh_token' => 'Refresh Token'),
        'basic'      => array('mkt_client_id'     => 'Username',      'mkt_client_secret' => 'Password'),
    );
    $all_auth_fields = array('mkt_api_key', 'mkt_client_id', 'mkt_client_secret', 'mkt_seller_id', 'mkt_marketplace_id', 'mkt_refresh_token');
    foreach ($all_auth_fields as $fname) {
        $label_map = array(
            'mkt_api_key'        => 'API Key',
            'mkt_client_id'      => 'Client ID / Username',
            'mkt_client_secret'  => 'Client Secret / Password',
            'mkt_seller_id'      => 'Seller ID',
            'mkt_marketplace_id' => 'Marketplace ID',
            'mkt_refresh_token'  => 'Refresh Token',
        );
        $fkey    = str_replace('mkt_', '', $fname);
        $fval    = htmlspecialchars($mkt[$fkey] ?? '');
        $is_pass = in_array($fname, array('mkt_client_secret', 'mkt_api_key', 'mkt_refresh_token'));
        print '<tr class="auth_field auth_field_'.$fname.'" style="display:none"><td class="titlefield">'.$label_map[$fname].'</td>';
        print '<td><input type="'.($is_pass ? 'password' : 'text').'" name="'.$fname.'" value="'.$fval.'" style="width:420px" autocomplete="new-password"></td></tr>';
    }
    print '</table>';

    // --- Section Endpoints ---
    $cur_endpoints = $mkt['endpoints'] ?? array();
    print '<table class="noborder centpercent" style="margin-bottom:16px" id="endpoints_table">';
    print '<tr class="liste_titre">';
    print '<th>Endpoints</th><th style="width:55%">URL</th><th style="width:80px">Actions</th>';
    print '</tr>';
    foreach ($cur_endpoints as $epkey => $epurl) {
        print '<tr>';
        print '<td><input type="text" name="ep_key[]" value="'.htmlspecialchars($epkey).'" style="width:120px" placeholder="api / auth / webhook"></td>';
        print '<td><input type="url"  name="ep_url[]" value="'.htmlspecialchars($epurl).'" style="width:100%" placeholder="https://..."></td>';
        print '<td><button type="button" onclick="this.closest(\'tr\').remove()" class="button button-danger button-sm">✕</button></td>';
        print '</tr>';
    }
    print '<tr id="ep_add_row">';
    print '<td colspan="3"><button type="button" class="button button-sm" onclick="addEndpointRow()">+ Ajouter un endpoint</button></td>';
    print '</tr>';
    print '</table>';

    // Boutons sauvegarde + test
    print '<div style="display:flex; gap:12px; margin-bottom:24px;">';
    print '<input type="submit" class="button button-primary" value="💾 Sauvegarder la configuration">';
    print '</div>';
    print '</form>';

    // Bouton test connexion séparé (GET)
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'" style="display:inline">';
    print '<input type="hidden" name="token"  value="'.newToken().'">';
    print '<input type="hidden" name="action" value="testconn">';
    print '<input type="hidden" name="mkt"    value="'.htmlspecialchars($mkt_id).'">';
    print '<button type="submit" class="button button-action">🔌 Tester la connexion</button>';
    print '</form>';

    if (!isset($default_marketplaces[$mkt_id])) {
        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'&action=rmmkt" class="button button-danger" onclick="return confirm(\'Supprimer cette marketplace ?\')">🗑 Supprimer</a>';
    }

    // ── Section Mappings par flux ──────────────────────────────────────────
    print '<hr style="margin:24px 0">';
    print '<h3 style="margin-bottom:16px">Mappings par flux</h3>';

    $flow_labels = array('product' => '📦 Produit', 'price' => '💶 Prix', 'stock' => '🏭 Stock', 'order' => '🛒 Commande');
    $cur_flow    = GETPOST('flow', 'alpha') ?: 'product';
    $all_mappings = array_merge(
        isset($default_marketplaces[$mkt_id]['mappings']) ? $default_marketplaces[$mkt_id]['mappings'] : array(),
        isset($db_marketplaces[$mkt_id]['mappings'])      ? $db_marketplaces[$mkt_id]['mappings']      : array()
    );
    // Merge par flux
    $merged_mappings = array();
    foreach ($flow_labels as $fl => $fl_lbl) {
        $merged_mappings[$fl] = array();
        $seen = array();
        // defaults first
        if (!empty($default_marketplaces[$mkt_id]['mappings'][$fl])) {
            foreach ($default_marketplaces[$mkt_id]['mappings'][$fl] as $r) {
                $key = $r['source'].':'.$r['target'];
                if (!in_array($key, $seen)) { $merged_mappings[$fl][] = $r; $seen[] = $key; }
            }
        }
        // puis overrides DB
        if (!empty($db_marketplaces[$mkt_id]['mappings'][$fl])) {
            foreach ($db_marketplaces[$mkt_id]['mappings'][$fl] as $r) {
                $key = $r['source'].':'.$r['target'];
                if (!in_array($key, $seen)) { $merged_mappings[$fl][] = $r; $seen[] = $key; }
            }
        }
    }

    // Onglets flux
    print '<div style="display:flex; gap:4px; margin-bottom:0; border-bottom:2px solid #ddd;">';
    foreach ($flow_labels as $fl => $fl_lbl) {
        $cls = ($cur_flow === $fl) ? 'background:#0069d9;color:#fff;' : 'background:#f5f5f5;color:#333;';
        print '<a href="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'&flow='.$fl.'"
                  style="padding:8px 18px; text-decoration:none; border-radius:4px 4px 0 0; font-size:13px; '.$cls.'">'.$fl_lbl.'</a>';
    }
    print '</div>';

    $flow_rows = $merged_mappings[$cur_flow] ?? array();

    // Construire index plat champ_key => label pour affichage
    $all_field_groups = mkt_get_mapping_fields($db, $cur_flow);
    $field_label_index = array();
    foreach ($all_field_groups as $grp => $grp_fields) {
        foreach ($grp_fields as $fk => $fl) {
            $field_label_index[$fk] = $fl;
        }
    }

    print '<table class="noborder centpercent" style="margin-top:0;">';
    print '<tr class="liste_titre">';
    print '<th>Champ Dolibarr (source)</th><th>Champ Marketplace (cible)</th><th style="width:80px">Action</th>';
    print '</tr>';

    if (empty($flow_rows)) {
        print '<tr><td colspan="3" class="opacitymedium" style="padding:12px">Aucun mapping pour ce flux.</td></tr>';
    } else {
        foreach ($flow_rows as $idx => $row) {
            $src_key   = htmlspecialchars($row['source']);
            $src_label = isset($field_label_index[$row['source']]) ? htmlspecialchars($field_label_index[$row['source']]) : $src_key;
            print '<tr>';
            print '<td><span title="'.$src_key.'" style="cursor:help">'.$src_label.'</span> <code style="font-size:11px;color:#888">['.$src_key.']</code></td>';
            print '<td><code>'.htmlspecialchars($row['target']).'</code></td>';
            print '<td>';
            print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'&flow='.$cur_flow.'" style="display:inline">';
            print '<input type="hidden" name="token"  value="'.newToken().'">';
            print '<input type="hidden" name="action" value="rmfield">';
            print '<input type="hidden" name="mkt"    value="'.htmlspecialchars($mkt_id).'">';
            print '<input type="hidden" name="flow"   value="'.$cur_flow.'">';
            print '<input type="hidden" name="fidx"   value="'.$idx.'">';
            print '<button type="submit" class="button button-danger button-sm" onclick="return confirm(\'Supprimer ?\')">✕</button>';
            print '</form>';
            print '</td></tr>';
        }
    }

    // Formulaire ajout mapping
    $mapping_field_groups = mkt_get_mapping_fields($db, $cur_flow);
    print '<tr style="background:#f9f9f9">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?tab=marketplaces&mkt='.urlencode($mkt_id).'&flow='.$cur_flow.'">';
    print '<input type="hidden" name="token"  value="'.newToken().'">';
    print '<input type="hidden" name="action" value="newfield">';
    print '<input type="hidden" name="mkt"    value="'.htmlspecialchars($mkt_id).'">';
    print '<input type="hidden" name="flow"   value="'.$cur_flow.'">';
    print '<td>';
    print '<select name="msrc" style="width:100%" required>';
    print '<option value="">— Choisir un champ Dolibarr —</option>';
    foreach ($mapping_field_groups as $group_label => $group_fields) {
        print '<optgroup label="'.htmlspecialchars($group_label).'">';
        foreach ($group_fields as $field_key => $field_label) {
            print '<option value="'.htmlspecialchars($field_key).'">'.htmlspecialchars($field_label).' ['.$field_key.']</option>';
        }
        print '</optgroup>';
    }
    print '</select>';
    print '</td>';
    print '<td><input type="text" name="mtgt" placeholder="Champ marketplace (ex: SellerProductId)" style="width:100%" required></td>';
    print '<td><button type="submit" class="button button-sm">+ Ajouter</button></td>';
    print '</form>';
    print '</tr>';
    print '</table>';

    print '</div>'; // end panel principal
    print '</div>'; // end flex
}

print dol_get_fiche_end();

// ─── JS ───────────────────────────────────────────────────────────────────────
?>
<script>
// Affichage dynamique des champs d'auth
var authFields = {
    apikey:     ['mkt_api_key'],
    oauth2:     ['mkt_client_id', 'mkt_client_secret'],
    oauth2_lwa: ['mkt_client_id', 'mkt_client_secret', 'mkt_seller_id', 'mkt_marketplace_id', 'mkt_refresh_token'],
    basic:      ['mkt_client_id', 'mkt_client_secret']
};
function updateAuthFields(type) {
    document.querySelectorAll('.auth_field').forEach(function(el) { el.style.display = 'none'; });
    var show = authFields[type] || [];
    show.forEach(function(fname) {
        document.querySelectorAll('.auth_field_' + fname).forEach(function(el) { el.style.display = ''; });
    });
}
// Init au chargement
document.addEventListener('DOMContentLoaded', function() {
    var sel = document.querySelector('[name="mkt_auth_type"]');
    if (sel) updateAuthFields(sel.value);
});

// Ajout d'une ligne endpoint dynamiquement
function addEndpointRow() {
    var table = document.getElementById('endpoints_table');
    var addRow = document.getElementById('ep_add_row');
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="ep_key[]" style="width:120px" placeholder="api / auth / webhook"></td>'
                 + '<td><input type="url" name="ep_url[]" style="width:100%" placeholder="https://..."></td>'
                 + '<td><button type="button" onclick="this.closest(\'tr\').remove()" class="button button-danger button-sm">✕</button></td>';
    table.insertBefore(tr, addRow);
}
</script>
<?php

llxFooter();
