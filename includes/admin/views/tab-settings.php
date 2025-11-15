<?php if (!defined('ABSPATH')) exit; ?>

<div class="wb-settings-tab">
    <h2><?php esc_html_e('Plugin Settings', 'wb-importer'); ?></h2>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('General Settings', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <form method="post" action="options.php">
                <?php 
                settings_fields('wb_importer_settings');
                do_settings_sections('wb-importer-settings');
                submit_button(__('Save Settings', 'wb-importer'));
                ?>
            </form>
        </div>
    </div>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('System Information', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <div class="wb-system-info">
                <table class="widefat striped">
                    <tbody>
                        <tr>
                            <th style="width: 200px;"><?php esc_html_e('Plugin Version', 'wb-importer'); ?></th>
                            <td><?php echo esc_html(WB_PRODUCT_IMPORTER_VERSION); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('WordPress Version', 'wb-importer'); ?></th>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('PHP Version', 'wb-importer'); ?></th>
                            <td><?php echo esc_html(phpversion()); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('WooCommerce Active', 'wb-importer'); ?></th>
                            <td><?php echo class_exists('WooCommerce') ? '✅' : '❌'; ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Uploads Directory Writable', 'wb-importer'); ?></th>
                            <td><?php echo wp_is_writable(WP_CONTENT_DIR . '/uploads/') ? '✅' : '❌'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
