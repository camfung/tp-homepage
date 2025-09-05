<?php
/**
 * Plugin Name: Traffic Portal Link Shortener
 * Plugin URI: https://trafficportal.dev
 * Description: WordPress shortcode integration with Traffic Portal API for creating and managing short links
 * Version: 1.0.0
 * Author: Traffic Portal Team
 * Author URI: https://trafficportal.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: traffic-portal-link-shortener
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TPLS_VERSION', '1.0.0');
define('TPLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TPLS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TPLS_API_BASE_URL', 'https://dev.trfc.link');

/**
 * Main plugin class
 */
class Traffic_Portal_Link_Shortener {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Admin handler instance
     */
    private $admin = null;
    
    /**
     * Shortcode handler instance
     */
    private $shortcode = null;
    
    /**
     * API proxy handler instance
     */
    private $api = null;
    
    /**
     * Assets manager instance
     */
    private $assets = null;
    
    /**
     * Database handler instance
     */
    private $database = null;
    
    /**
     * Get single instance of the class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load database handler first
        require_once TPLS_PLUGIN_DIR . 'includes/class-traffic-portal-database.php';
        $this->database = new Traffic_Portal_Database();
        
        // Load assets manager
        require_once TPLS_PLUGIN_DIR . 'includes/class-traffic-portal-assets.php';
        $this->assets = new Traffic_Portal_Assets();
        
        // Load API proxy
        require_once TPLS_PLUGIN_DIR . 'includes/class-traffic-portal-api.php';
        $this->api = new Traffic_Portal_API();
        
        // Load shortcode handler
        require_once TPLS_PLUGIN_DIR . 'includes/class-traffic-portal-shortcode.php';
        $this->shortcode = new Traffic_Portal_Shortcode($this->assets, $this->api);
        
        // Load admin interface if in admin
        if (is_admin()) {
            require_once TPLS_PLUGIN_DIR . 'includes/class-traffic-portal-admin.php';
            $this->admin = new Traffic_Portal_Admin($this->database, $this->api);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check requirements first
        $this->check_requirements();
        
        // Create database tables
        if ($this->database) {
            $this->database->create_tables();
        }
        
        // Set default options
        add_option('tpls_version', TPLS_VERSION);
        add_option('tpls_installed', time());
        
        // Clear any existing rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'traffic-portal-link-shortener',
            false,
            dirname(TPLS_PLUGIN_BASENAME) . '/languages/'
        );
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        $this->check_requirements();
    }
    
    /**
     * Check plugin requirements
     */
    private function check_requirements() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.2', '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Traffic Portal Link Shortener requires PHP 8.2 or higher.', 'traffic-portal-link-shortener'); ?></p>
        </div>
        <?php
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Traffic Portal Link Shortener requires WordPress 6.0 or higher.', 'traffic-portal-link-shortener'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Get plugin version
     */
    public function get_version(): string {
        return TPLS_VERSION;
    }
    
    /**
     * Get API base URL
     */
    public function get_api_base_url(): string {
        return TPLS_API_BASE_URL;
    }
}

// Initialize the plugin
if (!function_exists('tpls_init')) {
    function tpls_init() {
        return Traffic_Portal_Link_Shortener::get_instance();
    }
}

// Start the plugin
tpls_init();