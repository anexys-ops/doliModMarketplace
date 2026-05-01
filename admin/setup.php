<?php
/**
 * Setup Page - MarketPlace_BDC Module Configuration
 * 
 * @package     marketplace_bdc
 * @subpackage  admin
 * @author      BDC
 * @version     1.2.0
 */

// Load Dolibarr environment properly
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Protect against direct access
if (!isset($conf) || !isset($user)) {
    header("Location: ../index.php");
    exit;
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

$langs->loadLangs(array('admin', 'marketplace_bdc@marketplace_bdc'));

// Check permission
if (!$user->admin) {
    accessforbidden();
}

// Get action
$action = GETPOST('action', 'alpha');
$tab = GETPOST('tab', 'alpha') ?: 'general';

// Predefined test endpoints
$test_endpoints = array(
    'cdiscount' => array(
        'name' => 'Cdiscount',
        'url' => 'https://api.cdiscount.com/api/1.0',
        'type' => 'REST',
        'auth_url' => 'https://api.cdiscount.com/api/1.0/auth/GenerateToken',
        'client_id' => 'LuxGreenApiCdiscount',
        'client_secret' => 'YlXszv0hpB86bwZSXkyHYvL7RX3s0fIa'
    ),
    'mirakl' => array(
        'name' => 'Mirakl ADEO',
        'url' => 'https://adeo-marketplace.mirakl.net/api',
        'type' => 'REST',
        'auth_type' => 'header',
        'api_key' => 'd93a0347-3645-41ff-98d0-8837017a1bfa'
    ),
    'amazon' => array(
        'name' => 'Amazon SP-API (EU)',
        'url' => 'https://sellingpartnerapi-eu.amazon.com',
        'type' => 'REST',
        'auth_type' => 'oauth2',
        'seller_id' => 'A3EH3LRP5DO8KW',
        'client_id' => 'amzn1.application-oa2-client.9d11c3172c03474090f53b3f127d8759'
    )
);

// Test endpoint connection
$test_result = null;
if ($action == 'test_endpoint') {
    $endpoint_id = GETPOST('test_endpoint_id', 'alpha');
    if (isset($test_endpoints[$endpoint_id])) {
        $endpoint = $test_endpoints[$endpoint_id];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint['url']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if ($endpoint_id == 'cdiscount' && isset($endpoint['auth_url'])) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
                    'ClientID' => $endpoint['client_id'],
                    'ClientSecret' => $endpoint['client_secret']
                )));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            } elseif ($endpoint_id == 'mirakl') {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: ' . $endpoint['api_key']
                ));
            }
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code >= 200 && $http_code < 400) {
                $test_result = array('status' => 'success', 'message' => $langs->trans('ConnectionOK') . ' (HTTP ' . $http_code . ')');
            } else {
                $test_result = array('status' => 'error', 'message' => $langs->trans('ConnectionFailed') . ' (HTTP ' . $http_code . ')');
            }
        } catch (Exception $e) {
            $test_result = array('status' => 'error', 'message' => $e->getMessage());
        }
    }
}

// ACTIONS
if ($action == 'savegeneral') {
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENABLE_SYNC', GETPOST('enable_sync') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_AUTO_SYNC_TIME', GETPOST('auto_sync_time', 'int'), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    $tab = 'general';
}

if ($action == 'newendpoint') {
    $endpoint_name = GETPOST('endpoint_name', 'alpha');
    $endpoint_url = GETPOST('endpoint_url', 'string');
    $endpoint_type = GETPOST('endpoint_type', 'alpha');
    
    if ($endpoint_name && $endpoint_url) {
        $endpoints = json_decode($conf->global->MARKETPLACE_BDC_ENDPOINTS ?? '{}', true);
        $endpoints[$endpoint_name] = array(
            'url' => $endpoint_url,
            'type' => $endpoint_type,
            'created' => date('Y-m-d H:i:s')
        );
        dolibarr_set_const($db, 'MARKETPLACE_BDC_ENDPOINTS', json_encode($endpoints), 'chaine', 0, '', $conf->entity);
        setEventMessages($langs->trans('EndpointAdded'), null, 'mesgs');
    }
    $tab = 'endpoints';
}

if ($action == 'rmendpoint') {
    $endpoint_id = GETPOST('endpoint_id', 'alpha');
    $endpoints = json_decode($conf->global->MARKETPLACE_BDC_ENDPOINTS ?? '{}', true);
    unset($endpoints[$endpoint_id]);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENDPOINTS', json_encode($endpoints), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('EndpointDeleted'), null, 'mesgs');
    $tab = 'endpoints';
}

if ($action == 'newmapping') {
    $mapping_name = GETPOST('mapping_name', 'alpha');
    $mapping_source = GETPOST('mapping_source', 'alpha');
    $mapping_target = GETPOST('mapping_target', 'alpha');
    $mapping_type = GETPOST('mapping_type', 'alpha');
    
    if ($mapping_name && $mapping_source && $mapping_target) {
        $mappings = json_decode($conf->global->MARKETPLACE_BDC_MAPPINGS ?? '{}', true);
        $mappings[$mapping_name] = array(
            'source' => $mapping_source,
            'target' => $mapping_target,
            'type' => $mapping_type,
            'created' => date('Y-m-d H:i:s')
        );
        dolibarr_set_const($db, 'MARKETPLACE_BDC_MAPPINGS', json_encode($mappings), 'chaine', 0, '', $conf->entity);
        setEventMessages($langs->trans('MappingAdded'), null, 'mesgs');
    }
    $tab = 'mappings';
}

if ($action == 'rmmapping') {
    $mapping_id = GETPOST('mapping_id', 'alpha');
    $mappings = json_decode($conf->global->MARKETPLACE_BDC_MAPPINGS ?? '{}', true);
    unset($mappings[$mapping_id]);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_MAPPINGS', json_encode($mappings), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('MappingDeleted'), null, 'mesgs');
    $tab = 'mappings';
}

// Get settings
$enable_sync = $conf->global->MARKETPLACE_BDC_ENABLE_SYNC ?? '0';
$auto_sync_time = $conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME ?? '3600';
$endpoints = json_decode($conf->global->MARKETPLACE_BDC_ENDPOINTS ?? '{}', true);
$mappings = json_decode($conf->global->MARKETPLACE_BDC_MAPPINGS ?? '{}', true);

// DISPLAY PAGE
llxHeader();

$form = new Form($db);

// Title with tabs
print load_fiche_titre($langs->trans('Setup'), '', 'marketplace_bdc@marketplace_bdc');

// Tab navigation
$tabs = array(
    'general' => $langs->trans('GeneralSettings'),
    'endpoints' => $langs->trans('Endpoints'),
    'mappings' => $langs->trans('Mappings'),
    'test' => $langs->trans('TestConnections')
);

print '<div class="tabs">';
foreach ($tabs as $tab_key => $tab_label) {
    $class = ($tab == $tab_key) ? 'tab-active' : '';
    print '<a href="?tab=' . $tab_key . '" class="tab ' . $class . '">' . $tab_label . '</a>';
}
print '</div>';
print '<hr>';

// If TAB 1: GENERAL SETTINGS
if ($tab == 'general') {
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?tab=general">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="savegeneral">';
    
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="2">' . $langs->trans('Configuration') . '</th></tr>';
    
    // Enable Sync
    print '<tr><td class="titlefield"><label for="enable_sync">' . $langs->trans('EnableAutoSync') . '</label></td>';
    print '<td><input type="checkbox" id="enable_sync" name="enable_sync" value="1"' . ($enable_sync ? ' checked' : '') . '> <span class="opacitymedium">' . $langs->trans('EnableAutoSyncDesc') . '</span></td></tr>';
    
    // Auto Sync Time
    print '<tr><td class="titlefield"><label for="auto_sync_time">' . $langs->trans('AutoSyncInterval') . ' (seconds)</label></td>';
    print '<td><input type="number" id="auto_sync_time" name="auto_sync_time" value="' . htmlspecialchars($auto_sync_time) . '" min="60" max="86400"> <span class="opacitymedium">' . $langs->trans('AutoSyncIntervalDesc') . '</span></td></tr>';
    
    print '</table></div><br>';
    print '<div class="center"><input type="submit" class="button button-primary" value="' . $langs->trans('Save') . '"></div>';
    print '</form>';
}

// TAB 2: ENDPOINTS
elseif ($tab == 'endpoints') {
    // Add Endpoint Form
    print '<div class="form-group" style="margin-bottom: 20px;">';
    print '<h3>' . $langs->trans('AddEndpoint') . '</h3>';
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?tab=endpoints" style="margin-top: 10px;">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="newendpoint">';
    
    print '<div class="div-table-responsive"><table class="noborder">';
    print '<tr>';
    print '<td><input type="text" name="endpoint_name" placeholder="' . $langs->trans('EndpointName') . '" required></td>';
    print '<td><input type="url" name="endpoint_url" placeholder="https://api.example.com" required></td>';
    print '<td><select name="endpoint_type"><option value="REST">REST</option><option value="SOAP">SOAP</option><option value="GraphQL">GraphQL</option></select></td>';
    print '<td><input type="submit" class="button" value="' . $langs->trans('Add') . '"></td>';
    print '</tr></table></div>';
    print '</form></div>';
    
    // List Endpoints
    if (!empty($endpoints)) {
        print '<h3>' . $langs->trans('ConfiguredEndpoints') . '</h3>';
        print '<div class="div-table-responsive"><table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>' . $langs->trans('Name') . '</th>';
        print '<th>' . $langs->trans('URL') . '</th>';
        print '<th>' . $langs->trans('Type') . '</th>';
        print '<th>' . $langs->trans('Created') . '</th>';
        print '<th>' . $langs->trans('Action') . '</th>';
        print '</tr>';
        
        foreach ($endpoints as $id => $endpoint) {
            print '<tr><td>' . htmlspecialchars($id) . '</td>';
            print '<td><code>' . htmlspecialchars($endpoint['url']) . '</code></td>';
            print '<td>' . htmlspecialchars($endpoint['type']) . '</td>';
            print '<td>' . $endpoint['created'] . '</td>';
            print '<td>';
            print '<a href="?tab=endpoints&action=rmendpoint&endpoint_id=' . urlencode($id) . '" class="button button-delete" onclick="return confirm(\'' . $langs->trans('ConfirmDelete') . '\');">' . $langs->trans('Delete') . '</a>';
            print '</td></tr>';
        }
        print '</table></div>';
    }
}

// TAB 3: MAPPINGS
elseif ($tab == 'mappings') {
    // Add Mapping Form
    print '<div class="form-group" style="margin-bottom: 20px;">';
    print '<h3>' . $langs->trans('AddMapping') . '</h3>';
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?tab=mappings" style="margin-top: 10px;">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="newmapping">';
    
    print '<div class="div-table-responsive"><table class="noborder">';
    print '<tr>';
    print '<td><input type="text" name="mapping_name" placeholder="' . $langs->trans('MappingName') . '" required></td>';
    print '<td><select name="mapping_type"><option value="product">Product</option><option value="price">Price</option><option value="stock">Stock</option><option value="order">Order</option></select></td>';
    print '<td><input type="text" name="mapping_source" placeholder="' . $langs->trans('SourceField') . '" required></td>';
    print '<td><input type="text" name="mapping_target" placeholder="' . $langs->trans('TargetField') . '" required></td>';
    print '<td><input type="submit" class="button" value="' . $langs->trans('Add') . '"></td>';
    print '</tr></table></div>';
    print '</form></div>';
    
    // List Mappings
    if (!empty($mappings)) {
        print '<h3>' . $langs->trans('ConfiguredMappings') . '</h3>';
        print '<div class="div-table-responsive"><table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th>' . $langs->trans('Name') . '</th>';
        print '<th>' . $langs->trans('Type') . '</th>';
        print '<th>' . $langs->trans('SourceField') . '</th>';
        print '<th>' . $langs->trans('TargetField') . '</th>';
        print '<th>' . $langs->trans('Created') . '</th>';
        print '<th>' . $langs->trans('Action') . '</th>';
        print '</tr>';
        
        foreach ($mappings as $id => $mapping) {
            print '<tr><td>' . htmlspecialchars($id) . '</td>';
            print '<td><span class="badge">' . $mapping['type'] . '</span></td>';
            print '<td><code>' . htmlspecialchars($mapping['source']) . '</code></td>';
            print '<td><code>' . htmlspecialchars($mapping['target']) . '</code></td>';
            print '<td>' . $mapping['created'] . '</td>';
            print '<td>';
            print '<a href="?tab=mappings&action=rmmapping&mapping_id=' . urlencode($id) . '" class="button button-delete" onclick="return confirm(\'' . $langs->trans('ConfirmDelete') . '\');">' . $langs->trans('Delete') . '</a>';
            print '</td></tr>';
        }
        print '</table></div>';
    }
}

// TAB 4: TEST CONNECTIONS
elseif ($tab == 'test') {
    print '<h3>' . $langs->trans('TestConnections') . '</h3>';
    
    if ($test_result) {
        $class = ($test_result['status'] == 'success') ? 'alert-success' : 'alert-danger';
        print '<div class="' . $class . '" style="padding: 10px; margin-bottom: 20px;">';
        print $test_result['message'];
        print '</div>';
    }
    
    print '<h4>Endpoints de Test Disponibles</h4>';
    print '<div class="div-table-responsive" style="margin-bottom: 20px;">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>Marketplace</th>';
    print '<th>Endpoint</th>';
    print '<th>Auth Type</th>';
    print '<th>Action</th>';
    print '</tr>';
    
    foreach ($test_endpoints as $id => $endpoint) {
        print '<tr>';
        print '<td><strong>' . $endpoint['name'] . '</strong></td>';
        print '<td><code style="font-size: 11px;">' . htmlspecialchars($endpoint['url']) . '</code></td>';
        print '<td>';
        if ($id == 'cdiscount') print '<span class="badge">OAuth2</span>';
        elseif ($id == 'mirakl') print '<span class="badge">API Key</span>';
        elseif ($id == 'amazon') print '<span class="badge">OAuth2 LWA</span>';
        print '</td>';
        print '<td>';
        print '<form method="POST" style="display: inline;">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="test_endpoint">';
        print '<input type="hidden" name="tab" value="test">';
        print '<input type="hidden" name="test_endpoint_id" value="' . htmlspecialchars($id) . '">';
        print '<input type="submit" class="button button-sm" value="Tester">';
        print '</form>';
        print '</td>';
        print '</tr>';
    }
    
    print '</table>';
    print '</div>';
    
    // Test Keys Display
    print '<hr>';
    print '<h4>Clés de Test Configurées</h4>';
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th>Clé</th><th>Valeur</th><th>Statut</th></tr>';
    
    $test_keys = array(
        'CDISCOUNT_CLIENT_ID' => 'LuxGreenApiCdiscount',
        'CDISCOUNT_API_BASE' => 'https://api.cdiscount.com/api/1.0',
        'MIRAKL_API_KEY' => 'd93a0347-3645-41ff-98d0-8837017a1bfa',
        'MIRAKL_API_BASE' => 'https://adeo-marketplace.mirakl.net/api',
        'AMAZON_SELLER_ID' => 'A3EH3LRP5DO8KW',
        'AMAZON_MARKETPLACE_FR' => 'A13V1IB3VIYZZH'
    );
    
    foreach ($test_keys as $key => $value) {
        print '<tr>';
        print '<td><strong>' . htmlspecialchars($key) . '</strong></td>';
        print '<td><code style="font-size: 11px;">' . htmlspecialchars(substr($value, 0, 50)) . (strlen($value) > 50 ? '...' : '') . '</code></td>';
        print '<td><span class="badge badge-success">✓</span></td>';
        print '</tr>';
    }
    
    print '</table>';
    print '</div>';
}

llxFooter();
