<?php
/**
 * Log Manager Class
 * 
 * Manages marketplace sync logs with retention policy
 */

class MarketplaceLogManager
{
    private $db;
    private $table;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->table = MAIN_DB_PREFIX . 'modmkp_synclog';
    }
    
    /**
     * Log a marketplace sync event
     */
    public function log($marketplace_id, $type, $message, $status = 'ok', $offer_id = null)
    {
        $sql = "INSERT INTO " . $this->table . "
                (fk_marketplace, fk_offer, type, message, status, date_created)
                VALUES (
                    " . intval($marketplace_id) . ",
                    " . ($offer_id ? intval($offer_id) : 'NULL') . ",
                    '" . $this->db->escape($type) . "',
                    '" . $this->db->escape($message) . "',
                    '" . $this->db->escape($status) . "',
                    NOW()
                )";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get logs with filters
     */
    public function getLogs($filters = array(), $limit = 100, $offset = 0)
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($filters['marketplace_id'])) {
            $sql .= " AND fk_marketplace = " . intval($filters['marketplace_id']);
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = '" . $this->db->escape($filters['status']) . "'";
        }
        
        if (!empty($filters['type'])) {
            $sql .= " AND type = '" . $this->db->escape($filters['type']) . "'";
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND date_created >= '" . $this->db->escape($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND date_created <= '" . $this->db->escape($filters['date_to']) . "'";
        }
        
        $sql .= " ORDER BY date_created DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        
        $result = $this->db->query($sql);
        $logs = array();
        
        while ($row = $this->db->fetch_object($result)) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get logs count
     */
    public function getLogsCount($filters = array())
    {
        $sql = "SELECT COUNT(*) as cnt FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($filters['marketplace_id'])) {
            $sql .= " AND fk_marketplace = " . intval($filters['marketplace_id']);
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = '" . $this->db->escape($filters['status']) . "'";
        }
        
        $result = $this->db->query($sql);
        $row = $this->db->fetch_object($result);
        
        return $row->cnt;
    }
    
    /**
     * Get stats
     */
    public function getStats($days = 7)
    {
        $sql = "SELECT 
                    status,
                    COUNT(*) as cnt,
                    DATE(date_created) as date
                FROM " . $this->table . "
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL " . intval($days) . " DAY)
                GROUP BY status, DATE(date_created)
                ORDER BY date DESC";
        
        $result = $this->db->query($sql);
        $stats = array();
        
        while ($row = $this->db->fetch_object($result)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    /**
     * Purge old logs based on retention
     */
    public function purgeOldLogs($retention_days = 30)
    {
        $sql = "DELETE FROM " . $this->table . "
                WHERE date_created < DATE_SUB(NOW(), INTERVAL " . intval($retention_days) . " DAY)";
        
        return $this->db->query($sql);
    }
    
    /**
     * Export logs to CSV
     */
    public function exportToCSV($filters = array())
    {
        $logs = $this->getLogs($filters, 10000, 0);
        
        $csv = "Date,Marketplace,Type,Status,Message\n";
        
        foreach ($logs as $log) {
            $date = date('Y-m-d H:i:s', strtotime($log->date_created));
            $csv .= "\"" . $date . "\",";
            $csv .= "\"" . $log->fk_marketplace . "\",";
            $csv .= "\"" . $log->type . "\",";
            $csv .= "\"" . $log->status . "\",";
            $csv .= "\"" . addslashes($log->message) . "\"\n";
        }
        
        return $csv;
    }
}
