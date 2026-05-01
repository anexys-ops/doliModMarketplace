<?php
/**
 * Setup Page - MarketPlace_BDC Module Configuration
 * 
 * @package     marketplace_bdc
 * @subpackage  admin
 * @author      BDC
 * @version     1.2.0
 */

// Load Dolibarr
$res = 0;
if (!defined('DOL_DOCUMENT_ROOT')) {
    if (file_exists("../main.inc.php")) {
        $res = @include("../main.inc.php");
    } elseif (file_exists("../../main.inc.php")) {
        $res = @include("../../main.inc.php");
    }
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

// ACTIONS
if ($action == 'update_general') {
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENABLE_SYNC', GETPOST('enable_sync') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_AUTO_SYNC_TIME', GETPOST('auto_sync_time', 'int'), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    $tab = 'general';
}

if ($action == 'add_endpoint') {
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

if ($action == 'delete_endpoint') {
    $endpoint_id = GETPOST('endpoint_id', 'alpha');
    $endpoints = json_decode($conf->global->MARKETPLACE_BDC_ENDPOINTS ?? '{}', true);
    unset($endpoints[$endpoint_id]);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENDPOINTS', json_encode($endpoints), 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('EndpointDeleted'), null, 'mesgs');
    $tab = 'endpoints';
}

if ($action == 'add_mapping') {
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

if ($action == 'delete_mapping') {
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
    'mappings' => $langs->trans('Mappings')
);

print '<div class="tabs">';
foreach ($tabs as $tab_key => $tab_label) {
    $class = ($tab == $tab_key) ? 'tab-active' : '';
    print '<a href="?tab=' . $tab_key . '" class="tab ' . $class . '">' . $tab_label . '</a>';
}
print '</div>';
print '<hr>';

// TAB 1: GENERAL SETTINGS
if ($tab == 'general') {
    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?tab=general">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_general">';
    
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
    print '<input type="hidden" name="action" value="add_endpoint">';
    
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
            print '<a href="?tab=endpoints&action=delete_endpoint&endpoint_id=' . urlencode($id) . '" class="button button-delete" onclick="return confirm(\'' . $langs->trans('ConfirmDelete') . '\');">' . $langs->trans('Delete') . '</a>';
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
    print '<input type="hidden" name="action" value="add_mapping">';
    
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
            print '<a href="?tab=mappings&action=delete_mapping&mapping_id=' . urlencode($id) . '" class="button button-delete" onclick="return confirm(\'' . $langs->trans('ConfirmDelete') . '\');">' . $langs->trans('Delete') . '</a>';
            print '</td></tr>';
        }
        print '</table></div>';
    }
}

llxFooter();
