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

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

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

        // Id unique du module (voir https://wiki.dolibarr.org/index.php/List_of_modules_id)
        $this->numero = 500120;

        // Identifiant texte du module
        $this->rights_class = 'marketplace_bdc';

        // Famille
        $this->family = 'technic';

        // Position dans la famille
        $this->module_position = '70';

        // Nom du module (extrait automatiquement du nom de classe)
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Description
        $this->description = 'MarketPlace Manager - Gestion des offres produits sur les places de marché (ADEO, Cdiscount, Amazon)';

        // Version
        $this->version = '1.2.0';

        // Clé dans llx_const
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Icône
        $this->picto = 'fa-globe';

        // Parties du module
        $this->module_parts = array(
            'triggers'      => 0,
            'login'         => 0,
            'substitutions' => 0,
            'menus'         => 0,
            'css'           => 0,
            'js'            => 0,
            'hooks'         => array(),
            'models'        => 0,
            'theme'         => 0,
            'sms'           => 0,
            'ckeditor'      => 0,
        );

        // Dossiers de données
        $this->dirs = array();

        // Dictionnaires (initialisé vide pour éviter count() sur null)
        $this->dictionaries = array(
            'tabname'   => array(),
            'tablib'    => array(),
            'condition' => array(),
        );

        // Dépendances
        $this->depends      = array();
        $this->requiredby   = array();
        $this->conflictwith = array();
        $this->langfiles    = array('marketplace_bdc@marketplace_bdc');

        // Page de configuration
        $this->config_page_url = array('setup.php@marketplace_bdc');

        // Constantes, boites, crons
        $this->const    = array();
        $this->boxes    = array();
        $this->cronjobs = array();

        // Droits et menus (définis dans init())
        $this->rights  = array();
        $this->menu    = array();

        // Onglet sur la fiche produit
        $this->tabs = array(
            'product:+marketplaces:Marketplaces:marketplace_bdc@marketplace_bdc:$user->hasRight("marketplace_bdc","marketplace","read"):/custom/marketplace_bdc/marketplace/product_tab.php?id=__ID__',
        );

        // Triggers
        $this->triggers = array();
    }

    /**
     * Activation du module
     *
     * @param  string $options Options
     * @return int             1 si OK, 0 si erreur
     */
    public function init($options = '')
    {
        $this->rights = array();
        $r = 0;

        // Lecture
        $this->rights[$r][0] = $this->numero.'01';
        $this->rights[$r][1] = 'Lire les offres marketplace';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'read';
        $r++;

        // Ecriture
        $this->rights[$r][0] = $this->numero.'02';
        $this->rights[$r][1] = 'Créer/Modifier les offres marketplace';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'write';
        $r++;

        // Synchronisation
        $this->rights[$r][0] = $this->numero.'03';
        $this->rights[$r][1] = 'Synchroniser vers les marketplaces';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'sync';
        $r++;

        // Administration
        $this->rights[$r][0] = $this->numero.'04';
        $this->rights[$r][1] = 'Administrer la configuration marketplace';
        $this->rights[$r][4] = 'marketplace';
        $this->rights[$r][5] = 'admin';
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=marketplace_bdc',
            'type'     => 'left',
            'titre'    => 'Dashboard',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'dashboard',
            'url'      => '/custom/marketplace_bdc/marketplace/dashboard.php',
            'langs'    => 'marketplace_bdc@marketplace_bdc',
            'position' => 100,
            'enabled'  => 'isModEnabled("marketplace_bdc")',
            'perms'    => '$user->hasRight("marketplace_bdc","marketplace","read")',
            'target'   => '',
            'user'     => 2,
        );

        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=marketplace_bdc',
            'type'     => 'left',
            'titre'    => 'Commandes',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'orders',
            'url'      => '/custom/marketplace_bdc/marketplace/orders.php',
            'langs'    => 'marketplace_bdc@marketplace_bdc',
            'position' => 110,
            'enabled'  => 'isModEnabled("marketplace_bdc")',
            'perms'    => '$user->hasRight("marketplace_bdc","marketplace","read")',
            'target'   => '',
            'user'     => 2,
        );

        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=marketplace_bdc',
            'type'     => 'left',
            'titre'    => 'Configuration',
            'mainmenu' => 'marketplace_bdc',
            'leftmenu' => 'config',
            'url'      => '/custom/marketplace_bdc/admin/setup.php',
            'langs'    => 'marketplace_bdc@marketplace_bdc',
            'position' => 120,
            'enabled'  => 'isModEnabled("marketplace_bdc")',
            'perms'    => '$user->hasRight("marketplace_bdc","marketplace","admin")',
            'target'   => '',
            'user'     => 2,
        );

        return $this->_init(array(), $options);
    }

    /**
     * Désactivation du module
     *
     * @param  string $options Options
     * @return int             1 si OK, 0 si erreur
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
