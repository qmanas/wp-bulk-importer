<?php
/**
 * AJAX handlers for WB Product Importer
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WB_Ajax_Handlers {
    /**
     * @var WB_Product_Importer
     */
    private $importer;

    /**
     * Constructor
     *
     * @param WB_Product_Importer $importer The main plugin class instance
     */
    public function __construct($importer) {
        $this->importer = $importer;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_wb_import_products', [$this, 'ajax_import_products']);
        add_action('wp_ajax_wb_import_acf_data', [$this, 'ajax_import_acf_data']);
    }

    /**
     * AJAX handler for importing products
     */
    public function ajax_import_products() {
        check_ajax_referer('wb_import_products', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Handle product import logic here
        wp_send_json_success('Product import functionality will be implemented here');
    }

    /**
     * AJAX handler for importing ACF data
     */
    public function ajax_import_acf_data() {
        check_ajax_referer('wb_import_acf_data', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $file_path = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_send_json_error('Invalid file path');
        }
        
        // Call the import_acf_data method from the main class
        $results = $this->importer->import_acf_data($file_path);
        
        if ($results['success']) {
            $message = '✅ ACF data imported successfully!<br>';
            $message .= 'Imported to ' . $results['imported'] . ' products.<br>';
            $message .= 'Matched categories: ' . implode(', ', $results['matched_categories']) . '<br>';
            
            if (!empty($results['unmatched_categories'])) {
                $message .= '<br><strong>Unmatched categories:</strong><br>' . 
                           implode('<br>', $results['unmatched_categories']);
            }
            
            wp_send_json_success($message);
        } else {
            wp_send_json_error($results['message']);
        }
    }
}
