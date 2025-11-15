<?php if (!defined('ABSPATH')) exit; ?>

<div class="wb-import-tab">
    <h2><?php esc_html_e('Import Products', 'wb-importer'); ?></h2>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('Import from Directory', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <form id="wb-import-form" class="wb-import-form">
                <?php wp_nonce_field('wb_import_products', 'wb_import_nonce'); ?>
                
                <div class="wb-form-group">
                    <label for="import-directory" class="wb-form-label">
                        <?php esc_html_e('Source Directory', 'wb-importer'); ?>
                    </label>
                    <input type="text" 
                           id="import-directory" 
                           name="import_directory" 
                           class="wb-form-control" 
                           value="<?php echo esc_attr(get_option('wb_importer_default_import_dir', '')); ?>">
                    <p class="description">
                        <?php esc_html_e('Enter the full path to the directory containing product data.', 'wb-importer'); ?>
                    </p>
                </div>
                
                <div class="wb-form-group">
                    <label class="wb-form-label"><?php esc_html_e('Import Options', 'wb-importer'); ?></label>
                    <div>
                        <label>
                            <input type="checkbox" name="import_images" value="1" checked>
                            <?php esc_html_e('Import product images', 'wb-importer'); ?>
                        </label>
                    </div>
                    <div>
                        <label>
                            <input type="checkbox" name="update_existing" value="1" checked>
                            <?php esc_html_e('Update existing products', 'wb-importer'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="wb-form-group">
                    <button type="submit" class="wb-btn wb-btn-primary" id="wb-start-import">
                        <span class="dashicons dashicons-update wb-spinner" style="display: none;"></span>
                        <?php esc_html_e('Start Import', 'wb-importer'); ?>
                    </button>
                    <span class="spinner" id="wb-import-spinner"></span>
                </div>
            </form>
        </div>
    </div>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('Import Log', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <div class="wb-log-wrapper">
                <textarea id="wb-import-log" class="wb-log-area" readonly></textarea>
            </div>
            <div class="wb-log-actions" style="margin-top: 15px;">
                <button type="button" id="wb-clear-log" class="wb-btn wb-btn-secondary">
                    <?php esc_html_e('Clear Log', 'wb-importer'); ?>
                </button>
                <button type="button" id="wb-download-log" class="wb-btn wb-btn-secondary">
                    <?php esc_html_e('Download Log', 'wb-importer'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
