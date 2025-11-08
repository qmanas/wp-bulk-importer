<?php
/**
 * Plugin Name: WB Product Importer
 * Plugin URI: https://mill2mall.com/
 * Description: Handles product imports for Mill2Mall WooCommerce store
 * Version: 0.7.33
 * Author: Your Name
 * Author URI: https://mill2mall.com/
 * Text Domain: wb-importer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 *
 * @package WB_Product_Importer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/wb-debug.log');

error_log('WB Plugin: Starting initialization');

// Load environment variables
$env_file = __DIR__ . '/.env';
$version = '0.7.33'; // Default version if .env is not found

if (file_exists($env_file)) {
    $env_vars = parse_ini_file($env_file);
    if (isset($env_vars['VERSION'])) {
        $version = $env_vars['VERSION'];
    }
}

// Define plugin constants
define('WB_PRODUCT_IMPORTER_VERSION', $version);
define('WB_PRODUCT_IMPORTER_FILE', __FILE__);

error_log('WB Plugin: Before defining paths');

try {
    if (!function_exists('plugin_dir_path')) {
        error_log('WB Plugin: plugin_dir_path function does not exist!');
    } else {
        error_log('WB Plugin: plugin_dir_path exists');
    }
    
    define('WB_PRODUCT_IMPORTER_PATH', trailingslashit(plugin_dir_path(__FILE__)));
    define('WB_PRODUCT_IMPORTER_URL', trailingslashit(plugin_dir_url(__FILE__)));
    define('WB_PRODUCT_IMPORTER_BASENAME', plugin_basename(__FILE__));
    
    error_log('WB Plugin: Paths defined successfully');
    error_log('WB Plugin: Path: ' . WB_PRODUCT_IMPORTER_PATH);
    error_log('WB Plugin: URL: ' . WB_PRODUCT_IMPORTER_URL);
} catch (Exception $e) {
    error_log('WB Plugin: Error defining paths: ' . $e->getMessage());
}

// Check if WordPress is loaded
if (!function_exists('add_action')) {
    error_log('WB Plugin: WordPress is not loaded!');
    return;
}

error_log('WB Plugin: WordPress is loaded, checking WooCommerce');

// Make sure WooCommerce is active
$active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
error_log('WB Plugin: Active plugins: ' . print_r($active_plugins, true));

if (!in_array('woocommerce/woocommerce.php', $active_plugins)) {
    error_log('WB Plugin: WooCommerce is not active');
    add_action('admin_notices', 'wb_missing_woocommerce_notice');
    return;
}

error_log('WB Plugin: WooCommerce is active');

/**
 * Display WooCommerce missing notice.
 */
function wb_missing_woocommerce_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('WB Product Importer requires WooCommerce to be installed and active.', 'wb-importer'); ?></p>
    </div>
    <?php
}

// Include required files
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/class-wb-product-importer.php';
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/class-wb-image-tracker.php';
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/class-wb-ajax-handlers.php';

// Initialize the plugin
function wb_product_importer_init() {
    // Check if WooCommerce is active and user has proper permissions
    if (!class_exists('WooCommerce') || !current_user_can('manage_options')) {
        return;
    }
    
    // Initialize the plugin
    $wb_importer = new WB_Product_Importer();
    
    // Initialize AJAX handlers in admin
    if (is_admin()) {
        new WB_Ajax_Handlers($wb_importer);
    }
}

/**
 * Create database tables on plugin activation
 */
function wb_plugin_activation() {
    // Create image tracking table
    WB_Image_Tracker::create_table();
    
    // Create uploads directory if it doesn't exist
    $upload_dir = wp_upload_dir();
    $wb_upload_dir = $upload_dir['basedir'] . '/wb-imports';
    
    if (!file_exists($wb_upload_dir)) {
        wp_mkdir_p($wb_upload_dir);
    }
    
    // Schedule cleanup of orphaned images (runs daily)
    if (!wp_next_scheduled('wb_cleanup_orphaned_images')) {
        wp_schedule_event(time(), 'daily', 'wb_cleanup_orphaned_images');
    }
}
register_activation_hook(__FILE__, 'wb_plugin_activation');

/**
 * Clean up on plugin deactivation
 */
function wb_plugin_deactivation() {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('wb_cleanup_orphaned_images');
}
register_deactivation_hook(__FILE__, 'wb_plugin_deactivation');

/**
 * Load plugin textdomain.
 */
function wb_load_textdomain() {
    load_plugin_textdomain('wb-importer', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Load text domain on init
add_action('init', 'wb_load_textdomain');

// Start the plugin
add_action('plugins_loaded', 'wb_product_importer_init');