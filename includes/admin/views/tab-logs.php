<?php if (!defined('ABSPATH')) exit; ?>

<div class="wb-logs-tab">
    <h2><?php esc_html_e('Import Logs', 'wb-importer'); ?></h2>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('Recent Log Entries', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <div class="wb-log-filters" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div>
                        <label for="log-level-filter" class="wb-form-label"><?php esc_html_e('Log Level', 'wb-importer'); ?></label>
                        <select id="log-level-filter" class="wb-form-control" style="width: 150px;">
                            <option value="all"><?php esc_html_e('All Levels', 'wb-importer'); ?></option>
                            <option value="info"><?php esc_html_e('Info', 'wb-importer'); ?></option>
                            <option value="warning"><?php esc_html_e('Warning', 'wb-importer'); ?></option>
                            <option value="error"><?php esc_html_e('Error', 'wb-importer'); ?></option>
                            <option value="success"><?php esc_html_e('Success', 'wb-importer'); ?></option>
                        </select>
                    </div>
                    <div>
                        <label for="log-date-filter" class="wb-form-label"><?php esc_html_e('Date Range', 'wb-importer'); ?></label>
                        <select id="log-date-filter" class="wb-form-control" style="width: 200px;">
                            <option value="today"><?php esc_html_e('Today', 'wb-importer'); ?></option>
                            <option value="yesterday"><?php esc_html_e('Yesterday', 'wb-importer'); ?></option>
                            <option value="week" selected><?php esc_html_e('Last 7 Days', 'wb-importer'); ?></option>
                            <option value="month"><?php esc_html_e('Last 30 Days', 'wb-importer'); ?></option>
                            <option value="all"><?php esc_html_e('All Time', 'wb-importer'); ?></option>
                        </select>
                    </div>
                    <div style="margin-top: 22px;">
                        <button type="button" id="wb-apply-log-filter" class="wb-btn wb-btn-primary">
                            <?php esc_html_e('Apply Filters', 'wb-importer'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="wb-log-entries">
                <div id="wb-log-entries-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                    <p class="description" style="text-align: center; padding: 20px; color: #666;">
                        <?php esc_html_e('No log entries found.', 'wb-importer'); ?>
                    </p>
                </div>
                
                <div class="wb-log-actions" style="margin-top: 15px; display: flex; gap: 10px;">
                    <button type="button" id="wb-clear-logs" class="wb-btn wb-btn-secondary">
                        <?php esc_html_e('Clear All Logs', 'wb-importer'); ?>
                    </button>
                    <button type="button" id="wb-export-logs" class="wb-btn wb-btn-secondary">
                        <?php esc_html_e('Export Logs', 'wb-importer'); ?>
                    </button>
                    <div style="margin-left: auto;">
                        <span class="spinner" id="wb-logs-spinner" style="float: none; margin-top: 5px;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="wb-card">
        <div class="wb-card-header">
            <h2><?php esc_html_e('Debug Information', 'wb-importer'); ?></h2>
        </div>
        <div class="wb-card-body">
            <p>
                <?php esc_html_e('Use this section to help with troubleshooting. The debug log contains detailed information about plugin operations.', 'wb-importer'); ?>
            </p>
            
            <div class="wb-debug-actions" style="margin: 15px 0;">
                <button type="button" id="wb-enable-debug" class="wb-btn wb-btn-secondary">
                    <?php esc_html_e('Enable Debug Mode', 'wb-importer'); ?>
                </button>
                <button type="button" id="wb-view-debug-log" class="wb-btn wb-btn-secondary">
                    <?php esc_html_e('View Debug Log', 'wb-importer'); ?>
                </button>
            </div>
            
            <div id="wb-debug-log-container" style="display: none; margin-top: 20px;">
                <textarea id="wb-debug-log" class="wb-log-area" style="height: 300px;" readonly></textarea>
            </div>
        </div>
    </div>
</div>
