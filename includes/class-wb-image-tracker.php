<?php
/**
 * WB Image Tracker
 *
 * Handles tracking of images and their relationships with products
 */
class WB_Image_Tracker {
    /**
     * @var string Database table name
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wb_image_tracking';
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
    public function track_image($image_path, $product_id, $variation_id = 0, $is_featured = false) {
        global $wpdb;
        
        $original_filename = basename($image_path);
        $relative_path = str_replace(ABSPATH, '', $image_path);
        
        // Check if this image is already tracked
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, product_id, variation_id FROM {$this->table_name} WHERE image_path = %s",
            $relative_path
        ));
        
        if ($existing) {
            // Update existing record if product/variation changed
            if ($existing->product_id != $product_id || $existing->variation_id != $variation_id) {
                $wpdb->update(
                    $this->table_name,
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
            $this->table_name,
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
    public function find_product_by_image($image_path) {
        global $wpdb;
        
        $relative_path = str_replace(ABSPATH, '', $image_path);
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE image_path = %s",
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
    public function get_product_images($product_id, $variation_id = 0) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE product_id = %d",
            $product_id
        );
        
        if ($variation_id) {
            $query .= $wpdb->prepare(" AND variation_id = %d", $variation_id);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Delete tracking for an image
     * 
     * @param string $image_path Full server path to the image
     * @return bool Whether the deletion was successful
     */
    public function delete_image_tracking($image_path) {
        global $wpdb;
        
        $relative_path = str_replace(ABSPATH, '', $image_path);
        
        return (bool) $wpdb->delete(
            $this->table_name,
            array('image_path' => $relative_path),
            array('%s')
        );
    }
    
    /**
     * Get all images in the system
     * 
     * @param array $args Query arguments
     * @return array Array of image records
     */
    public function get_all_images($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'date_added',
            'order' => 'DESC',
            'search' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM {$this->table_name}";
        $where = array();
        $query_args = array();
        
        if (!empty($args['search'])) {
            $where[] = "(image_path LIKE %s OR original_filename LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        
        // Add order by and limit
        $query .= $wpdb->prepare(
            " ORDER BY %s %s LIMIT %d OFFSET %d",
            $args['orderby'],
            $args['order'],
            $args['per_page'],
            $offset
        );
        
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get total count of tracked images
     * 
     * @return int Total count
     */
    public function get_total_images() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }
    
    /**
     * Create the database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wb_image_tracking';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            image_path varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            product_id bigint(20) NOT NULL,
            variation_id bigint(20) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            date_added datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY image_path (image_path),
            KEY product_id (product_id),
            KEY variation_id (variation_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
