-- ============================================================
-- Table: llx_modmkp_mapping
-- 
-- Stores field mappings between Dolibarr and Marketplaces
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_mapping (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_marketplace INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL COMMENT 'product, category, order',
    config JSON,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_marketplace_entity (fk_marketplace, entity_type),
    INDEX idx_marketplace (fk_marketplace)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Table: llx_modmkp_mapping_fields
-- 
-- Individual field mappings with extrafield support
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_mapping_fields (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_mapping INT NOT NULL,
    dolibarr_field VARCHAR(100) NOT NULL COMMENT 'Dolibarr field name',
    marketplace_field VARCHAR(100) NOT NULL COMMENT 'Marketplace field name',
    is_extrafield TINYINT(1) DEFAULT 0 COMMENT '0=standard, 1=extrafield',
    is_required TINYINT(1) DEFAULT 0 COMMENT '0=optional, 1=required',
    transformation VARCHAR(255) COMMENT 'Transformation function (e.g., multiply_100, uppercase)',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_mapping_field (fk_mapping, dolibarr_field, marketplace_field),
    INDEX idx_mapping (fk_mapping),
    CONSTRAINT fk_mapping FOREIGN KEY (fk_mapping) 
        REFERENCES llx_modmkp_mapping(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Table: llx_modmkp_mapping_history
-- 
-- Track mapping changes for audit
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_mapping_history (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_mapping INT NOT NULL,
    action VARCHAR(50) COMMENT 'created, updated, deleted',
    old_value JSON,
    new_value JSON,
    user_id INT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_mapping (fk_mapping),
    INDEX idx_date (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
