<?php
/**
 * Mapping Manager Class
 * 
 * Manages field mappings between Dolibarr and Marketplaces
 * Handles Products, Categories, Orders with Extrafields support
 */

class MappingManager
{
    private $db;
    private $mapping_table;
    private $fields_table;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->mapping_table = MAIN_DB_PREFIX . 'modmkp_mapping';
        $this->fields_table = MAIN_DB_PREFIX . 'modmkp_mapping_fields';
    }
    
    /**
     * Save a mapping
     */
    public function saveMapping($marketplace_id, $entity_type, $mapping_config)
    {
        $entity_type = $this->db->escape($entity_type); // product, category, order
        $config_json = json_encode($mapping_config);
        
        // Check if exists
        $sql = "SELECT rowid FROM " . $this->mapping_table . "
                WHERE fk_marketplace = " . intval($marketplace_id) . "
                AND entity_type = '" . $entity_type . "'";
        $result = $this->db->query($sql);
        
        if ($this->db->num_rows($result) > 0) {
            // Update
            $sql = "UPDATE " . $this->mapping_table . "
                    SET config = '" . $this->db->escape($config_json) . "',
                        date_updated = NOW()
                    WHERE fk_marketplace = " . intval($marketplace_id) . "
                    AND entity_type = '" . $entity_type . "'";
        } else {
            // Insert
            $sql = "INSERT INTO " . $this->mapping_table . "
                    (fk_marketplace, entity_type, config)
                    VALUES (" . intval($marketplace_id) . ", '" . $entity_type . "', '" . $this->db->escape($config_json) . "')";
        }
        
        return $this->db->query($sql);
    }
    
    /**
     * Get mapping
     */
    public function getMapping($marketplace_id, $entity_type)
    {
        $sql = "SELECT * FROM " . $this->mapping_table . "
                WHERE fk_marketplace = " . intval($marketplace_id) . "
                AND entity_type = '" . $this->db->escape($entity_type) . "'";
        
        $result = $this->db->query($sql);
        $row = $this->db->fetch_object($result);
        
        if ($row) {
            return json_decode($row->config, true);
        }
        
        return array();
    }
    
    /**
     * Save field mapping
     */
    public function saveFieldMapping($mapping_id, $dolibarr_field, $marketplace_field, $is_extrafield = 0)
    {
        $sql = "INSERT INTO " . $this->fields_table . "
                (fk_mapping, dolibarr_field, marketplace_field, is_extrafield)
                VALUES (" . intval($mapping_id) . ",
                        '" . $this->db->escape($dolibarr_field) . "',
                        '" . $this->db->escape($marketplace_field) . "',
                        " . intval($is_extrafield) . ")
                ON DUPLICATE KEY UPDATE
                marketplace_field = '" . $this->db->escape($marketplace_field) . "'";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get field mappings
     */
    public function getFieldMappings($mapping_id)
    {
        $sql = "SELECT * FROM " . $this->fields_table . "
                WHERE fk_mapping = " . intval($mapping_id);
        
        $result = $this->db->query($sql);
        $mappings = array();
        
        while ($row = $this->db->fetch_object($result)) {
            $mappings[] = $row;
        }
        
        return $mappings;
    }
    
    /**
     * Get Dolibarr extrafields by entity
     */
    public function getExtrafields($entity_type)
    {
        // $entity_type: 'product', 'category', 'commande', etc
        $sql = "SELECT attrname, label, type FROM " . MAIN_DB_PREFIX . "extrafields
                WHERE elementtype = '" . $this->db->escape($entity_type) . "'
                AND active = 1
                ORDER BY pos ASC";
        
        $result = $this->db->query($sql);
        $extrafields = array();
        
        while ($row = $this->db->fetch_object($result)) {
            $extrafields[] = $row;
        }
        
        return $extrafields;
    }
    
    /**
     * Get standard fields for entity
     */
    public function getStandardFields($entity_type)
    {
        $fields = array();
        
        switch ($entity_type) {
            case 'product':
                $fields = array(
                    'ref' => 'SKU/Reference',
                    'label' => 'Product Label',
                    'description' => 'Description',
                    'price' => 'Price',
                    'price_ttc' => 'Price TTC',
                    'tva_tx' => 'VAT Rate',
                    'weight' => 'Weight',
                    'quantity' => 'Quantity/Stock',
                    'status' => 'Status (0/1)',
                    'barcode' => 'Barcode',
                    'cost_price' => 'Cost Price',
                    'fk_product_category' => 'Category ID'
                );
                break;
                
            case 'category':
                $fields = array(
                    'label' => 'Category Label',
                    'description' => 'Description',
                    'rowid' => 'Category ID'
                );
                break;
                
            case 'order':
                $fields = array(
                    'ref' => 'Order Reference',
                    'ref_client' => 'Client Reference',
                    'socname' => 'Company Name',
                    'firstname' => 'First Name',
                    'lastname' => 'Last Name',
                    'address' => 'Address',
                    'zip' => 'ZIP Code',
                    'city' => 'City',
                    'country' => 'Country',
                    'email' => 'Email',
                    'phone' => 'Phone',
                    'total_ht' => 'Total HT',
                    'total_ttc' => 'Total TTC',
                    'total_tva' => 'Total VAT',
                    'date' => 'Order Date',
                    'date_livraison' => 'Delivery Date'
                );
                break;
        }
        
        return $fields;
    }
}
