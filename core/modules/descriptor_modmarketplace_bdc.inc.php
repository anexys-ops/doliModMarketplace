<?php
/**
 * Module MarketPlace_BDC
 * 
 * Copyright (C) 2026 BDC
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module MarketPlace_BDC
 */
class modMarketPlace_BDC extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs;

        $this->db = $db;

        // Id for module (must be unique).
        $this->numero = 500000;

        // Key text used to identify module
        $this->rights_class = 'marketplace_bdc';

        // Family
        $this->family = 'technic';

        // Module position
        $this->module_position = '70';

        // Module label
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description
        $this->description = 'MarketPlace Manager - Manage product offers across multiple marketplaces (ADEO, Cdiscount, Amazon, WooCommerce)';

        // Version
        $this->version = '1.2.0';

        // Key used in llx_const table
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        // Icon
        $this->picto = 'fa-globe';

        // Module parts
        $this->module_parts = array(
            'triggers' => 0,
            'login' => 0,
            'substitutions' => 0,
            'menus' => 0,
            'css' => 0,
            'js' => 0,
            'hooks' => array(),
            'models' => 0,
            'theme' => 0,
            'sms' => 0,
            'ckeditor' => 0,
        );

        // Data folders
        $this->dirs = array(
            '/marketplace_bdc/marketplace',
            '/marketplace_bdc/admin',
            '/marketplace_bdc/class',
            '/marketplace_bdc/class/api',
            '/marketplace_bdc/sql',
            '/marketplace_bdc/modules/configs',
        );

        // Dictionaries
        $this->dictionaries = array(
            'tabname' => array(),
            'tablib' => array(),
            'condition' => array(),
        );

        // Dependencies
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array('marketplace_bdc@marketplace_bdc');

        // Config pages
        $this->config_page_url = array('setup.php@marketplace_bdc');

        // Constants
        $this->const = array();

        // Boxes
        $this->boxes = array();

        // Cronjobs
        $this->cronjobs = array();

        // Permissions (initialized in init())
        $this->rights = array();

        // Menus (initialized in init())
        $this->menu = array();

        // Tabs - Product Card
        $this->tabs = array(
            'product:+marketplaces:Marketplaces:marketplace_bdc@marketplace_bdc:1:/custom/marketplace_bdc/marketplace/product_tab.php?id=__ID__',
        );

        // Triggers
        $this->triggers = array();
    }

    /**
     * Init
     *
     * @param string $options Options
     * @return int 1 if ok, 0 if error
     */
    public function init($options = '')
    {
        // Define rights during initialization
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

        // Logs sous le menu Tools de Dolibarr
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=tools',
            'type' => 'left',
            'titre' => 'Logs Import/Export Marketplace',
            'mainmenu' => 'tools',
            'leftmenu' => 'marketplace_bdc_logs',
            'url' => '/custom/marketplace_bdc/admin/tools.php',
            'langs' => 'marketplace_bdc@marketplace_bdc',
            'position' => 200,
            'enabled' => 'isModEnabled("marketplace_bdc")',
            'perms' => '$user->hasRight("marketplace_bdc", "marketplace", "admin")',
            'target' => '',
            'user' => 2,
        );

        return 1;
    }
}
