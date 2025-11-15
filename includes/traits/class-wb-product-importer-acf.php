<?php
/**
 * ACF import functionality for WB Product Importer
 */
trait WB_Product_Importer_ACF {
    /**
     * Import ACF data for products
     *
     * @param string $file_path Path to the ACF JSON file
     * @return array Results of the import
     */
    public function import_acf_data($file_path) {
        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'message' => "ACF JSON file not found at {$file_path}"
            ];
        }

        $data = json_decode(file_get_contents($file_path), true);
        if (!$data) {
            return [
                'success' => false,
                'message' => 'Error: Invalid JSON format in ACF data file.'
            ];
        }

        $results = [
            'imported' => 0,
            'skipped' => 0,
            'unmatched_categories' => [],
            'matched_categories' => []
        ];

        // Get the category mapping
        $category_map = $this->get_category_mapping();
        $processed_categories = [];

        foreach ($data as $item) {
            $raw_category = $item['category'] ?? '';
            
            // Get the mapped category name
            $category = $this->get_mapped_category($raw_category, $category_map);
            
            if (!$category) {
                if (!in_array($raw_category, $results['unmatched_categories'])) {
                    $results['unmatched_categories'][] = $raw_category;
                }
                $results['skipped']++;
                continue;
            }

            // Track matched categories
            if (!in_array($category, $results['matched_categories'])) {
                $results['matched_categories'][] = $category;
            }

            // Get products in this category
            $products = $this->get_products_by_category($category);
            
            if (empty($products)) {
                $this->log_error("No products found in category: {$category}");
                continue;
            }

            $acf_fields = $item['acf_fields'] ?? [];
            $field_keys = array_keys($acf_fields);

            foreach ($products as $product_id) {
                // Clear existing ACF fields
                $this->clear_acf_fields($product_id, $field_keys);
                
                // Update ACF fields
                $this->update_acf_fields($product_id, $acf_fields);
                
                $results['imported']++;
            }
        }

        // Sort results for better readability
        sort($results['unmatched_categories']);
        sort($results['matched_categories']);

        return $results;
    }

    /**
     * Get the category mapping
     * 
     * @return array Category mapping
     */
    private function get_category_mapping() {
        return [
            // Single mapping categories
            'Corduroy Fabric' => 'Corduroy Fabric',
            'Cotton Batik Print Fabrics' => 'Cotton Batik Print Fabrics',
            'Cotton Batik Print Fabric' => 'Cotton Batik Print Fabrics',
            'Cotton Chambray Fabrics' => 'Cotton Chambray Fabrics',
            'Cotton Greige Fabrics' => 'Cotton Greige Fabrics',
            'Cotton Heavy Twill Yarn Dyed Fabrics' => 'Cotton Heavy Twill Yarn Dyed Fabrics',
            'Cotton Heavy Twill Yarn dyed fabric' => 'Cotton Heavy Twill Yarn Dyed Fabrics',
            'Cotton Madras Checks' => 'Cotton Madras Checks',
            'Cotton Madras Checks 2' => 'Cotton Madras Checks 2',
            'Cotton Madras Checks Over Dyed' => 'Cotton Madras Checks Over Dyed',
            'Cotton Organic Yarn Dyed Checks fabrics - Madras Check' => 'Cotton Organic Yarn Dyed Checks fabrics - Madras Check',
            'Cotton Organic Yarn Dyed Checks fabric - Madras Check' => 'Cotton Organic Yarn Dyed Checks fabrics - Madras Check',
            'Cotton Patch Work' => 'Cotton Patch Work',
            'Cotton Patchwork 2' => 'Cotton Patchwork 2',
            'Cotton Pintuck Fabrics' => 'Cotton Pintuck Fabrics',
            'Cotton Print Patchwork' => 'Cotton Print Patchwork',
            'Cotton Print Patchwork Fabric' => 'Cotton Print Patchwork',
            'Cotton Voile fabrics' => 'Cotton Voile fabrics',
            'Cotton Voile fabric' => 'Cotton Voile fabrics',
            'Cotton Yarn dyed Flannel' => 'Cotton Yarn dyed Flannel',
            'Cotton Yarn dyed over print' => 'Cotton Yarn dyed over print',
            'Lenin Woven Fabrics' => 'Lenin Woven Fabrics',
            'Patchwork Fabrics' => 'Patchwork Fabrics',
            'Print Fabrics' => 'Print Fabrics',
            
            // Handle duplicate categories with different slugs
            'Cotton Corduroy Patchwork' => 'Cotton Corduroy Patchwork',
            'Cotton Corduroy Patchwork (Patchwork Fabrics)' => 'Cotton Corduroy Patchwork',
            'Cotton Denim Patchwork' => 'Cotton Denim Patchwork',
            'Cotton Denim Patchwork (Patchwork)' => 'Cotton Denim Patchwork',
            'Cotton Fancy Patchwork' => 'Cotton Fancy Patchwork',
            'Cotton Fancy Patchwork (Patchwork)' => 'Cotton Fancy Patchwork',
            'Cotton Flannel Patch work' => 'Cotton Flannel Patch work',
            'Cotton Flannel Patch work (Patchwork)' => 'Cotton Flannel Patch work',
            
            // Default fallback
            'Uncategorized' => 'Cotton Print Patchwork'
        ];
    }

    /**
     * Get the mapped category name
     * 
     * @param string $raw_category Raw category name
     * @param array $category_map Category mapping
     * @return string|null Mapped category name or null if not found
     */
    private function get_mapped_category($raw_category, $category_map) {
        // Try exact match first
        if (isset($category_map[$raw_category])) {
            return $category_map[$raw_category];
        }
        
        // Try case-insensitive match
        $lower_raw = strtolower(trim($raw_category));
        foreach ($category_map as $key => $value) {
            if (strtolower(trim($key)) === $lower_raw) {
                return $value;
            }
        }
        
        return null;
    }

    /**
     * Get products by category
     * 
     * @param string $category Category name
     * @return array Array of product IDs
     */
    private function get_products_by_category($category) {
        $query = new WP_Query([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'tax_query'      => [
                'relation' => 'OR',
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'name',
                    'terms'    => $category,
                ],
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => sanitize_title($category),
                ],
            ],
            'fields' => 'ids',
        ]);

        return $query->posts ?: [];
    }

    /**
     * Clear ACF fields for a product
     * 
     * @param int $product_id Product ID
     * @param array $field_keys Array of ACF field keys
     */
    private function clear_acf_fields($product_id, $field_keys) {
        foreach ($field_keys as $key) {
            if (function_exists('delete_field')) {
                delete_field($key, $product_id);
            } else {
                delete_post_meta($product_id, $key);
                delete_post_meta($product_id, '_' . $key);
            }
        }
    }

    /**
     * Update ACF fields for a product
     * 
     * @param int $product_id Product ID
     * @param array $acf_fields Array of ACF fields
     */
    private function update_acf_fields($product_id, $acf_fields) {
        foreach ($acf_fields as $key => $value) {
            if (function_exists('update_field')) {
                update_field($key, $value, $product_id);
            } else {
                update_post_meta($product_id, $key, $value);
            }
        }
    }
}
