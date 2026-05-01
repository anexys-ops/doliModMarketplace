<?php
/**
 * Version Manager Class
 * 
 * Manages module version, git info, and build metadata
 */

class VersionManager
{
    const VERSION = '1.2.0';
    const VERSION_DATE = '2026-05-01';
    const MINIMUM_PHP = '8.1';
    const MINIMUM_DOLIBARR = '17.0';
    
    private $module_dir;
    
    public function __construct($module_dir = null)
    {
        $this->module_dir = $module_dir ?: dirname(__FILE__) . '/../..';
    }
    
    /**
     * Get version info
     */
    public function getVersionInfo()
    {
        return array(
            'version' => self::VERSION,
            'version_date' => self::VERSION_DATE,
            'php_min' => self::MINIMUM_PHP,
            'dolibarr_min' => self::MINIMUM_DOLIBARR,
            'php_current' => phpversion(),
            'timestamp' => time()
        );
    }
    
    /**
     * Get Git info
     */
    public function getGitInfo()
    {
        $info = array(
            'branch' => 'Unknown',
            'commit' => 'Unknown',
            'commit_short' => 'Unknown',
            'commit_date' => 'Unknown',
            'commit_author' => 'Unknown',
            'remote' => 'Unknown',
            'tags' => array()
        );
        
        $git_dir = $this->module_dir . '/.git';
        
        if (!is_dir($git_dir)) {
            return $info;
        }
        
        // Get current branch
        $head_file = $git_dir . '/HEAD';
        if (file_exists($head_file)) {
            $head = trim(file_get_contents($head_file));
            $branch = str_replace('ref: refs/heads/', '', $head);
            $info['branch'] = $branch;
        }
        
        // Get current commit
        $current_dir = getcwd();
        chdir($this->module_dir);
        
        // Try git commands
        if (shell_exec('which git') !== null) {
            // Current commit
            $commit = trim(shell_exec('git rev-parse HEAD 2>/dev/null') ?: '');
            if ($commit) {
                $info['commit'] = $commit;
                $info['commit_short'] = substr($commit, 0, 7);
                
                // Commit date
                $date = trim(shell_exec('git log -1 --format=%ai 2>/dev/null') ?: '');
                if ($date) {
                    $info['commit_date'] = $date;
                }
                
                // Commit author
                $author = trim(shell_exec('git log -1 --format=%an 2>/dev/null') ?: '');
                if ($author) {
                    $info['commit_author'] = $author;
                }
            }
            
            // Remote
            $remote = trim(shell_exec('git config --get remote.origin.url 2>/dev/null') ?: '');
            if ($remote) {
                $info['remote'] = $remote;
            }
            
            // Tags
            $tags_output = trim(shell_exec('git tag -l 2>/dev/null') ?: '');
            if ($tags_output) {
                $info['tags'] = explode("\n", $tags_output);
            }
        }
        
        chdir($current_dir);
        return $info;
    }
    
    /**
     * Get full build info
     */
    public function getBuildInfo()
    {
        return array(
            'version' => $this->getVersionInfo(),
            'git' => $this->getGitInfo(),
            'build_date' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'uname' => php_uname()
        );
    }
    
    /**
     * Get version string (for display)
     */
    public function getVersionString()
    {
        $version = self::VERSION;
        $git = $this->getGitInfo();
        
        if ($git['commit_short'] !== 'Unknown') {
            $version .= " ({$git['commit_short']})";
        }
        
        if ($git['branch'] !== 'Unknown') {
            $version .= " [{$git['branch']}]";
        }
        
        return $version;
    }
    
    /**
     * Get release notes for current version
     */
    public function getReleaseNotes()
    {
        return array(
            '1.2.0' => array(
                'date' => '2026-05-01',
                'features' => array(
                    'Phase 5: Field Mapping & Extrafields',
                    'Full Extrafield support for Products/Categories/Orders',
                    'Automatic Extrafield detection'
                ),
                'improvements' => array(
                    'Advanced configuration system',
                    'Cron job management',
                    'DEV/PROD connection tests',
                    'Comprehensive logging'
                )
            ),
            '1.0.0' => array(
                'date' => '2026-05-01',
                'features' => array(
                    'Core module implementation',
                    'Multi-marketplace support (ADEO, Cdiscount, Amazon, WooCommerce)',
                    'Product tab integration',
                    'Order management system'
                )
            )
        );
    }
}
