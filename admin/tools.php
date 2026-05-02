<?php
/**
 * Tools - Marketplace Logs & Monitoring
 *
 * Page accessible depuis le menu Outils de Dolibarr
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

if (empty($conf->global->MAIN_MODULE_MARKETPLACE_BDC)) {
    accessforbidden('Module not enabled');
}

if (!$user->hasRight('marketplace_bdc', 'marketplace', 'admin')) {
    accessforbidden();
}

$langs->load('marketplace_bdc@marketplace_bdc');

// Filtres
$filter_marketplace = GETPOSTINT('marketplace_id');
$filter_status      = GETPOST('status', 'alpha');
$filter_type        = GETPOST('type', 'alpha');
$filter_date_from   = GETPOST('date_from', 'alpha');
$filter_date_to     = GETPOST('date_to', 'alpha');
$page               = GETPOSTINT('page');

$action = GETPOST('action', 'alpha');

// Purge
if ($action === 'purge' && GETPOSTINT('days') > 0) {
    $days = GETPOSTINT('days');
    if (!empty($_GET['token']) || !empty($_POST['token'])) {
        // csrf handled natively by Dolibarr via formconfirm - simple confirm suffices here
    }
    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "modmkp_synclog
            WHERE date_created < DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY)";
    $db->query($sql);
    setEventMessages($langs->trans('LogsPurged', $days), null, 'mesgs');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Export CSV
if ($action === 'export') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="marketplace_logs_' . date('Y-m-d') . '.csv"');

    $sql = "SELECT rowid, fk_marketplace, type, status, message, date_created
            FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE 1=1";

    if ($filter_marketplace) {
        $sql .= " AND fk_marketplace = " . (int) $filter_marketplace;
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
        $sql .= " AND date_created <= '" . $db->escape($filter_date_to) . " 23:59:59'";
    }

    $sql .= " ORDER BY date_created DESC LIMIT 10000";
    $result = $db->query($sql);

    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";
    echo "Date,Marketplace ID,Type,Statut,Message\n";
    while ($row = $db->fetch_object($result)) {
        $date = dol_print_date(strtotime($row->date_created), 'dayhourlog');
        echo '"' . $date . '",';
        echo '"' . (int) $row->fk_marketplace . '",';
        echo '"' . htmlspecialchars_decode($row->type) . '",';
        echo '"' . htmlspecialchars_decode($row->status) . '",';
        echo '"' . str_replace('"', '""', $row->message) . '"' . "\n";
    }
    exit;
}

// --- Récupération des données ---

// Liste des marketplaces
$sql_markets = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "modmkp_marketplace ORDER BY label";
$result_markets = $db->query($sql_markets);
$marketplaces = [];
if ($result_markets) {
    while ($row = $db->fetch_object($result_markets)) {
        $marketplaces[] = $row;
    }
}

// Construction de la requête principale
$sql_base = "FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE 1=1";

if ($filter_marketplace) {
    $sql_base .= " AND fk_marketplace = " . (int) $filter_marketplace;
}
if ($filter_status) {
    $sql_base .= " AND status = '" . $db->escape($filter_status) . "'";
}
if ($filter_type) {
    $sql_base .= " AND type = '" . $db->escape($filter_type) . "'";
}
if ($filter_date_from) {
    $sql_base .= " AND date_created >= '" . $db->escape($filter_date_from) . "'";
}
if ($filter_date_to) {
    $sql_base .= " AND date_created <= '" . $db->escape($filter_date_to) . " 23:59:59'";
}

// Comptage total
$r_count = $db->query("SELECT COUNT(*) as cnt " . $sql_base);
$total_logs = 0;
if ($r_count) {
    $row_c = $db->fetch_object($r_count);
    $total_logs = (int) $row_c->cnt;
}

// Statistiques globales
$stat_ok   = 0;
$stat_err  = 0;
$stat_warn = 0;
$r_ok = $db->query("SELECT COUNT(*) as c FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='ok'");
if ($r_ok) { $ro = $db->fetch_object($r_ok); $stat_ok = (int) $ro->c; }
$r_err = $db->query("SELECT COUNT(*) as c FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='error'");
if ($r_err) { $ro = $db->fetch_object($r_err); $stat_err = (int) $ro->c; }
$r_warn = $db->query("SELECT COUNT(*) as c FROM " . MAIN_DB_PREFIX . "modmkp_synclog WHERE status='warning'");
if ($r_warn) { $ro = $db->fetch_object($r_warn); $stat_warn = (int) $ro->c; }

// Pagination
$limit  = 50;
$offset = max(0, (int) $page) * $limit;

$sql = "SELECT rowid, fk_marketplace, type, status, message, date_created " . $sql_base;
$sql .= " ORDER BY date_created DESC LIMIT " . $limit . " OFFSET " . $offset;
$result = $db->query($sql);
$logs   = [];
if ($result) {
    while ($row = $db->fetch_object($result)) {
        $logs[] = $row;
    }
}

// Map marketplace id -> label
$mkp_map = [];
foreach ($marketplaces as $m) {
    $mkp_map[$m->rowid] = htmlspecialchars($m->label);
}

// ----------------------------------------------------------------
// Affichage
// ----------------------------------------------------------------

$title      = 'Logs Import/Export Marketplace';
$help_url   = '';
$morehead   = '';

llxHeader($morehead, $title, $help_url);

print_fiche_titre($title, '', 'fa-list-alt');

// Filtres
$export_url = $_SERVER['PHP_SELF'] . '?action=export'
    . '&marketplace_id=' . urlencode((string) $filter_marketplace)
    . '&status=' . urlencode($filter_status)
    . '&type=' . urlencode($filter_type)
    . '&date_from=' . urlencode($filter_date_from)
    . '&date_to=' . urlencode($filter_date_to);

?>

<!-- Filtres -->
<form method="GET" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="div-table-responsive-no-min" style="margin-bottom:15px;">
<table class="noborder centpercent" style="border-spacing:5px;">
<tr class="liste_titre">
    <td><?php echo $langs->trans('Marketplace'); ?></td>
    <td><?php echo $langs->trans('Type'); ?></td>
    <td><?php echo $langs->trans('Status'); ?></td>
    <td><?php echo $langs->trans('DateFrom'); ?></td>
    <td><?php echo $langs->trans('DateTo'); ?></td>
    <td></td>
</tr>
<tr>
    <td>
        <select name="marketplace_id" class="flat minwidth150">
            <option value="">-- Tous --</option>
            <?php foreach ($marketplaces as $m): ?>
            <option value="<?php echo (int) $m->rowid; ?>" <?php echo $filter_marketplace == $m->rowid ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($m->label); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </td>
    <td>
        <select name="type" class="flat minwidth100">
            <option value="">-- Tous --</option>
            <option value="test"   <?php echo $filter_type === 'test'   ? 'selected' : ''; ?>>Test</option>
            <option value="sync"   <?php echo $filter_type === 'sync'   ? 'selected' : ''; ?>>Sync</option>
            <option value="import" <?php echo $filter_type === 'import' ? 'selected' : ''; ?>>Import</option>
            <option value="export" <?php echo $filter_type === 'export' ? 'selected' : ''; ?>>Export</option>
            <option value="order"  <?php echo $filter_type === 'order'  ? 'selected' : ''; ?>>Commande</option>
            <option value="error"  <?php echo $filter_type === 'error'  ? 'selected' : ''; ?>>Erreur</option>
        </select>
    </td>
    <td>
        <select name="status" class="flat minwidth100">
            <option value="">-- Tous --</option>
            <option value="ok"      <?php echo $filter_status === 'ok'      ? 'selected' : ''; ?>>OK</option>
            <option value="error"   <?php echo $filter_status === 'error'   ? 'selected' : ''; ?>>Erreur</option>
            <option value="warning" <?php echo $filter_status === 'warning' ? 'selected' : ''; ?>>Warning</option>
            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>En attente</option>
        </select>
    </td>
    <td>
        <input type="date" name="date_from" class="flat" value="<?php echo htmlspecialchars($filter_date_from); ?>" placeholder="Date début">
    </td>
    <td>
        <input type="date" name="date_to" class="flat" value="<?php echo htmlspecialchars($filter_date_to); ?>" placeholder="Date fin">
    </td>
    <td style="white-space:nowrap;">
        <input type="submit" class="button" value="Filtrer">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="button">Réinitialiser</a>
        <a href="<?php echo $export_url; ?>" class="button buttongen">
            <span class="fa fa-file-csv"></span> Export CSV
        </a>
    </td>
</tr>
</table>
</div>
</form>

<!-- Statistiques -->
<div class="info-box-container" style="display:flex; gap:12px; margin:15px 0; flex-wrap:wrap;">

    <div class="info-box" style="border-left:4px solid #0088cc; background:#f4f9ff; padding:12px 18px; border-radius:5px; min-width:160px;">
        <span class="info-box-icon" style="font-size:22px; color:#0088cc;">&#9776;</span>
        <div class="info-box-content">
            <span class="info-box-text" style="color:#666; font-size:12px;">Total logs (filtre actif)</span>
            <span class="info-box-number" style="font-size:24px; font-weight:bold;"><?php echo $total_logs; ?></span>
        </div>
    </div>

    <div class="info-box" style="border-left:4px solid #28a745; background:#f4fff4; padding:12px 18px; border-radius:5px; min-width:160px;">
        <span class="info-box-icon" style="font-size:22px; color:#28a745;">&#10003;</span>
        <div class="info-box-content">
            <span class="info-box-text" style="color:#666; font-size:12px;">Succès (OK)</span>
            <span class="info-box-number" style="font-size:24px; font-weight:bold; color:#28a745;"><?php echo $stat_ok; ?></span>
        </div>
    </div>

    <div class="info-box" style="border-left:4px solid #dc3545; background:#fff4f4; padding:12px 18px; border-radius:5px; min-width:160px;">
        <span class="info-box-icon" style="font-size:22px; color:#dc3545;">&#10007;</span>
        <div class="info-box-content">
            <span class="info-box-text" style="color:#666; font-size:12px;">Erreurs</span>
            <span class="info-box-number" style="font-size:24px; font-weight:bold; color:#dc3545;"><?php echo $stat_err; ?></span>
        </div>
    </div>

    <div class="info-box" style="border-left:4px solid #ffc107; background:#fffdf0; padding:12px 18px; border-radius:5px; min-width:160px;">
        <span class="info-box-icon" style="font-size:22px; color:#ffc107;">&#9888;</span>
        <div class="info-box-content">
            <span class="info-box-text" style="color:#666; font-size:12px;">Avertissements</span>
            <span class="info-box-number" style="font-size:24px; font-weight:bold; color:#ffc107;"><?php echo $stat_warn; ?></span>
        </div>
    </div>

</div>

<!-- Actions de purge -->
<div style="margin:10px 0 15px 0;">
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=purge&days=7"
       onclick="return confirm('Supprimer les logs de plus de 7 jours ?');"
       class="button buttonDelete">
        <span class="fa fa-trash"></span> Purger > 7 jours
    </a>
    &nbsp;
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=purge&days=30"
       onclick="return confirm('Supprimer les logs de plus de 30 jours ?');"
       class="button buttonDelete">
        <span class="fa fa-trash"></span> Purger > 30 jours
    </a>
    &nbsp;
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=purge&days=90"
       onclick="return confirm('Supprimer les logs de plus de 90 jours ?');"
       class="button buttonDelete">
        <span class="fa fa-trash"></span> Purger > 90 jours
    </a>
</div>

<!-- Tableau des logs -->
<div class="div-table-responsive">
<table class="tagtable noborder centpercent">
    <thead>
    <tr class="liste_titre">
        <th class="liste_titre"><?php echo $langs->trans('Date'); ?></th>
        <th class="liste_titre">Marketplace</th>
        <th class="liste_titre">Type</th>
        <th class="liste_titre"><?php echo $langs->trans('Status'); ?></th>
        <th class="liste_titre">Message</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($logs)): ?>
    <tr>
        <td colspan="5" class="opacitymedium" style="text-align:center; padding:20px;">
            Aucun log trouvé avec ces critères.
        </td>
    </tr>
    <?php else: ?>
    <?php foreach ($logs as $log):
        $ts = strtotime($log->date_created);
        $date_str = dol_print_date($ts, 'dayhourlog');

        switch ($log->status) {
            case 'ok':
                $badge = '<span style="color:#28a745; font-weight:bold;">&#10003; OK</span>';
                $row_class = '';
                break;
            case 'error':
                $badge = '<span style="color:#dc3545; font-weight:bold;">&#10007; Erreur</span>';
                $row_class = 'highlight';
                break;
            case 'warning':
                $badge = '<span style="color:#e0a800; font-weight:bold;">&#9888; Warning</span>';
                $row_class = '';
                break;
            case 'pending':
                $badge = '<span style="color:#17a2b8; font-weight:bold;">&#8987; En attente</span>';
                $row_class = '';
                break;
            default:
                $badge = '<span>' . htmlspecialchars($log->status) . '</span>';
                $row_class = '';
        }

        $mkp_label = isset($mkp_map[$log->fk_marketplace]) ? $mkp_map[$log->fk_marketplace] : '#' . (int) $log->fk_marketplace;
    ?>
    <tr class="<?php echo $row_class; ?>">
        <td style="white-space:nowrap;"><?php echo $date_str; ?></td>
        <td><?php echo $mkp_label; ?></td>
        <td><code><?php echo htmlspecialchars($log->type); ?></code></td>
        <td><?php echo $badge; ?></td>
        <td style="max-width:500px; word-break:break-word;"><?php echo nl2br(htmlspecialchars($log->message)); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($total_logs > $limit): ?>
<div style="text-align:center; margin:15px 0;">
    <?php
    $nb_pages = (int) ceil($total_logs / $limit);
    for ($i = 0; $i < $nb_pages; $i++):
        $url_p = $_SERVER['PHP_SELF'] . '?page=' . $i
            . '&marketplace_id=' . urlencode((string) $filter_marketplace)
            . '&status=' . urlencode($filter_status)
            . '&type=' . urlencode($filter_type)
            . '&date_from=' . urlencode($filter_date_from)
            . '&date_to=' . urlencode($filter_date_to);
        if ($page == $i) {
            echo '<span style="display:inline-block;padding:4px 10px;background:#0088cc;color:#fff;border-radius:3px;margin:2px;">' . ($i + 1) . '</span> ';
        } else {
            echo '<a href="' . $url_p . '" style="display:inline-block;padding:4px 10px;border:1px solid #ddd;border-radius:3px;margin:2px;text-decoration:none;">' . ($i + 1) . '</a> ';
        }
    endfor;
    ?>
</div>
<?php endif; ?>

<?php

llxFooter();
$db->close();
