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
// Try main entry point used when URL is index.php
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

// ACTIONS
if ($action == 'update') {
    dolibarr_set_const($db, 'MARKETPLACE_BDC_ENABLE_SYNC', GETPOST('enable_sync') ? '1' : '0', 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'MARKETPLACE_BDC_AUTO_SYNC_TIME', GETPOST('auto_sync_time', 'int'), 'chaine', 0, '', $conf->entity);
    
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}

// Get settings
$enable_sync = $conf->global->MARKETPLACE_BDC_ENABLE_SYNC ?? '0';
$auto_sync_time = $conf->global->MARKETPLACE_BDC_AUTO_SYNC_TIME ?? '3600';

// DISPLAY PAGE
llxHeader();

$form = new Form($db);

// Title
print load_fiche_titre($langs->trans('Setup'), '', 'marketplace_bdc@marketplace_bdc');

// Configuration Form
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

// Configuration Box
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="2">' . $langs->trans('Configuration') . '</th>';
print '</tr>';

// Enable Sync
print '<tr>';
print '<td class="titlefield"><label for="enable_sync">' . $langs->trans('EnableAutoSync') . '</label></td>';
print '<td>';
print '<input type="checkbox" id="enable_sync" name="enable_sync" value="1"' . ($enable_sync ? ' checked' : '') . '>';
print ' <span class="opacitymedium">' . $langs->trans('EnableAutoSyncDesc') . '</span>';
print '</td>';
print '</tr>';

// Auto Sync Time
print '<tr>';
print '<td class="titlefield"><label for="auto_sync_time">' . $langs->trans('AutoSyncInterval') . ' (seconds)</label></td>';
print '<td>';
print '<input type="number" id="auto_sync_time" name="auto_sync_time" value="' . htmlspecialchars($auto_sync_time) . '" min="60" max="86400">';
print ' <span class="opacitymedium">' . $langs->trans('AutoSyncIntervalDesc') . '</span>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';
print '<br>';

// Action buttons
print '<div class="center">';
print '<input type="submit" class="button button-primary" value="' . $langs->trans('Save') . '">';
print '</div>';
print '</form>';

// Module Status Box
print '<hr>';
print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('ModuleStatus') . '</th>';
print '</tr>';
print '<tr>';
print '<td>';
print '✅ ' . $langs->trans('ModuleEnabled') . '<br>';
print '✅ ' . $langs->trans('Version') . ': 1.2.0<br>';
print '✅ ' . $langs->trans('Rights') . ': ' . $langs->trans('Configured') . '<br>';
print '✅ ' . $langs->trans('Tabs') . ': ' . $langs->trans('ProductTab') . '<br>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';

// Information Box
print '<hr>';
print '<div class="info">';
print '<h3>' . $langs->trans('Information') . '</h3>';
print '<ul>';
print '<li>' . $langs->trans('MarketPlacesIntegration') . '</li>';
print '<li>' . $langs->trans('SupportsMultipleMarketplaces') . '</li>';
print '<li>' . $langs->trans('AutomaticSynchronization') . '</li>';
print '</ul>';
print '</div>';

llxFooter();
?>
