<?php
/**
 * Module MarketPlace_BDC
 * 
 * Dolibarr Module Declaration
 * Manages product offers across multiple marketplaces
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modMarketPlace_BDC extends DolibarrModules
{
    /**
     * Constructor
     */
    public function __construct($db)
    {
        parent::__construct($db);

        $this->numero = 500000;
        $this->rights_class = 'marketplace_bdc';
        $this->family = 'technic';
        $this->module_position = 70;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->description = 'MarketPlace Manager - Manage product offers across multiple marketplaces (ADEO, Cdiscount, Amazon, WooCommerce)';
        $this->version = '1.2.0';  // Version Dolibarr (must match VersionManager)
        $this->version_dolibarr = '17.0';  // Minimum Dolibarr version
        $this->need_dolibarr_version = array(17, 0);
        $this->phpmin = array(8, 1);
        $this->picto = 'fa-globe';

        // Data folders
        $this->dirs = array(
            '/marketplace_bdc/marketplace',
            '/marketplace_bdc/admin',
            '/marketplace_bdc/class',
            '/marketplace_bdc/class/api',
            '/marketplace_bdc/sql',
            '/marketplace_bdc/modules/configs',
        );

        // Configs
        $this->config_page_url = array('setup.php@marketplace_bdc');

        // Dependencies
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array('marketplace_bdc@marketplace_bdc');

        // Constants
        $this->const = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Some pages
        $this->menu = array();

        // Triggers
        $this->triggers = array();

        // Permissions
        $this->rights = array();
    }

    /**
     * Init
     * 
     * @param string $options Options
     * @return int 1 if ok, 0 if error
     */
    public function init($options = '')
    {
        $sql = array();

        // Create SQL tables
        $this->load_tables_sql_files('/marketplace_bdc/sql/');

        // Rights
        $this->rights = array();
        $r = 0;

        // Read
        $this->rights[$r][0] = $this->numero . sprintf('%02d', 1);
        $this->rights[$r][1] = 'Read marketplace offers';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'read';
        $r++;

        // Write
        $this->rights[$r][0] = $this->numero . sprintf('%02d', 2);
        $this->rights[$r][1] = 'Create/Update marketplace offers';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'write';
        $r++;

        // Sync
        $this->rights[$r][0] = $this->numero . sprintf('%02d', 3);
        $this->rights[$r][1] = 'Synchronize to marketplaces';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'sync';
        $r++;

        // Admin
        $this->rights[$r][0] = $this->numero . sprintf('%02d', 4);
        $this->rights[$r][1] = 'Administer marketplace configuration';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'admin';
        $r++;

        // Tabs - Product Card
        $this->tabs = array(
            'product:+marketplaces:Marketplaces:marketplace_bdc@marketplace_bdc:1:/custom/marketplace_bdc/marketplace/product_tab.php?id=__ID__',
        );

        // Menu entries
        $this->menu = array();
        $r = 0;

        // Dashboard
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=marketplace_bdc',
            'type' => 'left',
            'titre' => 'Dashboard',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'dashboard',
            'url' => '/custom/marketplace_bdc/marketplace/dashboard.php',
            'langs' => 'marketplace_bdc@marketplace_bdc',
            'position' => 100,
            'enabled' => 'isModEnabled("marketplace_bdc")',
            'perms' => '$user->hasRight("marketplace_bdc", "marketplace", "read")',
            'target' => '',
            'user' => 2,
        );

        // Orders
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=marketplace_bdc',
            'type' => 'left',
            'titre' => 'Orders',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'orders',
            'url' => '/custom/marketplace_bdc/marketplace/orders.php',
            'langs' => 'marketplace_bdc@marketplace_bdc',
            'position' => 110,
            'enabled' => 'isModEnabled("marketplace_bdc")',
            'perms' => '$user->hasRight("marketplace_bdc", "marketplace", "read")',
            'target' => '',
            'user' => 2,
        );

        // Configuration
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=marketplace_bdc',
            'type' => 'left',
            'titre' => 'Configuration',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'config',
            'url' => '/custom/marketplace_bdc/admin/setup.php',
            'langs' => 'marketplace_bdc@marketplace_bdc',
            'position' => 120,
            'enabled' => 'isModEnabled("marketplace_bdc")',
            'perms' => '$user->hasRight("marketplace_bdc", "marketplace", "admin")',
            'target' => '',
            'user' => 2,
        );

        return 1;
    }
}
