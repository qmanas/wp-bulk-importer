<?php
/**
 * WB Product Importer
 *
 * A class to handle product imports for WB
 *
 * @package WB_Product_Importer
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Include trait files
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/traits/class-wb-product-importer-assets.php';
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/traits/class-wb-product-importer-import.php';
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/traits/class-wb-product-importer-admin.php';
require_once WB_PRODUCT_IMPORTER_PATH . 'includes/traits/class-wb-product-importer-acf.php';

/**
 * WB Product Importer class
 */
class WB_Product_Importer
{
    // Use traits for better code organization
    use WB_Product_Importer_Assets;
    use WB_Product_Importer_Import;
    use WB_Product_Importer_Admin;
    use WB_Product_Importer_ACF;

    /**
     * Base path for WB product images
     *
     * @var string
     */
    const WB_IMAGE_BASE_PATH = WP_CONTENT_DIR . '/uploads/wb-knitted-products/';

    /**
     * Singleton instance
     *
     * @var WB_Product_Importer
     */
    private static $instance = null;

    /**
     * Debug log
     *
     * @var array
     */
    private $debug_log = [];

    /**
     * Image tracking table name
     *
     * @var string
     */
    private $image_tracking_table;

    /**
     * Get the singleton instance
     *
     * @return WB_Product_Importer Instance of the class
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->image_tracking_table = $wpdb->prefix . 'wb_image_tracking';

        // Initialize admin hooks if we're in the admin area
        if (is_admin()) {
            $this->init_hooks();
        }

        // Create data directory if it doesn't exist
        $data_dir = WB_PRODUCT_IMPORTER_PATH . 'data';
        if (!file_exists($data_dir)) {
            wp_mkdir_p($data_dir);
        }
    }

    // Hooks are now in the WB_Product_Importer_Admin trait

    /**
     * Enqueue admin styles and scripts
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('toplevel_page_wb-helper' !== $hook) {
            return;
        }

        // Enqueue WordPress media and components
        wp_enqueue_media();
        wp_enqueue_style('wp-components');

        // Enqueue admin styles
        wp_enqueue_style(
            'wb-admin-styles',
            WB_PRODUCT_IMPORTER_URL . 'assets/css/admin.css',
            array('wp-components'),
            WB_PRODUCT_IMPORTER_VERSION
        );

        // Add inline styles for dynamic elements
        $custom_css = '
        .wb-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            margin: 20px 0;
            border-radius: 4px;
            overflow: hidden;
        }
        .wb-card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #ccd0d4;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .wb-card-header h2 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #1d2327;
        }
        .wb-card-body {
            padding: 20px;
        }
        .wb-import-form {
            margin-bottom: 2rem;
        }
        .wb-import-form:last-child {
            margin-bottom: 0;
        }
        .wb-log-area {
            width: 100%;
            min-height: 300px;
            font-family: monospace;
            background: #1e1e1e;
            color: #e0e0e0;
            padding: 15px;
            border: none;
            border-radius: 0 0 4px 4px;
            line-height: 1.5;
            white-space: pre;
            overflow-x: auto;
        }
        .wb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            line-height: 1.5;
        }
        .wb-btn-primary {
            background: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        .wb-btn-primary:hover {
            background: #135e96;
            border-color: #135e96;
        }
        .wb-btn-secondary {
            background: #f0f0f1;
            color: #1d2327;
            border-color: #dcdcde;
        }
        .wb-btn-secondary:hover {
            background: #dcdcde;
            border-color: #c3c4c7;
        }
        .wb-form-group {
            margin-bottom: 1.5rem;
        }
        .wb-form-group:last-child {
            margin-bottom: 0;
        }
        .wb-form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .wb-form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background: #fff;
            color: #2c3338;
        }
        .wb-form-control:focus {
            border-color: #2271b1;
            box-shadow: 0 0 0 1px #2271b1;
            outline: 2px solid transparent;
        }
        .wb-checkbox {
            margin-right: 8px;
        }
        .wb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .wb-spinner {
            animation: wb-spin 1s linear infinite;
            margin-right: 8px;
        }
        @keyframes wb-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .wb-alert {
            padding: 12px 16px;
            margin: 1rem 0;
            border-radius: 4px;
            border-left: 4px solid #72aee6;
        }
        .wb-alert-success {
            background-color: #edfaef;
            border-left-color: #00a32a;
        }
        .wb-alert-warning {
            background-color: #fff8e5;
            border-left-color: #dba617;
        }
        .wb-alert-error {
            background-color: #fcf0f1;
            border-left-color: #d63638;
        }
        .wb-tabs {
            display: flex;
            border-bottom: 1px solid #ccd0d4;
            margin: 0 -20px 20px;
            padding: 0 20px;
        }
        .wb-tab {
            padding: 10px 16px;
            margin-right: 4px;
            border: 1px solid transparent;
            border-bottom: none;
            background: none;
            cursor: pointer;
            border-radius: 4px 4px 0 0;
            font-weight: 500;
            color: #646970;
        }
        .wb-tab-active {
            background: #fff;
            border-color: #ccd0d4;
            border-bottom-color: #fff;
            color: #1d2327;
            margin-bottom: -1px;
        }
        .wb-tab-content {
            display: none;
        }
        .wb-tab-content-active {
            display: block;
        }
        .wb-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        .wb-header {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        .wb-version {
            margin-left: 10px;
            color: #646970;
            font-size: 0.9em;
        }
        .wb-card-wide {
            grid-column: 1 / -1;
        }';

        wp_add_inline_style('wb-admin-styles', $custom_css);

        // Enqueue admin scripts
        wp_enqueue_script(
            'wb-admin-scripts',
            WB_PRODUCT_IMPORTER_URL . 'assets/js/admin.js',
            array('jquery', 'wp-element', 'wp-components', 'wp-api-fetch'),
            WB_PRODUCT_IMPORTER_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        wp_localize_script('wb-admin-scripts', 'wbAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wb_admin_nonce'),
            'restUrl' => esc_url_raw(rest_url('wb/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'i18n' => array(
                'confirmImport' => __('Are you sure you want to import products? This might take a while for large directories.', 'wb-importer'),
                'importing' => __('Importing...', 'wb-importer'),
                'importComplete' => __('Import completed!', 'wb-importer'),
                'error' => __('An error occurred. Please try again.', 'wb-importer'),
                'knittedProducts' => __('Knitted Products', 'wb-importer'),
                'wovenProducts' => __('Woven Products', 'wb-importer'),
                'dryRun' => __('Dry Run (simulate only)', 'wb-importer'),
                'import' => __('Import', 'wb-importer'),
                'generateJson' => __('Generate & Download JSON', 'wb-importer'),
                'debugLog' => __('Debug Log', 'wb-importer'),
                'clearLog' => __('Clear Log', 'wb-importer'),
                'summary' => __('Summary', 'wb-importer'),
                'tools' => __('Tools', 'wb-importer'),
                'settings' => __('Settings', 'wb-importer')
            )
        ));
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('admin_init', [$this, 'handle_import_request']);
        add_action('admin_menu', [$this, 'add_import_buttons_page']);
        add_action('admin_menu', [$this, 'add_acf_manager_page']);
        add_action('admin_post_wb_import_knitted', [$this, 'handle_knitted_import']);
        add_action('admin_post_wb_import_woven', [$this, 'handle_woven_import']);
        add_action('admin_post_wb_generate_structure', [$this, 'handle_generate_structure']);
        add_action('wp_dashboard_setup', [$this, 'register_image_count_widget']);

        // Add AJAX handler for image lookup
        add_action('wp_ajax_wb_lookup_image', [$this, 'ajax_lookup_image']);

        // Add AJAX handlers for ACF management
        add_action('wp_ajax_wb_save_acf_data', [$this, 'ajax_save_acf_data']);
        add_action('wp_ajax_wb_get_all_acf_data', [$this, 'ajax_get_all_acf_data']);

        // Add AJAX handler for price update
        add_action('wp_ajax_wb_save_price', [$this, 'ajax_save_price']);
    }

    /**
     * Track a new image and its association with a product/variation
     * 
     * @param string $image_path Full server path to the image
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id WooCommerce variation ID (0 for main product image)
     * @param bool $is_featured Whether this is a featured image
     * @return int|false The inserted/updated record ID or false on failure
     */
    public function track_image($image_path, $product_id, $variation_id = 0, $is_featured = false)
    {
        global $wpdb;

        $original_filename = basename($image_path);
        $relative_path = str_replace(ABSPATH, '', $image_path);

        // Check if this image is already tracked
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, product_id, variation_id FROM {$this->image_tracking_table} WHERE image_path = %s",
            $relative_path
        ));

        if ($existing) {
            // Update existing record if product/variation changed
            if ($existing->product_id != $product_id || $existing->variation_id != $variation_id) {
                $wpdb->update(
                    $this->image_tracking_table,
                    array(
                        'product_id' => $product_id,
                        'variation_id' => $variation_id,
                        'is_featured' => $is_featured ? 1 : 0,
                        'last_updated' => current_time('mysql')
                    ),
                    array('id' => $existing->id),
                    array('%d', '%d', '%d', '%s'),
                    array('%d')
                );
            }
            return $existing->id;
        }

        // Insert new record
        $result = $wpdb->insert(
            $this->image_tracking_table,
            array(
                'image_path' => $relative_path,
                'original_filename' => $original_filename,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'is_featured' => $is_featured ? 1 : 0,
                'date_added' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%d', '%d', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Find a product by its associated image
     * 
     * @param string $image_path Full server path to the image
     * @return object|false Database row with product info or false if not found
     */
    public function find_product_by_image($image_path)
    {
        global $wpdb;

        $relative_path = str_replace(ABSPATH, '', $image_path);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->image_tracking_table} WHERE image_path = %s",
            $relative_path
        ));
    }

    /**
     * Get all images for a product or variation
     * 
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id Optional variation ID to filter by
     * @return array Array of image records
     */
    public function get_product_images($product_id, $variation_id = 0)
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->image_tracking_table} WHERE product_id = %d",
            $product_id
        );

        if ($variation_id) {
            $query .= $wpdb->prepare(" AND variation_id = %d", $variation_id);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Handle AJAX request to look up an image's associated product
     */
    public function ajax_lookup_image()
    {
        check_ajax_referer('wb_image_lookup', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }

        $image_url = isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : '';

        if (empty($image_url)) {
            wp_send_json_error('Image URL is required');
        }

        $upload_dir = wp_upload_dir();
        $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);

        $image_info = $this->find_product_by_image($image_path);

        if ($image_info) {
            $product = wc_get_product($image_info->product_id);
            if ($product) {
                wp_send_json_success(array(
                    'product_id' => $product->get_id(),
                    'product_name' => $product->get_name(),
                    'product_edit_url' => get_edit_post_link($product->get_id(), 'url'),
                    'is_variation' => $image_info->variation_id > 0,
                    'is_featured' => (bool) $image_info->is_featured,
                    'date_added' => $image_info->date_added
                ));
            }
        }

        wp_send_json_error('No product found for this image');
    }

    /**
     * Add import buttons page to admin menu
     */
    public function add_import_buttons_page()
    {
        add_menu_page(
            'WB Helper',
            'WB Helper',
            'manage_woocommerce',
            'wb-helper',
            [$this, 'render_import_buttons'],
            'dashicons-admin-tools',
            2
        );
    }

    /**
     * Consolidate similar log entries with folder-based counts
     * 
     * @param array $logs Array of log messages
     * @return array Consolidated log messages with folder counts
     */
    /**
     * Consolidate similar log entries with folder-based counts
     * 
     * @param array $logs Array of log messages
     * @return array Consolidated log messages with folder counts
     */
    private function consolidate_logs($logs)
    {
        $folder_counts = [];
        $other_logs = [];
        $dry_run = false;

        // First pass: check if this is a dry run
        foreach ($logs as $log) {
            if (strpos($log, '[DRY RUN]') !== false) {
                $dry_run = true;
                break;
            }
        }

        foreach ($logs as $log) {
            $log = trim($log);
            if (empty($log)) {
                continue;
            }

            // Handle dry run prefix consistently
            $log_message = $dry_run ? str_replace('[DRY RUN] Would ', '', $log) : $log;

            // Extract folder from product creation/update logs
            if (
                strpos($log_message, 'create product for image:') !== false ||
                strpos($log_message, 'process variations for:') !== false ||
                strpos($log_message, 'Updated product:') !== false ||
                strpos($log_message, 'Created product:') !== false
            ) {

                // Extract the path part
                $parts = explode(':', $log_message, 2);
                if (count($parts) === 2) {
                    $path = trim($parts[1]);
                    // Get the folder name (second-to-last part of the path)
                    $segments = explode('/', $path);
                    if (count($segments) > 1) {
                        $folder = $segments[count($segments) - 2];
                        $folder_counts[$folder] = ($folder_counts[$folder] ?? 0) + 1;
                        continue;
                    }
                }
            }

            // Handle other log messages (errors, info, etc.)
            if (preg_match('/^\[([^\]]+)\](.*)/', $log_message, $matches)) {
                $prefix = '[' . trim($matches[1]) . ']';
                $message = trim($matches[2]);

                // Group similar messages
                $key = $prefix . ' ' . (strtok($message, ' ') ?: '');
                $other_logs[$key] = ($other_logs[$key] ?? 0) + 1;
            } else {
                $other_logs[$log_message] = ($other_logs[$log_message] ?? 0) + 1;
            }
        }

        // Format folder counts
        $result = [];
        if (!empty($folder_counts)) {
            // Calculate total products
            $total_products = array_sum($folder_counts);

            // Add total products count
            $result[] = "\n📦 " . ($dry_run ? '[DRY RUN] ' : '') . "Total Products: $total_products\n";

            // Add folder summaries
            ksort($folder_counts); // Sort folders alphabetically
            $folder_summary = [];
            foreach ($folder_counts as $folder => $count) {
                $folder_summary[] = "$folder ($count)";
            }

            // Split into chunks of 5 folders per line for better readability
            $chunked_folders = array_chunk($folder_summary, 5);
            foreach ($chunked_folders as $chunk) {
                $result[] = '📁 ' . implode(' | ', $chunk);
            }

            $result[] = "\n" . str_repeat('─', 60) . "\n";
        }

        // Format other logs with proper spacing
        if (!empty($other_logs)) {
            $result[] = "📋 " . ($dry_run ? '[DRY RUN] ' : '') . "Process Summary:";

            // Sort logs by type (errors first, then warnings, then info)
            $sorted_logs = [];
            $error_logs = [];
            $warning_logs = [];
            $info_logs = [];

            foreach ($other_logs as $log => $count) {
                if (stripos($log, '[ERROR]') !== false) {
                    $error_logs[$log] = $count;
                } elseif (stripos($log, '[WARNING]') !== false) {
                    $warning_logs[$log] = $count;
                } else {
                    $info_logs[$log] = $count;
                }
            }

            // Combine and sort logs
            $sorted_logs = $error_logs + $warning_logs + $info_logs;

            foreach ($sorted_logs as $log => $count) {
                $prefix = '';
                if (stripos($log, '[ERROR]') !== false) {
                    $prefix = '❌ ';
                } elseif (stripos($log, '[WARNING]') !== false) {
                    $prefix = '⚠️ ';
                } elseif (stripos($log, '[SUCCESS]') !== false) {
                    $prefix = '✅ ';
                } else {
                    $prefix = 'ℹ️ ';
                }

                $result[] = $prefix . ($count > 1 ? "$log (x$count)" : $log);
            }
        }

        return $result;
    }

    /**
     * Enqueue admin styles and scripts
                padding: 10px;
                box-sizing: border-box;
                resize: vertical;
            }
            .wb-scan-status { display: none; margin-left: 10px; }
            .wb-postbox-container { margin-top: 20px; }
            #wb-summary { margin-bottom: 20px; }
            #wb-summary .inside { padding: 15px; }
            #post-body-content { width: 100%; float: none; }
            .postbox { margin-bottom: 20px; }
            .postbox .inside { margin: 0; padding: 0; }
            .postbox .inside > * { padding: 12px; }
            .postbox .inside > *:first-child { padding-top: 12px; }
            .postbox .inside > *:last-child { margin-bottom: 0; }
        ');
    }

    /**
     * Add help tab for the import page
     */
    public function add_help_tab()
    {
        $screen = get_current_screen();

        if ('toplevel_page_wb-helper' !== $screen->id) {
            return;
        }

        $screen->add_help_tab(array(
            'id' => 'wb_import_help',
            'title' => __('Import Help', 'wb-importer'),
            'content' => '<p>' . __('Use the import tools to import products from your WB directories. The Dry Run option will simulate the import without making any changes.', 'wb-importer') . '</p>'
        ));
    }

    /**
     * Render import buttons page with modern UI
     */
    public function render_import_buttons()
    {
        // Add screen options and help tabs
        $screen = get_current_screen();

        // Enqueue scripts and styles
        $this->enqueue_admin_assets('toplevel_page_wb-helper');
        $this->add_help_tab();

        // Get any existing logs
        $logs = get_transient('wb_import_debug_log');
        $consolidated_logs = $this->consolidate_logs((array) $logs);

        // Get scan status
        $scan_error = get_transient('wb_scan_error');
        $scan_success = get_transient('wb_scan_success');

        // Start output
        ?>
        <div class="wrap wb-wrapper">
            <header class="wb-header">
                <h1 class="wp-heading-inline">
                    <span class="dashicons dashicons-admin-tools"
                        style="font-size: 32px; width: 32px; height: 32px; margin-right: 10px;"></span>
                    <?php esc_html_e('WB Product Importer', 'wb-importer'); ?>
                </h1>
                <span class="wb-version">v<?php echo esc_html(WB_PRODUCT_IMPORTER_VERSION); ?></span>
            </header>

            <?php if (isset($_GET['wb_import_done'])): ?>
                <div class="wb-alert wb-alert-success">
                    <p><?php esc_html_e('Import completed successfully!', 'wb-importer'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($scan_error): ?>
                <div class="wb-alert wb-alert-error">
                    <p><span class="dashicons dashicons-warning"></span>
                        <strong><?php esc_html_e('Error:', 'wb-importer'); ?></strong>
                        <?php echo esc_html($scan_error); ?>
                    </p>
                    <?php delete_transient('wb_scan_error'); ?>
                </div>
            <?php elseif ($scan_success): ?>
                <div class="wb-alert wb-alert-success">
                    <p><span class="dashicons dashicons-yes"></span> <?php echo esc_html($scan_success); ?></p>
                    <?php delete_transient('wb_scan_success'); ?>
                </div>
            <?php endif; ?>

            <div class="wb-grid">
                <!-- Import Card -->
                <div class="wb-card">
                    <div class="wb-card-header">
                        <h2><?php esc_html_e('Import Products', 'wb-importer'); ?></h2>
                    </div>
                    <div class="wb-card-body">
                        <div class="wb-tabs">
                            <button type="button" class="wb-tab wb-tab-active" data-tab="knitted">
                                <?php esc_html_e('Knitted Products', 'wb-importer'); ?>
                            </button>
                            <button type="button" class="wb-tab" data-tab="woven">
                                <?php esc_html_e('Woven Products', 'wb-importer'); ?>
                            </button>
                        </div>

                        <!-- Knitted Products Import -->
                        <div class="wb-tab-content wb-tab-content-active" id="knitted-tab">
                            <div class="wb-form-group">
                                <p><?php esc_html_e('Import knitted products from the WB directory.', 'wb-importer'); ?></p>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                    class="wb-import-form">
                                    <input type="hidden" name="action" value="wb_import_knitted">
                                    <div class="wb-form-group">
                                        <label class="wb-checkbox-label">
                                            <input type="checkbox" name="dry_run" value="1" checked class="wb-checkbox">
                                            <?php esc_html_e('Dry Run (simulate only)', 'wb-importer'); ?>
                                        </label>
                                    </div>
                                    <button type="submit" class="wb-btn wb-btn-primary">
                                        <span class="dashicons dashicons-update wb-spinner" style="display: none;"></span>
                                        <span
                                            class="wb-btn-text"><?php esc_html_e('Import Knitted Products', 'wb-importer'); ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Woven Products Import -->
                        <div class="wb-tab-content" id="woven-tab">
                            <div class="wb-form-group">
                                <p><?php esc_html_e('Import woven products from the WB directory.', 'wb-importer'); ?></p>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                                    class="wb-import-form">
                                    <input type="hidden" name="action" value="wb_import_woven">
                                    <div class="wb-form-group">
                                        <label class="wb-checkbox-label">
                                            <input type="checkbox" name="dry_run" value="1" checked class="wb-checkbox">
                                            <?php esc_html_e('Dry Run (simulate only)', 'wb-importer'); ?>
                                        </label>
                                    </div>
                                    <button type="submit" class="wb-btn wb-btn-primary">
                                        <span class="dashicons dashicons-update wb-spinner" style="display: none;"></span>
                                        <span
                                            class="wb-btn-text"><?php esc_html_e('Import Woven Products', 'wb-importer'); ?></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tools Card -->
                <div class="wb-card">
                    <div class="wb-card-header">
                        <h2><?php esc_html_e('Tools', 'wb-importer'); ?></h2>
                    </div>
                    <div class="wb-card-body">
                        <div class="wb-form-group">
                            <h3><?php esc_html_e('JSON Tools', 'wb-importer'); ?></h3>
                            <p><?php esc_html_e('Generate and download the product structure JSON for inspection.', 'wb-importer'); ?>
                            </p>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="wb_generate_structure">
                                <input type="hidden" name="_wpnonce"
                                    value="<?php echo esc_attr(wp_create_nonce('wb_generate_structure_nonce')); ?>">
                                <button type="submit" class="wb-btn wb-btn-secondary" style="width: 100%;">
                                    <span class="dashicons dashicons-download"></span>
                                    <?php esc_html_e('Generate & Download JSON', 'wb-importer'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Debug Log Card -->
                <div class="wb-card wb-card-wide">
                    <div class="wb-card-header">
                        <h2><?php esc_html_e('Debug Log', 'wb-importer'); ?></h2>
                        <button type="button" class="wb-btn wb-btn-secondary wb-clear-log">
                            <span class="dashicons dashicons-trash"></span>
                            <?php esc_html_e('Clear Log', 'wb-importer'); ?>
                        </button>
                    </div>
                    <div class="wb-card-body">
                        <div class="wb-form-group">
                            <textarea class="wb-log-area"
                                readonly><?php echo esc_textarea(implode("\n", $consolidated_logs)); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    // Tab switching
                    $('.wb-tab').on('click', function () {
                        const tabId = $(this).data('tab');

                        // Update active tab
                        $('.wb-tab').removeClass('wb-tab-active');
                        $(this).addClass('wb-tab-active');

                        // Show active content
                        $('.wb-tab-content').removeClass('wb-tab-content-active');
                        $(`#${tabId}-tab`).addClass('wb-tab-content-active');
                    });

                    // Form submission handling
                    $('.wb-import-form').on('submit', function () {
                        const $form = $(this);
                        const $button = $form.find('button[type="submit"]');
                        const $spinner = $button.find('.wb-spinner');
                        const $buttonText = $button.find('.wb-btn-text');

                        // Show loading state
                        $button.prop('disabled', true);
                        $spinner.show();
                        $buttonText.text(wbAdmin.i18n.importing);

                        // Auto-scroll debug log to bottom
                        $('.wb-log-area').scrollTop($('.wb-log-area')[0].scrollHeight);
                    });

                    // Clear log button
                    $('.wb-clear-log').on('click', function () {
                        $('.wb-log-area').val('');
                    });
                });
            </script>
        </div>
        <?php

        // Add screen meta for postboxes
        $screen = get_current_screen();
        add_meta_box('wb-summary', 'Summary', '', $screen->id, 'normal', 'high');
        add_meta_box('wb-import-actions', 'Import Products', '', $screen->id, 'normal', 'high');
        add_meta_box('wb-debug-log', 'Debug Log', '', $screen->id, 'normal', 'high');
        add_meta_box('wb-json-tools', 'JSON Tools', '', $screen->id, 'side', 'high');

        // Add JavaScript for postbox handling
        ?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                // Close all postboxes by default
                // $('.postbox').addClass('closed');

                // Initialize postboxes with the correct screen ID
                if (typeof postboxes !== 'undefined') {
                    postboxes.add_postbox_toggles('<?php echo $screen->id; ?>');
                }

                // Handle clicks on postbox headers
                $('.postbox .hndle, .postbox .handlediv').on('click', function (e) {
                    // Don't trigger if clicking on a link or button inside the header
                    if ($(e.target).is('a, button, input, .dashicons')) {
                        return;
                    }

                    var postbox = $(this).closest('.postbox');
                    var toggle = postbox.find('.handlediv');

                    // Toggle the closed class
                    postbox.toggleClass('closed');

                    // Update the toggle button state
                    var isClosed = postbox.hasClass('closed');
                    toggle.attr('aria-expanded', !isClosed);

                    // Toggle the content
                    postbox.find('.inside').slideToggle(!isClosed);
                });

                // Handle scan button click
                $('#wb-scan-button').on('click', function (e) {
                    if (!confirm('Are you sure you want to scan for products? This might take a while for large directories.')) {
                        e.preventDefault();
                        return false;
                    }
                    $('#wb-scan-status').show();
                    $(this).prop('disabled', true).addClass('button-disabled');
                    return true;
                });
            });
            //]]>
        </script>
        <?php

        // Scan status messages are now handled in the summary postbox

        // Try to load the structure from the saved file first
        $upload_dir = wp_upload_dir();
        $structure_file = $upload_dir['basedir'] . '/wb-product-data/wb_structure_woven.json';
        $structure = [];

        // First try to load from saved file
        if (file_exists($structure_file)) {
            $structure_data = file_get_contents($structure_file);
            if (!empty($structure_data)) {
                $structure = json_decode($structure_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('Error decoding structure JSON: ' . json_last_error_msg());
                    $structure = [];
                }
            }
        }

        // If no saved structure, try to scan the directory
        if (empty($structure)) {
            $woven_path = WP_CONTENT_DIR . '/uploads/wb-products/';

            if (is_dir($woven_path)) {
                $structure = $this->generate_product_structure($woven_path);

                // If we got a valid structure, save it
                if (!empty($structure) && !is_wp_error($structure)) {
                    $structure_dir = dirname($structure_file);
                    if (!is_dir($structure_dir)) {
                        wp_mkdir_p($structure_dir);
                    }
                    file_put_contents($structure_file, json_encode($structure, JSON_PRETTY_PRINT));
                }
            } else {
                // Fallback to knitted products if woven directory doesn't exist
                $knitted_path = WP_CONTENT_DIR . '/uploads/wb-knitted-products/';
                $structure_file = str_replace('_woven.', '_knitted.', $structure_file);

                if (is_dir($knitted_path)) {
                    $structure = $this->generate_product_structure($knitted_path);

                    if (!empty($structure) && !is_wp_error($structure)) {
                        $structure_dir = dirname($structure_file);
                        if (!is_dir($structure_dir)) {
                            wp_mkdir_p($structure_dir);
                        }
                        file_put_contents($structure_file, json_encode($structure, JSON_PRETTY_PRINT));
                    }
                }
            }
        }

        // Display the structure or a message
        if (empty($structure)) {
            echo '<div class="notice notice-warning">';
            echo '<p>No product structure found. Please click the "Scan Products Now" button above to scan your product directories.</p>';
            echo '<p>Make sure one of these directories exists and contains your product folders:</p>';
            echo '<ul style="margin-left: 20px;">';
            echo '<li><code>' . esc_html(WP_CONTENT_DIR . '/uploads/wb-products/') . '</code> (preferred for this site)</li>';
            echo '<li><code>' . esc_html(WP_CONTENT_DIR . '/uploads/wb-knitted-products/') . '</code></li>';
            echo '</ul>';
            echo '</div>';
        } else {
            $this->render_structure_visualization($structure);
        }

        echo '</div>'; // Close visualization div

        echo '</div>'; // Close wrap div
    }

    /**
     * Handle knitted product import
     */
    public function handle_knitted_import()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied');
        }

        $dry_run = !empty($_POST['dry_run']);

        // Generate the product structure from the folder
        $base_path = WP_CONTENT_DIR . '/uploads/wb-knitted-products/';
        $structure = $this->generate_product_structure($base_path);

        if (empty($structure)) {
            wp_die('No product structure could be generated. Please check if the directory exists: ' . $base_path);
        }

        // Save the generated structure for reference
        $structure_file = WB_PRODUCT_IMPORTER_PATH . 'data/wb_structure_knitted.json';
        if (!is_dir(dirname($structure_file))) {
            wp_mkdir_p(dirname($structure_file));
        }
        file_put_contents($structure_file, json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Prepare and import products
        $structured_data = $this->prepare_product_import_data($structure);
        $this->import_knitted_products($structured_data, $dry_run);

        wp_redirect(admin_url('admin.php?page=wb-helper&wb_import_done=1'));
        exit;
    }

    /**
     * Handle woven product import
     */
    /**
     * Handle JSON structure generation and download
     */
    public function handle_generate_structure()
    {
        if (
            !current_user_can('manage_woocommerce') ||
            !isset($_POST['_wpnonce']) ||
            !wp_verify_nonce($_POST['_wpnonce'], 'wb_generate_structure_nonce')
        ) {
            wp_die('Permission denied');
        }

        // Generate the structure
        $structure = $this->generate_product_structure(WP_CONTENT_DIR . '/uploads/wb-products/');
        $json = json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Set headers for file download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="wb_structure_' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));

        // Output the JSON
        echo $json;
        exit;
    }

    /**
     * Handle woven product import
     */
    public function handle_woven_import()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied');
        }

        $folder_path = WP_CONTENT_DIR . '/uploads/wb-products/';
        $dry_run = !empty($_POST['dry_run']);

        $this->import_woven_products($folder_path, $dry_run);

        wp_redirect(admin_url('admin.php?page=wb-helper&wb_import_done=1'));
        exit;
    }

    /**
     * Handle manual import trigger via URL param ?run_wb=1
     */
    public function handle_import_request()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle product scan
        if (
            isset($_GET['action']) && $_GET['action'] === 'scan_products' &&
            isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wb_scan_products')
        ) {

            // Start timing the scan
            $start_time = microtime(true);

            // Force a fresh scan
            $upload_dir = wp_upload_dir();
            $structure_dir = $upload_dir['basedir'] . '/wb-product-data/';
            $woven_path = WP_CONTENT_DIR . '/uploads/wb-products/';
            $knitted_path = WP_CONTENT_DIR . '/uploads/wb-knitted-products/';
            $structure = [];
            $scanned_path = '';

            // Clear any previous errors
            delete_transient('wb_scan_error');

            // Check which directory exists and scan it
            if (is_dir($woven_path)) {
                $scanned_path = $woven_path;
                $structure_file = $structure_dir . 'wb_structure_woven.json';
            } elseif (is_dir($knitted_path)) {
                $scanned_path = $knitted_path;
                $structure_file = $structure_dir . 'wb_structure_knitted.json';
            } else {
                $error_msg = 'Neither woven nor knitted products directory found. Please ensure one of these directories exists: ' .
                    $woven_path . ' or ' . $knitted_path;
                error_log($error_msg);
                set_transient('wb_scan_error', $error_msg, 60);
                wp_redirect(remove_query_arg(['action', '_wpnonce']));
                exit;
            }

            // Log start of scan
            error_log('Starting product structure scan in: ' . $scanned_path);

            // Generate the structure
            $structure = $this->generate_product_structure($scanned_path);

            if (is_wp_error($structure)) {
                $error_msg = 'Error generating product structure: ' . $structure->get_error_message();
                error_log($error_msg);
                set_transient('wb_scan_error', $error_msg, 60); // Store error for 1 minute
            } else {
                // Ensure the structure directory exists
                if (!is_dir($structure_dir)) {
                    wp_mkdir_p($structure_dir);
                }

                // Save the structure
                $result = file_put_contents($structure_file, json_encode($structure, JSON_PRETTY_PRINT));

                if ($result === false) {
                    $error_msg = 'Failed to save product structure to: ' . $structure_file;
                    error_log($error_msg);
                    set_transient('wb_scan_error', $error_msg, 60);
                } else {
                    $scan_time = round(microtime(true) - $start_time, 2);
                    $scan_summary = sprintf(
                        'Scanned %d products with %d total variations in %s seconds',
                        count($structure),
                        array_sum(array_map('count', array_column($structure, 'variations'))),
                        $scan_time
                    );

                    error_log('Product scan completed: ' . $scan_summary);
                    set_transient('wb_scan_success', $scan_summary, 60);
                }
            }

            // Redirect back to the same page without the action parameter
            wp_redirect(remove_query_arg(['action', '_wpnonce']));
            exit;
        }

        // Handle import request
        if (isset($_GET['run_wb'])) {
            $this->run_import();
            exit;
        }
    }

    /**
     * Render a visual representation of the product structure
     * 
     * @param array $structure The product structure to render
     */
    private function render_structure_visualization($structure)
    {
        $total_products = count($structure);
        $total_variations = 0;
        $total_images = 0;

        // Group products by their main category (first part of the product name)
        $grouped_products = [];

        foreach ($structure as $product) {
            // Extract main category (first part before first space or slash)
            $product_name = $product['product_name'];
            $category_end = strpos($product_name, ' ');
            if ($category_end === false) {
                $category_end = strpos($product_name, '/');
            }
            $category = $category_end !== false ? substr($product_name, 0, $category_end) : $product_name;

            if (!isset($grouped_products[$category])) {
                $grouped_products[$category] = [];
            }
            $grouped_products[$category][] = $product;

            // Calculate totals
            $total_variations += count($product['variations']);
            foreach ($product['variations'] as $variation) {
                $total_images += count($variation['gallery']) + ($variation['featured'] ? 1 : 0);
            }
        }

        // Main visualization container - removed 'closed' class to make it open by default
        echo '<div id="wb-structure-visualization" class="postbox open">';
        echo '<h2 class="hndle"><span><span class="dashicons dashicons-chart-pie"></span> Product Structure</span></h2>';
        echo '<div class="inside">';

        // Check JSON file status
        $json_paths = [
            'ACF Data' => WP_CONTENT_DIR . '/uploads/woocommerce_acf_data_with_categories.json',
            'Product Structure' => WP_CONTENT_DIR . '/uploads/wb_product_structure.json',
            'Import Logs' => WP_CONTENT_DIR . '/uploads/wb_import_logs.json'
        ];

        $json_status = [];
        foreach ($json_paths as $name => $path) {
            $exists = file_exists($path);
            $json_status[] = sprintf(
                '<span class="dashicons %s"></span> %s: %s<br><small>%s</small>',
                $exists ? 'dashicons-yes-alt' : 'dashicons-warning',
                $name,
                $exists ? 'Found' : 'Not Found',
                esc_html(str_replace(ABSPATH, '/', $path))
            );
        }

        // Summary card
        echo '<div class="card">';
        echo '<h3 class="title"><span class="dashicons dashicons-info"></span> Summary</h3>';
        echo '<div class="misc-pub-section">';
        echo '<span class="dashicons dashicons-category"></span> ' . esc_html(sprintf(_n('%d Main Category', '%d Main Categories', count($grouped_products), 'wb-importer'), count($grouped_products))) . '<br>';
        echo '<span class="dashicons dashicons-tag"></span> ' . esc_html(sprintf(_n('%d Product', '%d Products', $total_products, 'wb-importer'), $total_products)) . '<br>';
        echo '<span class="dashicons dashicons-image-rotate"></span> ' . esc_html(sprintf(_n('%d Variation', '%d Variations', $total_variations, 'wb-importer'), $total_variations)) . '<br>';
        echo '<span class="dashicons dashicons-format-gallery"></span> ' . esc_html(sprintf(_n('%d Image', '%d Images', $total_images, 'wb-importer'), $total_images));
        echo '</div>';

        // JSON Status section
        echo '<div class="misc-pub-section" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">';
        echo '<h4><span class="dashicons dashicons-media-document"></span> JSON Files Status</h4>';
        echo implode('<br>', $json_status);
        echo '</div>';
        echo '</div>';

        // Categories accordion
        echo '<div class="meta-box-sortables ui-sortable">';

        foreach ($grouped_products as $category => $products) {
            $category_products = count($products);
            $category_variations = 0;
            $category_images = 0;

            // Calculate category totals
            foreach ($products as $product) {
                $category_variations += count($product['variations']);
                foreach ($product['variations'] as $variation) {
                    $category_images += count($variation['gallery']) + ($variation['featured'] ? 1 : 0);
                }
            }

            // Category postbox - expanded by default
            echo '<div class="postbox category-item" style="display: block;">';
            echo '<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">' . __('Toggle panel', 'wb-importer') . '</span><span class="toggle-indicator" style="transform: rotate(180deg);" aria-hidden="true"></span></button>';
            echo '<h3 class="hndle"><span class="dashicons dashicons-category"></span> ' . esc_html($category) . ' <span class="category-count">(' . $category_products . ')</span></h3>';
            echo '<div class="inside" style="display: block;">';

            // Category stats
            echo '<div class="misc-pub-section">';
            echo '<span class="dashicons dashicons-tag"></span> ' . sprintf(_n('%d Product', '%d Products', $category_products, 'wb-importer'), $category_products) . ' | ';
            echo '<span class="dashicons dashicons-image-rotate"></span> ' . sprintf(_n('%d Variation', '%d Variations', $category_variations, 'wb-importer'), $category_variations) . ' | ';
            echo '<span class="dashicons dashicons-format-gallery"></span> ' . sprintf(_n('%d Image', '%d Images', $category_images, 'wb-importer'), $category_images);
            echo '</div>';

            // Products table
            echo '<table class="wp-list-table widefat fixed striped table-view-list">';
            echo '<thead><tr>';
            echo '<th>' . __('Folder', 'wb-importer') . '</th>';
            echo '<th style="width: 150px;">' . __('Price', 'wb-importer') . '</th>';
            echo '<th>' . __('ACF Tabs', 'wb-importer') . '</th>';
            echo '<th>' . __('Images', 'wb-importer') . '</th>';
            echo '<th>' . __('Preview', 'wb-importer') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($products as $product) {
                $product_variations = count($product['variations']);
                $product_images = 0;

                // Calculate product images
                foreach ($product['variations'] as $variation) {
                    $product_images += count($variation['gallery']) + ($variation['featured'] ? 1 : 0);
                }

                // Get ACF tabs info for this product
                $acf_tabs = $this->get_acf_tabs_for_product($product);
                $tab_count = count($acf_tabs);
                $tabs_html = '';

                if ($tab_count > 0) {
                    $tabs_list = [];
                    $tabs_content = [];

                    foreach ($acf_tabs as $tab_name => $tab_content) {
                        $tab_id = sanitize_title($tab_name);
                        $tabs_list[] = '<button type="button" class="acf-tab-button" data-tab="' . esc_attr($tab_id) . '">' .
                            esc_html($tab_name) . '</button>';

                        $tabs_content[] = '<div id="' . esc_attr($tab_id) . '" class="acf-tab-content">' .
                            wpautop($tab_content) .
                            '</div>';
                    }

                    // Create a unique ID for this product's tabs container
                    $tabs_container_id = 'acf-tabs-' . md5($product['folder_path']);

                    $tabs_html = '<div class="acf-tabs-container" id="' . esc_attr($tabs_container_id) . '">';
                    $tabs_html .= '<div class="acf-tabs-header">' . implode('', $tabs_list) . '</div>';
                    $tabs_html .= '<div class="acf-tabs-body">' . implode('\n', $tabs_content) . '</div>';

                    // Add edit controls
                    $tabs_json = esc_attr(wp_json_encode($acf_tabs));
                    $tabs_html .= '<div class="acf-tabs-edit-controls" style="margin-top: 15px; display: none;">';
                    $tabs_html .= '<textarea class="acf-tabs-json" style="width: 100%; min-height: 200px; font-family: monospace; display: none;">' . esc_textarea(wp_json_encode($acf_tabs, JSON_PRETTY_PRINT)) . '</textarea>';
                    $tabs_html .= '<div class="acf-tabs-actions" style="margin-top: 10px;">';
                    $tabs_html .= '<button type="button" class="button button-primary save-acf-tabs" data-container="' . esc_attr($tabs_container_id) . '">' . esc_html__('Save Changes', 'wb-importer') . '</button> ';
                    $tabs_html .= '<button type="button" class="button discard-acf-tabs" data-container="' . esc_attr($tabs_container_id) . '">' . esc_html__('Discard', 'wb-importer') . '</button>';
                    $tabs_html .= '</div></div>'; // Close acf-tabs-edit-controls and acf-tabs-actions
                    $tabs_html .= '</div>'; // Close acf-tabs-container

                    // Add some basic styling for the tabs
                    $tabs_html .= '<style>.acf-tabs-container{margin:15px 0;}.acf-tabs-header{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;}.acf-tab-button{background:#f0f0f1;border:1px solid #dcdcde;padding:5px 12px;cursor:pointer;border-radius:3px;transition:all .2s ease;}.acf-tab-button:hover,.acf-tab-button.active{background:#2271b1;color:#fff;border-color:#2271b1;}.acf-tabs-body{border:1px solid #dcdcde;padding:15px;border-radius:3px;}.acf-tab-content{display:none;}.acf-tab-content.active{display:block;}.acf-tabs-edit-controls{margin-top:15px;}.acf-tabs-edit-toggle{margin-left:10px;font-size:.9em;}.edit-mode .acf-tabs-body{display:none;}.edit-mode .acf-tabs-json{display:block!important;}</style>';
                    $tabs_html .= '<div class="acf-tabs-status" style="display: none; margin: 10px 0; padding: 5px 10px; background: #f0f0f1; border-left: 4px solid #2271b1;"></div>';
                } else {
                    $tabs_html = '<div class="notice notice-warning"><p>' . esc_html__('No ACF data found for this product.', 'wb-importer') . '</p></div>';
                }

                // Get Price
                $product_id = $this->get_product_id_from_structure_item($product);
                $current_price = '';
                if ($product_id) {
                    $wc_product = wc_get_product($product_id);
                    if ($wc_product) {
                        $current_price = $wc_product->get_price();
                    }
                }

                echo '<tr class="product-item" data-product-id="' . esc_attr($product_id) . '">';
                echo '<td><code>' . esc_html(basename($product['folder_path'])) . '</code></td>';
                echo '<td>';
                echo '<div class="wb-price-control" style="display:flex; gap:5px; align-items:center;">';
                echo '<input type="number" step="0.01" class="wb-price-input" value="' . esc_attr($current_price) . '" style="width: 80px;" placeholder="0.00">';
                echo '<button type="button" class="button wb-save-price" ' . (!$product_id ? 'disabled title="Product not found in DB"' : '') . '><span class="dashicons dashicons-saved"></span></button>';
                echo '</div>';
                echo '<span class="price-status" style="font-size:10px; display:block; margin-top:2px;"></span>';
                echo '</td>';
                echo '<td>' . $tabs_html .
                    ($tab_count > 0 ? ' <a href="#" class="edit-acf-tabs button button-small" style="margin-top: 5px;">' .
                        '<span class="dashicons dashicons-edit"></span> ' . __('Edit Tabs', 'wb-importer') . '</a>' : '') . '</td>';
                echo '<td>' . $product_images . ' ' . _n('image', 'images', $product_images, 'wb-importer') . '</td>';

                // Show first variation image as preview if available
                echo '<td>';
                if (!empty($product['variations'][0]['featured'])) {
                    $first_variation = $product['variations'][0];
                    $image_url = content_url('uploads/wb-products/' . basename($product['folder_path']) . '/' . $first_variation['name'] . '/' . $first_variation['featured']);
                    echo '<a href="' . esc_url($image_url) . '"><img src="' . esc_url($image_url) . '" style="max-width: 50px; max-height: 50px; border: 1px solid #ddd;" class="product-thumbnail"></a>';
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="color: #ccc;"></span>';
                }
                echo '</td>';

                echo '</tr>';
            }

            echo '</tbody></table>'; // Close products table
            echo '</div>'; // Close .inside
            echo '</div>'; // Close .postbox
        }

        echo '</div>'; // Close .meta-box-sortables

        echo '</div>'; // Close .inside
        echo '</div>'; // Close .postbox

        // Enqueue WordPress postbox script for toggling
        ?>
        <script>
            jQuery(document).ready(function ($) {
                // Price Editor Handler
                $('.wb-save-price').on('click', function () {
                    var $btn = $(this);
                    var $row = $btn.closest('tr');
                    var productId = $row.data('product-id');
                    var price = $row.find('.wb-price-input').val();
                    var $status = $row.find('.price-status');

                    $btn.prop('disabled', true);
                    $status.text('Saving...').css('color', '#666');

                    $.ajax({
                        url: wbAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'wb_save_price',
                            nonce: wbAdmin.nonce,
                            product_id: productId,
                            price: price
                        },
                        success: function (response) {
                            if (response.success) {
                                $status.text(response.data).css('color', 'green');
                            } else {
                                $status.text(response.data).css('color', 'red');
                            }
                        },
                        error: function () {
                            $status.text('Error').css('color', 'red');
                        },
                        complete: function () {
                            $btn.prop('disabled', false);
                            setTimeout(function () { $status.text(''); }, 3000);
                        }
                    });
                });
                // Make sure postboxes is defined
                if (typeof postboxes !== 'undefined') {
                    // Initialize postboxes for our visualization
                    postboxes.add_postbox_toggles('wb-structure-visualization');
                    // Ensure the postbox is open by default
                    $('.postbox').removeClass('closed');

                    // Handle clicks on the entire header (h2.hndle)
                    $('.wb-structure-visualization .postbox > .hndle').on('click', function (e) {
                        // Don't trigger if clicking on a link or button inside the header
                        if ($(e.target).is('a, button, input, .dashicons')) {
                            return;
                        }

                        var postbox = $(this).closest('.postbox');
                        var toggle = postbox.find('.handlediv');

                        // Toggle the closed class
                        postbox.toggleClass('closed');

                        // Update the toggle button state
                        var isClosed = postbox.hasClass('closed');
                        toggle.attr('aria-expanded', !isClosed);

                        // Toggle the content
                        postbox.find('.inside').slideToggle(!isClosed);
                    });

                    // Handle clicks on the toggle button
                    $('.wb-structure-visualization .handlediv').on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var postbox = $(this).closest('.postbox');
                        var isClosed = !postbox.hasClass('closed');

                        // Toggle the closed class
                        postbox.toggleClass('closed');

                        // Update the toggle button state
                        $(this).attr('aria-expanded', isClosed);

                        // Toggle the content
                        postbox.find('.inside').slideToggle(isClosed);
                    });
                }
            });
        </script>

        <style>
            /* Additional styles for better appearance */
            .wb-structure-visualization .postbox {
                margin-bottom: 20px;
            }

            .wb-structure-visualization .postbox .hndle {
                cursor: pointer;
            }

            .wb-structure-visualization .dashicons {
                margin-right: 5px;
                vertical-align: middle;
            }

            .wb-structure-visualization .misc-pub-section {
                padding: 10px 12px;
                border-bottom: 1px solid #f0f0f0;
            }

            .wb-structure-visualization .misc-pub-section:last-child {
                border-bottom: none;
            }

            .wb-structure-visualization .product-thumbnail {
                border-radius: 3px;
                transition: transform 0.2s;
            }

            .wb-structure-visualization .product-thumbnail {
                max-width: 80px;
                max-height: 80px;
                border: 1px solid #ddd;
                border-radius: 3px;
                transition: all 0.2s ease;
            }

            .wb-structure-visualization .product-thumbnail:hover {
                transform: scale(1.5);
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                z-index: 10;
                position: relative;
            }

            .wb-structure-visualization .category-count {
                color: #646970;
                font-weight: normal;
            }

            .acf-tab-details {
                display: none;
                background: #f8f9fa;
                border: 1px solid #dcdcde;
                padding: 10px;
                margin-top: 10px;
                border-radius: 4px;
            }

            .acf-tab-details h4 {
                margin-top: 0;
                border-bottom: 1px solid #dcdcde;
                padding-bottom: 5px;
            }

            .acf-tab-details ul {
                margin: 0;
                padding-left: 20px;
            }

            .view-acf-tabs {
                margin-left: 8px;
                text-decoration: none;
            }

            .view-acf-tabs:hover {
                text-decoration: underline;
            }
        </style>
        <?php
    }

    /**
     * Generate product structure from folder
     * 
     * @param string $base_path Base path to scan
     * @param int $depth Current depth (used internally)
     * @param int $max_depth Maximum depth to scan (0 for unlimited)
     * @param string $parent_name Parent product name (used internally)
     * @return array Generated product structure
     */
    private function generate_product_structure($base_path, $depth = 0, $max_depth = 3, $parent_name = '')
    {
        $structure = [];

        // Ensure the base path exists and is a directory
        if (!is_dir($base_path)) {
            $this->debug_log[] = "[ERROR] Directory not found: $base_path";
            return [];
        }

        // Get all items in the directory
        $items = @scandir($base_path);

        if ($items === false) {
            $this->debug_log[] = "[ERROR] Could not read directory: $base_path";
            return [];
        }

        // Sort items to process directories first
        usort($items, function ($a, $b) use ($base_path) {
            $aIsDir = is_dir($base_path . '/' . $a);
            $bIsDir = is_dir($base_path . '/' . $b);

            if ($aIsDir === $bIsDir) {
                return strcasecmp($a, $b);
            }
            return $aIsDir ? -1 : 1;
        });

        $current_product = [
            'product_name' => $parent_name ?: ucwords(str_replace(['-', '_'], ' ', basename($base_path))),
            'folder_path' => $base_path,
            'variations' => []
        ];

        $has_variations = false;

        foreach ($items as $item) {
            // Skip hidden files and directories
            if ($item[0] === '.') {
                continue;
            }

            $full_path = $base_path . '/' . $item;

            if (is_dir($full_path)) {
                // If it's a directory and we haven't reached max depth, process it
                if ($max_depth > 0 && $depth < $max_depth) {
                    // If we're at the product level, use the directory name as the product name
                    if ($depth === 0) {
                        $current_product['product_name'] = ucwords(str_replace(['-', '_'], ' ', $item));
                    }

                    // Recursively process the subdirectory
                    $sub_structure = $this->generate_product_structure(
                        $full_path,
                        $depth + 1,
                        $max_depth,
                        $current_product['product_name']
                    );

                    // If the subdirectory contains variations, merge them
                    if (!empty($sub_structure)) {
                        $has_variations = true;
                        $structure = array_merge($structure, $sub_structure);
                    }
                }
            } else {
                // Process image files
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $is_featured = (strpos(strtolower($item), 'main') !== false) ||
                        (strpos(strtolower($item), 'featured') !== false) ||
                        (strpos(strtolower($item), 'cover') !== false);

                    $variation_name = $current_product['product_name'];

                    // If we're at the variation level, use the parent directory name as variation name
                    if ($depth > 0) {
                        $variation_name = basename($base_path);
                        $has_variations = true;
                    }

                    // Initialize variation if not exists
                    if (!isset($current_product['variations'][$variation_name])) {
                        $current_product['variations'][$variation_name] = [
                            'featured' => '',
                            'gallery' => []
                        ];
                    }

                    // Add image to the appropriate place
                    if ($is_featured) {
                        $current_product['variations'][$variation_name]['featured'] = $item;
                    } else {
                        $current_product['variations'][$variation_name]['gallery'][] = $item;
                    }
                }
            }
        }

        // Only add the current product if it has variations or is at the top level
        if (!empty($current_product['variations']) || $depth === 0) {
            // Convert variations to sequential array
            $current_product['variations'] = array_map(
                function ($variation, $name) {
                    return array_merge($variation, [
                        'name' => $name,
                        'display_name' => ucwords(str_replace(['-', '_'], ' ', $name))
                    ]);
                },
                array_values($current_product['variations']),
                array_keys($current_product['variations'])
            );

            $structure[] = $current_product;
        }

        return $structure;
    }

    /**
     * Import knitted products from structured data
     *
     * @param array $structured_data Product structure data
     * @param bool $dry_run Whether to perform a dry run without making changes
     * @return void
     */
    public function import_knitted_products($structured_data = null, $dry_run = false)
    {
        $this->debug_log = [];
        $total_products = 0;
        $total_variations = 0;
        $start_time = microtime(true);

        if ($dry_run) {
            $this->debug_log[] = "[INFO] Starting dry run - no changes will be made";
        } else {
            $this->debug_log[] = "[INFO] Starting import process";
        }

        if (empty($structured_data)) {
            $this->debug_log[] = "[ERROR] No product structure data provided";
            return;
        }

        // Process each product
        foreach ($structured_data as $product) {
            if (empty($product['product_name']) || empty($product['folder_path'])) {
                $this->debug_log[] = "[WARNING] Skipping invalid product data: " . json_encode($product);
                continue;
            }

            $product_name = $product['product_name'];
            $folder_path = $product['folder_path'];
            $variations = $product['variations'] ?? [];

            // Log product being processed
            $this->debug_log[] = "Processing product: $product_name";
            $this->debug_log[] = "Path: $folder_path";

            // Check if product already exists
            $product_check = new WP_Query([
                'post_type' => 'product',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 1,
                'title' => $product_name,
                'fields' => 'ids',
                'post__not_in' => $dry_run ? [0] : [] // Skip actual query in dry run
            ]);

            if (!empty($product_check->posts)) {
                $post_id = $product_check->posts[0];
                $is_new = false;
                $this->debug_log[] = "Found existing product (ID: $post_id)";
            } else {
                if ($dry_run) {
                    $post_id = 0;
                    $is_new = true;
                    $this->debug_log[] = "[DRY RUN] Would create product: $product_name";
                } else {
                    $post_data = [
                        'post_title' => $product_name,
                        'post_status' => 'publish',
                        'post_type' => 'product',
                        'post_content' => !empty($product['description']) ? $product['description'] : '',
                        'post_excerpt' => !empty($product['short_description']) ? $product['short_description'] : ''
                    ];

                    $post_id = wp_insert_post($post_data, true);

                    if (is_wp_error($post_id)) {
                        $this->debug_log[] = "[ERROR] Failed to create product '$product_name': " . $post_id->get_error_message();
                        continue;
                    }

                    $this->debug_log[] = "✅ Created new product (ID: $post_id)";
                    $is_new = true;
                }

                $total_products++;
            }

            // Process product variations if we have a valid post ID
            if ($post_id > 0) {
                if (!empty($variations)) {
                    $variation_count = count($variations);
                    $this->debug_log[] = "Processing $variation_count variations...";

                    if (!$dry_run) {
                        $variations_processed = $this->process_product_variations($post_id, $product_name, $folder_path, $variations);
                        $total_variations += $variations_processed;
                    } else {
                        $this->debug_log[] = "[DRY RUN] Would process $variation_count variations for: $product_name";
                        $total_variations += $variation_count;
                    }
                } else {
                    $this->debug_log[] = "[WARNING] No variations found for product: $product_name";
                }
            }

            $this->debug_log[] = str_repeat('-', 60);
        }

        // Calculate processing time
        $processing_time = round(microtime(true) - $start_time, 2);

        // Add summary
        $this->debug_log[] = "\n📊 Import Summary:";
        $this->debug_log[] = "Total products processed: $total_products";
        $this->debug_log[] = "Total variations processed: $total_variations";
        $this->debug_log[] = "Processing time: {$processing_time}s";

        if ($dry_run) {
            $this->debug_log[] = "\n⚠️ This was a dry run. No changes were made to the database.";
        } else {
            $this->debug_log[] = "\n✅ Import completed successfully!";
        }

        // Save debug log
        if (!empty($this->debug_log)) {
            set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);
        }

        return [
            'products' => $total_products,
            'variations' => $total_variations,
            'time' => $processing_time
        ];
    }

    /**
     * Process product variations
     * 
     * @param int $post_id Product ID
     * @param string $product_name Product name
     * @param string $folder_path Path to product folder
     * @param array $variations Array of variations
     * @return int Number of variations processed
     */
    private function process_product_variations($post_id, $product_name, $folder_path, $variations)
    {
        if (empty($variations) || !is_array($variations)) {
            $this->debug_log[] = "[WARNING] No variations provided for product ID $post_id";
            return 0;
        }

        $this->debug_log[] = "Processing " . count($variations) . " variations for product ID $post_id";

        // Mark as variable product
        wp_set_object_terms($post_id, 'variable', 'product_type');

        // Add the Color attribute
        $color_values = array_keys($variations);
        $attribute = new WC_Product_Attribute();
        $attribute->set_name('pa_color');
        $attribute->set_options(array_map('sanitize_title', $color_values));
        $attribute->set_visible(true);
        $attribute->set_variation(true);

        $product_obj = wc_get_product($post_id);
        $product_obj->set_attributes([$attribute]);
        $product_obj->save();

        // Add variations
        $processed_count = 0;
        foreach ($variations as $color => $images) {
            $result = $this->process_variation($post_id, $product_name, $folder_path, $color, $images);
            if ($result) {
                $processed_count++;
            }
        }

        $this->debug_log[] = "✅ Processed $processed_count variations for product ID $post_id";
        return $processed_count;
    }

    /**
     * Process a single variation
     * 
     * @param int $post_id Parent product ID
        $variation_id = $this->find_or_create_variation($post_id, $product_name, $color);

        if (!$variation_id) {
            $this->debug_log[] = "[ERROR] Failed to create variation for $product_name - $color";
            return false;
        }

        // Set variation data
        update_post_meta($variation_id, '_regular_price', '10');
        update_post_meta($variation_id, '_price', '10');
        update_post_meta($variation_id, 'attribute_pa_color', sanitize_title($color));
                $this->debug_log[] = "Processing images for variation: $color";
                $this->handle_variation_images($variation_id, $folder_path, $images);
                $this->debug_log[] = "✅ Processed " . count($images) . " images for variation: $color";
            } else {
                $this->debug_log[] = "[WARNING] No images found for variation: $color";
            }

            return true;
        } catch (Exception $e) {
            $this->debug_log[] = "[ERROR] Failed to process variation $color: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Find or create a variation
     */
    private function find_or_create_variation($post_id, $product_name, $color)
    {
        $variation_posts = get_children([
            'post_parent' => $post_id,
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'numberposts' => -1,
        ]);

        foreach ($variation_posts as $v) {
            $v_color = get_post_meta($v->ID, 'attribute_pa_color', true);
            if (strtolower($v_color) === strtolower(sanitize_title($color))) {
                return $v->ID;
            }
        }

        // Create new variation
        $variation_id = wp_insert_post([
            'post_title' => $product_name . ' - ' . $color,
            'post_status' => 'publish',
            'post_parent' => $post_id,
            'post_type' => 'product_variation',
        ]);

        if (is_wp_error($variation_id)) {
            $this->debug_log[] = "[ERROR] Failed to create variation for $product_name - $color: " . $variation_id->get_error_message();
            return false;
        }

        return $variation_id;
    }

    /**
     * Handle variation images
     */
    private function handle_variation_images($variation_id, $folder_path, $images)
    {
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            return;
        }

        // Assign featured image if missing
        if (!$variation->get_image_id() && !empty($images['featured'])) {
            $attachment_id = $this->wb_get_or_upload_image($folder_path, $images['featured'], $variation_id);
            if ($attachment_id) {
                set_post_thumbnail($variation_id, $attachment_id);
            }
        }

        // Assign gallery image if missing
        $existing_gallery = get_post_meta($variation_id, 'rtwpvg_images', true);
        if (empty($existing_gallery) && !empty($images['gallery'])) {
            $gallery_id = $this->wb_get_or_upload_image($folder_path, $images['gallery'], $variation_id);
            if ($gallery_id) {
                update_post_meta($variation_id, 'rtwpvg_images', [$gallery_id]);
            }
        }
    }

    /**
     * Get or upload an image to the media library
     */
    public function wb_get_or_upload_image($folder_path, $filename, $post_id = 0)
    {
        $relative_path = str_replace('/Users/developer/projects/WB/Knitted Products/', '', $folder_path);
        $local_path = self::WB_IMAGE_BASE_PATH . $relative_path . '/' . $filename;

        if (!file_exists($local_path)) {
            return 0;
        }

        // Check for existing image
        $existing = $this->wb_find_existing_image_by_filename($filename);
        if ($existing) {
            return $existing;
        }

        // Upload new image
        $upload_file = [
            'name' => basename($filename),
            'tmp_name' => $local_path,
        ];

        $filetype = wp_check_filetype($filename);
        $upload = wp_handle_sideload($upload_file, ['test_form' => false]);

        if (!empty($upload['error'])) {
            $this->debug_log[] = "[ERROR] Failed to upload $filename: " . $upload['error'];
            return 0;
        }

        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);

        if (is_wp_error($attach_id)) {
            $this->debug_log[] = "[ERROR] Failed to insert attachment for $filename: " . $attach_id->get_error_message();
            return 0;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    /**
     * Find existing image by filename
     */
    private function wb_find_existing_image_by_filename($filename)
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
            WHERE meta_key = '_wp_attached_file' 
            AND meta_value LIKE %s 
            LIMIT 1",
            '%' . $wpdb->esc_like($filename)
        );

        return (int) $wpdb->get_var($query) ?: 0;
    }

    /**
     * Prepare product import data from JSON
     */
    public function prepare_product_import_data($json_data)
    {
        $products = [];

        foreach ($json_data as $category) {
            foreach ($category['subfolders'] ?? [] as $product_folder) {
                $product_name = trim($product_folder['folder']);
                $image_files = $product_folder['images'] ?? [];
                $color_variations = [];

                foreach ($image_files as $img) {
                    // Extract product code from folder name
                    if (preg_match('/^(\d+)/', $product_name, $code_match)) {
                        $product_code = $code_match[1];
                    } else {
                        continue;
                    }

                    // Match only images for this product
                    if (preg_match('/\\d+-([A-Z0-9\\-]+)-(\\d+)\\.jpg$/i', $img, $matches)) {
                        $color = ucwords(strtolower(str_replace('-', ' ', $matches[1])));
                        $index = (int) $matches[2];
                        $color_variations[$color][] = ['index' => $index, 'file' => $img];
                    }
                }

                // Sort and assign images
                $variations = [];
                foreach ($color_variations as $color => $imgs) {
                    usort($imgs, fn($a, $b) => $a['index'] <=> $b['index']);
                    $featured = $imgs[0]['file'] ?? null;
                    $gallery = $imgs[1]['file'] ?? null;

                    if ($featured) {
                        $variations[$color] = [
                            'featured' => $featured,
                            'gallery' => $gallery,
                        ];
                    }
                }

                if (!empty($variations)) {
                    $products[] = [
                        'product_name' => $product_name,
                        'folder_path' => $product_folder['path'],
                        'variations' => $variations,
                    ];
                }
            }
        }

        return $products;
    }

    /**
     * Import woven products from folder
     */
    public function import_woven_products($folder_path, $dry_run = false)
    {
        if (!is_dir($folder_path)) {
            $this->debug_log[] = "[ERROR] Directory not found: $folder_path";
            return false;
        }

        $this->debug_log[] = "Starting woven products import from: $folder_path";

        if (!class_exists('WC_Product')) {
            $this->debug_log[] = "[ERROR] WooCommerce is not active";
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir())
                continue;

            $ext = strtolower($file->getExtension());
            $filename = $file->getBasename();
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) || strpos($filename, '.') === 0) {
                continue;
            }

            $image_path = $file->getRealPath();
            $relative_path = $this->normalize_path(str_replace($folder_path, '', $image_path));
            $file_mtime = filemtime($image_path);
            $image_name = 'wb-' . strtolower(pathinfo($filename, PATHINFO_FILENAME));

            // Check if product already exists
            $existing_product = get_posts([
                'post_type' => 'product',
                'meta_query' => [
                    ['key' => '_imported_image_path', 'value' => $relative_path],
                ],
                'post_status' => 'any',
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if ($dry_run) {
                $action = $existing_product ? 'update' : 'create';
                $this->debug_log[] = "[DRY RUN] Would $action product for image: $relative_path";
                continue;
            }

            // If exists, check modification time
            if ($existing_product) {
                $product_id = $existing_product[0];
                $existing_mtime = get_post_meta($product_id, '_image_file_mtime', true);

                if ($existing_mtime == $file_mtime) {
                    $this->debug_log[] = "Skipping unchanged file: $relative_path";
                    continue;
                }

                // Update existing product
                $attachment_id = $this->upload_image_to_media_library($image_path, $image_name);
                if ($attachment_id) {
                    set_post_thumbnail($product_id, $attachment_id);
                    update_post_meta($product_id, '_imported_image_path', $relative_path);
                    update_post_meta($product_id, '_image_file_mtime', $file_mtime);
                    $this->debug_log[] = "[SUCCESS] Updated product: $image_name";
                }
            } else {
                // Create new product
                $product = new WC_Product_Simple();
                $product->set_name($image_name);
                $product->set_status('publish');
                $product_id = $product->save();

                $attachment_id = $this->upload_image_to_media_library($image_path, $image_name);
                if ($attachment_id) {
                    set_post_thumbnail($product_id, $attachment_id);
                    update_post_meta($product_id, '_imported_image_path', $relative_path);
                    update_post_meta($product_id, '_image_file_mtime', $file_mtime);
                    $this->debug_log[] = "[SUCCESS] Created product: $image_name";
                }
            }
        }

        if (!empty($this->debug_log)) {
            set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);
        }

        return true;
    }

    /**
     * Sync knitted variation images
     */
    public function wb_sync_knitted_variation_images()
    {
        $json_path = WP_CONTENT_DIR . '/uploads/wb_structure_knitted_products.json';

        if (!file_exists($json_path)) {
            $this->debug_log[] = '❌ JSON file not found.';
            return false;
        }

        $json = file_get_contents($json_path);
        $raw_structure = json_decode($json, true);

        if (!is_array($raw_structure)) {
            $this->debug_log[] = '❌ Invalid JSON structure.';
            return false;
        }

        $structured_data = $this->prepare_product_import_data($raw_structure);
        $synced_count = 0;

        foreach ($structured_data as $product) {
            $product_name = $product['product_name'];
            $folder_path = $product['folder_path'];
            $variations = $product['variations'];

            // Find existing product by title
            $existing_product = new WP_Query([
                'post_type' => 'product',
                'title' => $product_name,
                'fields' => 'ids',
                'post_status' => 'publish',
                'posts_per_page' => 1
            ]);

            if (empty($existing_product->posts)) {
                $this->debug_log[] = "Product not found: $product_name";
                continue;
            }

            $product_id = $existing_product->posts[0];
            $this->process_product_variations($product_id, $product_name, $folder_path, $variations);
            $synced_count++;
        }

        $this->debug_log[] = "✅ Synced $synced_count products";
        set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);

        return $synced_count;
    }

    /**
     * Sync imported products with folder
     */
    public function sync_imported_products_with_folder($folder_path)
    {
        if (!class_exists('WC_Product')) {
            $this->debug_log[] = '❌ WooCommerce is not active';
            return false;
        }

        if (!is_dir($folder_path)) {
            $this->debug_log[] = "❌ Directory does not exist: $folder_path";
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($iterator as $file) {
            if ($file->isDir())
                continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif']))
                continue;

            $image_path = $file->getRealPath();
            $relative_path = $this->normalize_path(str_replace($folder_path, '', $image_path));
            $image_name = 'wb-' . strtolower(pathinfo($file->getBasename(), PATHINFO_FILENAME));

            // Check if product exists with this image
            $existing_products = get_posts([
                'post_type' => 'product',
                'meta_query' => [
                    ['key' => '_imported_image_path', 'value' => $relative_path]
                ],
                'post_status' => 'any',
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);

            if (empty($existing_products)) {
                $skipped++;
                continue;
            }

            $product_id = $existing_products[0];
            $attachment_id = $this->upload_image_to_media_library($image_path, $image_name);

            if ($attachment_id) {
                set_post_thumbnail($product_id, $attachment_id);
                update_post_meta($product_id, '_image_file_mtime', filemtime($image_path));
                $processed++;
            } else {
                $errors++;
            }
        }

        $this->debug_log[] = sprintf(
            '✅ Sync completed. Processed: %d, Skipped: %d, Errors: %d',
            $processed,
            $skipped,
            $errors
        );

        set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);
        return $processed;
    }

    /**
     * Update category featured images
     */
    public function update_category_featured_images()
    {
        if (!taxonomy_exists('product_cat')) {
            $this->debug_log[] = '❌ Product categories not found';
            return false;
        }

        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $updated = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            $args = [
                'post_type' => 'product',
                'posts_per_page' => 1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category->term_id,
                    ],
                ],
                'meta_query' => [
                    [
                        'key' => '_thumbnail_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ];

            $products = new WP_Query($args);

            if ($products->have_posts()) {
                $product = $products->posts[0];
                $thumbnail_id = get_post_thumbnail_id($product->ID);

                if ($thumbnail_id) {
                    update_term_meta($category->term_id, 'thumbnail_id', $thumbnail_id);
                    $updated++;
                    continue;
                }
            }
            $skipped++;
        }

        $this->debug_log[] = sprintf(
            '✅ Category images updated. Updated: %d, Skipped: %d',
            $updated,
            $skipped
        );

        set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);
        return $updated;
    }

    /**
     * Helper: Normalize Path
     */
    private function normalize_path($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Get match priority for an ACF entry
     * 
     * @param array $entry ACF entry
     * @param string $product_category_slug Product category in slug format
     * @return int Priority (higher is better)
     */
    private function get_match_priority($entry, $product_category_slug)
    {
        // Check wc_category match first (highest priority)
        if (!empty($entry['wc_category']) && $entry['wc_category'] === $product_category_slug) {
            return 100;
        }

        // Check exact category match
        if (isset($entry['category']) && strtolower(trim($entry['category'])) === $product_category_slug) {
            return 90;
        }

        // Check if product name contains the category or vice versa
        $entry_name = isset($entry['product_name']) ? strtolower($entry['product_name']) : '';
        if (
            strpos($entry_name, $product_category_slug) !== false ||
            strpos($product_category_slug, $entry_name) !== false
        ) {
            return 80;
        }

        // Check for common variations
        $common_variations = [
            'madras' => 'madras check',
            'corduroy' => 'corduroy fabric',
            'denim' => 'denim fabric',
            'flannel' => 'flannel fabric',
            'chambray' => 'chambray fabric'
        ];

        foreach ($common_variations as $key => $value) {
            if (
                (strpos($product_category_slug, $key) !== false &&
                    strpos($entry_name, $value) !== false) ||
                (strpos($product_category_slug, $value) !== false &&
                    strpos($entry_name, $key) !== false)
            ) {
                return 70;
            }
        }

        // Default priority for any other match
        return 10;
    }

    /**
     * Get ACF tabs for a product from JSON data
     * 
     * @param array $product Product data array
     * @return array Array of ACF tabs and their content
     */
    private function get_acf_tabs_for_product($product)
    {
        $tabs = [];

        // Extract search terms from the product structure
        $folder_path = $product['folder_path'] ?? '';
        $folder_basename = basename($folder_path);

        // Try to get variation name if it exists
        $variation_name = '';
        if (!empty($product['variations']) && isset($product['variations'][0]['name'])) {
            $variation_name = $product['variations'][0]['name'];
        } elseif (!empty($product['variations']) && isset($product['variations'][0]['display_name'])) {
            $variation_name = $product['variations'][0]['display_name'];
        }

        // Fallback to folder basename
        $search_term = !empty($variation_name) ? $variation_name : $folder_basename;

        // Path to the ACF JSON data file - try multiple possible locations
        $possible_paths = [
            '/Users/developer/projects/mill2mall/woocommerce_acf_data_with_categories.json',
            dirname(__FILE__) . '/../../../woocommerce_acf_data_with_categories.json',
            dirname(__FILE__) . '/woocommerce_acf_data_with_categories.json',
            ABSPATH . 'wp-content/uploads/woocommerce_acf_data_with_categories.json',
            WP_CONTENT_DIR . '/uploads/woocommerce_acf_data_with_categories.json'
        ];

        $json_path = '';
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $json_path = $path;
                break;
            }
        }

        // Check if we found a valid JSON file
        if (empty($json_path)) {
            error_log('WB Product Importer - ACF Data Error: JSON file not found in any of the searched locations');
            return $tabs;
        }

        // Read and parse the JSON file
        $json_data = file_get_contents($json_path);
        if ($json_data === false) {
            error_log('WB Product Importer - ACF Data Error: Failed to read JSON file');
            return $tabs;
        }

        $acf_data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WB Product Importer - ACF Data Error: Invalid JSON - ' . json_last_error_msg());
            return $tabs;
        }

        // If $acf_data is not an array, log error and return empty tabs
        if (!is_array($acf_data)) {
            error_log('WB Product Importer - ACF Data Error: Invalid data format in JSON file');
            return $tabs;
        }

        // Find matching product in the ACF data using improved matching
        $matching_product = null;
        $best_match_score = 0;

        foreach ($acf_data as $item) {
            if (!isset($item['product_name'])) {
                continue;
            }

            $acf_product_name = $item['product_name'];
            $match_score = 0;

            // Strategy 1: Check if search term is contained in ACF product name
            if (stripos($acf_product_name, $search_term) !== false) {
                $match_score = 80;
            }

            // Strategy 2: Check if ACF product name contains the variation name
            if (!empty($variation_name) && stripos($acf_product_name, $variation_name) !== false) {
                $match_score = max($match_score, 90);
            }

            // Strategy 3: Check category match
            if (!empty($item['category']) && stripos($folder_path, $item['category']) !== false) {
                $match_score = max($match_score, 70);
            }

            // Strategy 4: Check WC category slug match
            if (!empty($item['wc_category'])) {
                $wc_slug = $item['wc_category'];
                // Convert search term to slug format
                $search_slug = strtolower(str_replace([' ', '_'], '-', $search_term));
                if (stripos($wc_slug, $search_slug) !== false || stripos($search_slug, $wc_slug) !== false) {
                    $match_score = max($match_score, 85);
                }
            }

            // Strategy 5: Fuzzy word matching - split and compare words
            $acf_words = preg_split('/[\s\-_\/]+/', strtolower($acf_product_name));
            $search_words = preg_split('/[\s\-_\/]+/', strtolower($search_term));

            $common_words = array_intersect($acf_words, $search_words);
            if (count($common_words) > 0) {
                $word_match_ratio = count($common_words) / max(count($search_words), 1);
                $match_score = max($match_score, intval($word_match_ratio * 75));
            }

            // Keep track of best match
            if ($match_score > $best_match_score) {
                $best_match_score = $match_score;
                $matching_product = $item;
            }
        }

        // Only use match if score is above threshold
        if ($best_match_score < 50) {
            $matching_product = null;
            error_log("WB ACF Matching: No good match found for '{$search_term}' (best score: {$best_match_score})");
        } else {
            error_log("WB ACF Matching: Matched '{$search_term}' to '{$matching_product['product_name']}' (score: {$best_match_score})");
        }

        // If we found a matching product, extract the tabs data
        if ($matching_product && isset($matching_product['acf_fields']) && is_array($matching_product['acf_fields'])) {
            // Extract all ACF fields as tabs
            foreach ($matching_product['acf_fields'] as $key => $value) {
                if (!empty($value)) {
                    // Convert snake_case to Title Case
                    $tab_name = ucwords(str_replace('_', ' ', $key));
                    $tabs[$tab_name] = $value;
                }
            }
        }

        return $tabs;
    }

    /**
     * Helper: Upload Image to Media Library
     */
    private function upload_image_to_media_library($image_path, $image_name)
    {
        if (!file_exists($image_path)) {
            $this->debug_log[] = "[ERROR] Image not found: $image_path";
            return false;
        }

        // Check if WordPress functions are available
        if (
            !function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p') ||
            !function_exists('wp_insert_attachment') || !function_exists('wp_generate_attachment_metadata') ||
            !function_exists('wp_update_attachment_metadata') || !defined('ABSPATH')
        ) {
            $this->debug_log[] = "[ERROR] WordPress functions not available for media upload";
            return false;
        }

        $wp_filetype = wp_check_filetype($image_path);
        $upload_dir = wp_upload_dir();
        $filename = basename($image_path);

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir['path'])) {
            wp_mkdir_p($upload_dir['path']);
        }

        $new_path = $upload_dir['path'] . '/' . $filename;

        // Copy file to uploads directory
        if (!copy($image_path, $new_path)) {
            $this->debug_log[] = "[ERROR] Failed to copy file: $image_path to $new_path";
            return false;
        }

        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => function_exists('sanitize_file_name') ? sanitize_file_name($image_name) : preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $image_name),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $new_path);

        if (is_wp_error($attach_id)) {
            $this->debug_log[] = "[ERROR] Failed to insert attachment: " . $attach_id->get_error_message();
            return false;
        }

        // Include the image handler if not already included
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $new_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    public function fuzzy_match_wc_category_product_count()
    {
        // Get all WooCommerce categories
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ]);

        $results = [];
        $updated = 0;
        $skipped = 0;

        foreach ($categories as $category) {
            // Get actual product count
            $actual_count = $category->count;

            // Get products in this category
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $category->term_id,
                    ],
                ],
                'fields' => 'ids',
            ]);

            $real_count = count($products);

            if ($actual_count != $real_count) {
                // Update the count in the database
                global $wpdb;
                $wpdb->update(
                    $wpdb->term_taxonomy,
                    ['count' => $real_count],
                    ['term_taxonomy_id' => $category->term_taxonomy_id],
                    ['%d'],
                    ['%d']
                );
                $updated++;
                $results[] = sprintf(
                    'Updated category "%s" (ID: %d): %d → %d products',
                    $category->name,
                    $category->term_id,
                    $actual_count,
                    $real_count
                );
            } else {
                $skipped++;
            }
        }

        $summary = sprintf(
            '✅ Category product counts updated. Updated: %d, Skipped: %d',
            $updated,
            $skipped
        );

        $this->debug_log = array_merge([$summary], $results, $this->debug_log);
        set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'details' => $results,
        ];
    }

    /**
     * Register dashboard widget
     */
    public function register_image_count_widget()
    {
        if (current_user_can('manage_woocommerce')) {
            wp_add_dashboard_widget(
                'wb_image_count_widget',
                'WB Import Status',
                [$this, 'image_count_dashboard_widget']
            );
        }
    }


    /**
     * Display image count in dashboard widget
     */
    public function image_count_dashboard_widget()
    {
        $folder_path = WP_CONTENT_DIR . '/uploads/wb-products/';
        $image_count = 0;

        if (is_dir($folder_path)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $image_count++;
                }
            }
        }

        echo "<p><strong>Total Images Found in wb-products:</strong> $image_count</p>";

        // Show last import log
        $logs = get_transient('wb_import_debug_log');
        if (!empty($logs)) {
            echo '<div style="margin-top:20px;max-height:300px;overflow:auto;background:#f5f5f5;padding:10px;border:1px solid #ddd;">';
            echo '<h3>Last Import Log</h3>';
            echo '<pre style="white-space:pre-wrap;font-family:monospace;">';
            echo esc_html(implode("\n", (array) $logs));
            echo '</pre>';
            echo '</div>';
        }
    }

    /**
     * Run import process
     */
    private function run_import()
    {
        if (!function_exists('current_user_can') || !current_user_can('manage_woocommerce')) {
            if (function_exists('wp_die')) {
                wp_die('Permission denied');
            }
            return;
        }

        $this->debug_log = [];
        $this->debug_log[] = 'Starting import process at ' . (function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'));

        try {
            // Import knitted products
            $knitted_json = WP_CONTENT_DIR . '/uploads/wb_structure_knitted_products.json';
            if (file_exists($knitted_json)) {
                $json = file_get_contents($knitted_json);
                $raw_structure = json_decode($json, true);
                if (is_array($raw_structure)) {
                    $structured_data = $this->prepare_product_import_data($raw_structure);
                    $this->import_knitted_products($structured_data);
                    $this->debug_log[] = '✅ Knitted products import completed';
                }
            }

            // Import woven products
            $woven_folder = WP_CONTENT_DIR . '/uploads/wb-products/';
            if (is_dir($woven_folder)) {
                $this->import_woven_products($woven_folder);
                $this->debug_log[] = '✅ Woven products import completed';
            }

            // Update category featured images
            $this->update_category_featured_images();
            $this->debug_log[] = '✅ Category featured images updated';

            // Clean up old media
            $this->delete_old_media_except_last_20();
            $this->debug_log[] = '✅ Old media cleanup completed';

        } catch (Exception $e) {
            $this->debug_log[] = '❌ Error during import: ' . $e->getMessage();
        }

        // Save debug log
        if (function_exists('set_transient')) {
            set_transient('wb_import_debug_log', $this->debug_log, HOUR_IN_SECONDS);
        }

        // Redirect back to admin page
        if (function_exists('admin_url') && function_exists('wp_redirect')) {
            wp_redirect(admin_url('admin.php?page=wb-helper&import_complete=1'));
            exit;
        }
    }

    /**
     * Delete old media except last 20
     */
    public function delete_old_media_except_last_20()
    {
        global $wpdb;

        // Get the 20 most recent media uploads
        $recent_media = $wpdb->get_col("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            ORDER BY post_date DESC 
            LIMIT 20
        ");

        if (empty($recent_media)) {
            return;
        }

        // Delete all media except the 20 most recent
        $placeholders = implode(',', array_fill(0, count($recent_media), '%d'));
        $media_to_delete = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND ID NOT IN ($placeholders)
        ", $recent_media));

        if (!empty($media_to_delete)) {
            foreach ($media_to_delete as $media_id) {
                wp_delete_attachment($media_id, true);
            }
            $this->debug_log[] = "Deleted " . count($media_to_delete) . " old media files.";
        }
    }

    /**
     * Add ACF Manager page to admin menu
     */
    public function add_acf_manager_page()
    {
        add_submenu_page(
            'wb-helper',
            'ACF Manager',
            'ACF Manager',
            'manage_woocommerce',
            'wb-acf-manager',
            [$this, 'render_acf_manager_page']
        );
    }

    /**
     * Render ACF Manager page
     */
    public function render_acf_manager_page()
    {
        ?>
        <div class="wrap wb-acf-manager">
            <h1><span class="dashicons dashicons-edit"></span> ACF Data Manager</h1>
            <p class="description">Centralized editing interface for all product category ACF fields</p>

            <div id="acf-manager-loading" style="padding: 40px; text-align: center;">
                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                <p>Loading ACF data...</p>
            </div>

            <div id="acf-manager-content" style="display: none;">
                <!-- Content will be loaded via AJAX -->
            </div>

            <style>
                .wb-acf-manager {
                    max-width: 1400px;
                }

                .acf-category-item {
                    margin-bottom: 20px;
                    border: 1px solid #ccd0d4;
                    background: #fff;
                }

                .acf-category-header {
                    padding: 15px 20px;
                    background: #f6f7f7;
                    border-bottom: 1px solid #ccd0d4;
                    cursor: pointer;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .acf-category-header:hover {
                    background: #f0f0f1;
                }

                .acf-category-title {
                    font-size: 16px;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .acf-category-meta {
                    color: #646970;
                    font-size: 13px;
                }

                .acf-category-body {
                    padding: 20px;
                    display: none;
                }

                .acf-category-item.open .acf-category-body {
                    display: block;
                }

                .acf-field-group {
                    margin-bottom: 20px;
                    padding: 15px;
                    background: #f6f7f7;
                    border-radius: 4px;
                }

                .acf-field-label {
                    font-weight: 600;
                    margin-bottom: 8px;
                    display: block;
                    color: #1d2327;
                }

                .acf-field-control textarea {
                    width: 100%;
                    min-height: 120px;
                    font-family: 'Courier New', monospace;
                    font-size: 13px;
                    padding: 10px;
                    border: 1px solid #8c8f94;
                    border-radius: 4px;
                }

                .acf-field-control textarea:focus {
                    border-color: #2271b1;
                    outline: none;
                    box-shadow: 0 0 0 1px #2271b1;
                }

                .acf-actions {
                    margin-top: 20px;
                    padding-top: 15px;
                    border-top: 1px solid #ccd0d4;
                    display: flex;
                    gap: 10px;
                }

                .acf-save-status {
                    padding: 8px 12px;
                    border-radius: 4px;
                    display: none;
                    align-items: center;
                    gap: 8px;
                }

                .acf-save-status.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .acf-save-status.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .toggle-indicator {
                    transition: transform 0.2s;
                }

                .acf-category-item.open .toggle-indicator {
                    transform: rotate(180deg);
                }
            </style>

            <script>
                jQuery(document).ready(function ($) {
                    // Load ACF data on page load
                    loadACFData();

                    function loadACFData() {
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'wb_get_all_acf_data',
                                _wpnonce: '<?php echo wp_create_nonce('wb_acf_manager'); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    renderACFData(response.data);
                                    $('#acf-manager-loading').hide();
                                    $('#acf-manager-content').show();
                                } else {
                                    $('#acf-manager-loading').html('<p class="error">Failed to load ACF data: ' + (response.data || 'Unknown error') + '</p>');
                                }
                            },
                            error: function () {
                                $('#acf-manager-loading').html('<p class="error">Failed to load ACF data. Please refresh the page.</p>');
                            }
                        });
                    }

                    function renderACFData(data) {
                        let html = '<div class="acf-categories-list">';

                        data.forEach(function (item, index) {
                            html += '<div class="acf-category-item postbox" data-index="' + index + '">';
                            html += '<div class="acf-category-header">';
                            html += '<div class="acf-category-title">';
                            html += '<span class="dashicons dashicons-category"></span>';
                            html += escapeHtml(item.product_name);
                            html += '</div>';
                            html += '<div class="acf-category-meta">';
                            html += '<span class="toggle-indicator dashicons dashicons-arrow-down-alt2"></span>';
                            html += '</div>';
                            html += '</div>';
                            html += '<div class="acf-category-body">';

                            if (item.acf_fields && Object.keys(item.acf_fields).length > 0) {
                                for (let fieldKey in item.acf_fields) {
                                    html += '<div class="acf-field-group">';
                                    html += '<label class="acf-field-label">' + ucwords(fieldKey.replace(/_/g, ' ')) + '</label>';
                                    html += '<div class="acf-field-control">';
                                    html += '<textarea class="acf-field-textarea" data-field="' + fieldKey + '">' + escapeHtml(item.acf_fields[fieldKey]) + '</textarea>';
                                    html += '</div>';
                                    html += '</div>';
                                }

                                html += '<div class="acf-actions">';
                                html += '<button type="button" class="button button-primary save-category" data-category-name="' + escapeHtml(item.product_name) + '">Save Changes</button>';
                                html += '<div class="acf-save-status"></div>';
                                html += '</div>';
                            } else {
                                html += '<p>No ACF fields defined for this category.</p>';
                            }

                            html += '</div>';
                            html += '</div>';
                        });

                        html += '</div>';
                        $('#acf-manager-content').html(html);

                        // Bind event handlers
                        bindEventHandlers();
                    }

                    function bindEventHandlers() {
                        // Toggle category accordion
                        $(document).on('click', '.acf-category-header', function () {
                            $(this).closest('.acf-category-item').toggleClass('open');
                        });

                        // Save category data
                        $(document).on('click', '.save-category', function () {
                            let $btn = $(this);
                            let $container = $btn.closest('.acf-category-item');
                            let categoryName = $btn.data('category-name');
                            let $status = $container.find('.acf-save-status');

                            // Collect field data
                            let fields = {};
                            $container.find('.acf-field-textarea').each(function () {
                                let fieldName = $(this).data('field');
                                fields[fieldName] = $(this).val();
                            });

                            // Save via AJAX
                            $btn.prop('disabled', true).text('Saving...');
                            $status.hide();

                            $.ajax({
                                url: ajaxurl,
                                method: 'POST',
                                data: {
                                    action: 'wb_save_acf_data',
                                    _wpnonce: '<?php echo wp_create_nonce('wb_acf_save'); ?>',
                                    category_name: categoryName,
                                    fields: fields
                                },
                                success: function (response) {
                                    $btn.prop('disabled', false).text('Save Changes');

                                    if (response.success) {
                                        $status.removeClass('error').addClass('success')
                                            .html('<span class="dashicons dashicons-yes"></span> Saved successfully!')
                                            .show();
                                        setTimeout(function () { $status.fadeOut(); }, 3000);
                                    } else {
                                        $status.removeClass('success').addClass('error')
                                            .html('<span class="dashicons dashicons-warning"></span> Error: ' + (response.data || 'Unknown error'))
                                            .show();
                                    }
                                },
                                error: function () {
                                    $btn.prop('disabled', false).text('Save Changes');
                                    $status.removeClass('success').addClass('error')
                                        .html('<span class="dashicons dashicons-warning"></span> Save failed. Please try again.')
                                        .show();
                                }
                            });
                        });
                    }

                    function escapeHtml(text) {
                        if (!text) return '';
                        let map = {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        };
                        return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
                    }

                    function ucwords(str) {
                        return str.replace(/\b\w/g, function (l) { return l.toUpperCase(); });
                    }
                });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX handler to get all ACF data
     */
    public function ajax_get_all_acf_data()
    {
        check_ajax_referer('wb_acf_manager', '_wpnonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        // Get ACF data file
        $possible_paths = [
            ABSPATH . 'wp-content/uploads/woocommerce_acf_data_with_categories.json',
            WP_CONTENT_DIR . '/uploads/woocommerce_acf_data_with_categories.json'
        ];

        $json_path = '';
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $json_path = $path;
                break;
            }
        }

        if (empty($json_path)) {
            wp_send_json_error('ACF data file not found');
            return;
        }

        $json_data = file_get_contents($json_path);
        $acf_data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data: ' . json_last_error_msg());
            return;
        }

        wp_send_json_success($acf_data);
    }

    /**
     * AJAX handler to save ACF data
     */
    public function ajax_save_acf_data()
    {
        check_ajax_referer('wb_acf_save', '_wpnonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }

        $category_name = sanitize_text_field($_POST['category_name'] ?? '');
        $fields = $_POST['fields'] ?? [];

        if (empty($category_name) || !is_array($fields)) {
            wp_send_json_error('Invalid data provided');
            return;
        }

        // Get ACF data file
        $possible_paths = [
            ABSPATH . 'wp-content/uploads/woocommerce_acf_data_with_categories.json',
            WP_CONTENT_DIR . '/uploads/woocommerce_acf_data_with_categories.json'
        ];

        $json_path = '';
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $json_path = $path;
                break;
            }
        }

        if (empty($json_path)) {
            wp_send_json_error('ACF data file not found');
            return;
        }

        // Read current data
        $json_data = file_get_contents($json_path);
        $acf_data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid JSON data');
            return;
        }

        // Find and update the category
        $found = false;
        foreach ($acf_data as &$item) {
            if ($item['product_name'] === $category_name) {
                // Sanitize and update fields
                foreach ($fields as $key => $value) {
                    $item['acf_fields'][$key] = wp_kses_post($value);
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            wp_send_json_error('Category not found');
            return;
        }

        // Save back to file
        $result = file_put_contents($json_path, json_encode($acf_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($result === false) {
            wp_send_json_error('Failed to save file');
            return;
        }

        wp_send_json_success('ACF data saved successfully');
    }


    /**
     * Get product ID from structure item
     */
    private function get_product_id_from_structure_item($product)
    {
        if (!empty($product['variations'])) {
            foreach ($product['variations'] as $variation) {
                // Try featured image
                if (!empty($variation['featured'])) {
                    $image_path = $product['folder_path'] . '/' . $variation['name'] . '/' . $variation['featured'];
                    $tracked = $this->find_product_by_image($image_path);
                    if ($tracked)
                        return $tracked->product_id;
                }
                // Try gallery images
                if (!empty($variation['gallery'])) {
                    foreach ($variation['gallery'] as $image) {
                        $image_path = $product['folder_path'] . '/' . $variation['name'] . '/' . $image;
                        $tracked = $this->find_product_by_image($image_path);
                        if ($tracked)
                            return $tracked->product_id;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * AJAX handler to save product price
     */
    public function ajax_save_price()
    {
        check_ajax_referer('wb_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $price = isset($_POST['price']) ? sanitize_text_field($_POST['price']) : '';

        if (!$product_id) {
            wp_send_json_error('Invalid Product ID');
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Product not found');
        }

        $count = 0;

        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variation->set_regular_price($price);
                    $variation->set_price($price);
                    $variation->save();
                    $count++;
                }
            }
            wp_send_json_success("Updated $count variations");
        } else {
            // Handle simple product too just in case
            $product->set_regular_price($price);
            $product->set_price($price);
            $product->save();
            wp_send_json_success("Updated product price");
        }
    }
}

// Activation and deactivation hooks are registered in the main plugin file
register_deactivation_hook(__FILE__, ['WB_Product_Importer', 'deactivate']);