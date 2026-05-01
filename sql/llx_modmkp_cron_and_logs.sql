-- ============================================================
-- Table: llx_modmkp_synclog
-- 
-- Stores marketplace synchronization logs
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_synclog (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fk_marketplace INT NOT NULL,
    fk_offer INT,
    type VARCHAR(50) NOT NULL COMMENT 'test, sync, import, export, error',
    status VARCHAR(20) NOT NULL COMMENT 'ok, error, warning, pending',
    message TEXT,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_marketplace (fk_marketplace),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_date (date_created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Table: llx_modmkp_cron
-- 
-- Cron job configuration
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_cron (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    type VARCHAR(50) NOT NULL COMMENT 'sync_offers, sync_stock, fetch_orders, fetch_returns',
    enabled TINYINT(1) DEFAULT 1,
    frequency VARCHAR(50) COMMENT 'hourly, daily, weekly, monthly',
    hour INT COMMENT 'Hour of day (0-23)',
    day_of_week INT COMMENT 'Day of week (0-6, Sunday=0)',
    day_of_month INT COMMENT 'Day of month (1-31)',
    last_execution DATETIME,
    next_execution DATETIME,
    status VARCHAR(20) COMMENT 'pending, running, completed, failed',
    
    INDEX idx_enabled (enabled),
    INDEX idx_type (type),
    INDEX idx_next_execution (next_execution)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Table: llx_modmkp_config
-- 
-- Module configuration
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_modmkp_config (
    rowid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    type VARCHAR(50) COMMENT 'string, int, json, bool',
    description TEXT,
    date_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Insert default config
INSERT IGNORE INTO llx_modmkp_config (key_name, value, type, description) VALUES
('log_retention_days', '30', 'int', 'Number of days to keep logs'),
('enable_dev_mode', '0', 'bool', 'Enable development mode testing'),
('auto_retry_failed', '1', 'bool', 'Automatically retry failed syncs'),
('retry_attempts', '3', 'int', 'Number of retry attempts');
