<?php
/**
 * Assets handling trait for WB Product Importer
 */
trait WB_Product_Importer_Assets {
    /**
     * Enqueue admin styles and scripts
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_wb-helper' !== $hook) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-components');
        
        wp_enqueue_style(
            'wb-admin-styles',
            WB_PRODUCT_IMPORTER_URL . 'assets/css/admin.css',
            array('wp-components'),
            WB_PRODUCT_IMPORTER_VERSION
        );
        
        // Add inline styles
        $this->add_inline_styles();
        
        wp_enqueue_script(
            'wb-admin-scripts',
            WB_PRODUCT_IMPORTER_URL . 'assets/js/admin.js',
            array('jquery', 'wp-element', 'wp-components', 'wp-api-fetch'),
            WB_PRODUCT_IMPORTER_VERSION,
            true
        );
        
        wp_localize_script('wb-admin-scripts', 'wbAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wb_admin_nonce'),
            'restUrl' => esc_url_raw(rest_url('wb/v1/')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'i18n' => array(
                'confirmImport' => __('Are you sure you want to import products? This might take a while for large directories.', 'wb-importer'),
                'importing' => __('Importing...', 'wb-importer'),
                'importComplete' => __('Import complete!', 'wb-importer'),
                'error' => __('An error occurred. Please check the logs for more details.', 'wb-importer')
            )
        ));
    }

    /**
     * Add inline styles
     */
    private function add_inline_styles() {
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
    }
}
