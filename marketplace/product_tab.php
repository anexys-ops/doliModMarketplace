<?php
/**
 * Product Tab - MarketPlace Offers
 * 
 * Integrated as a Dolibarr product tab
 * This file is called by Dolibarr with $object (Product) already loaded
 */

// Context is loaded by Dolibarr core
// Variables available: $db, $user, $langs, $conf, $object (Product), $hookmanager

// Check permission
if (!$user->hasRight('marketplace_bdc', 'marketplace', 'read')) {
    echo dol_print_error('', 'Access denied');
    return;
}

// Get product ID
$product_id = $object->id ?? 0;

if (!$product_id) {
    echo '<div class="notice">No product ID provided</div>';
    return;
}

// Begin tab content
?>
<form method="POST" id="marketplace_form" class="tabpanel">
    <?php echo $hookmanager->getReplacedContent('product_tab', array('product_id' => $product_id)); ?>
    <input type="hidden" name="token" value="<?php echo newToken(); ?>">
    <input type="hidden" name="action" value="save_marketplace">
    <input type="hidden" name="object_id" value="<?php echo $product_id; ?>">
    
    <div class="div-table-responsive">
        <table class="noborder">
            <tr class="liste_titre">
                <th><?php echo $langs->trans('Marketplace'); ?></th>
                <th><?php echo $langs->trans('SKU'); ?></th>
                <th><?php echo $langs->trans('Price'); ?></th>
                <th><?php echo $langs->trans('Quantity'); ?></th>
                <th><?php echo $langs->trans('Status'); ?></th>
                <th><?php echo $langs->trans('Action'); ?></th>
            </tr>
        </table>
    </div>
    
    <div class="tabsAction">
        <?php if ($user->hasRight('marketplace_bdc', 'marketplace', 'write')) { ?>
            <input type="submit" class="button" value="<?php echo $langs->trans('Save'); ?>">
        <?php } ?>
    </div>
</form>

<?php
// Tab content ends
?>
