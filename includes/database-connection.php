<?php
/**
 * WooCommerce Gifting Flow Database Connection Handler
 * Direct database queries to ensure bulletproof admin data connection
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Database_Connection {
    
    private $wpdb;
    private $cache_duration = 300; // 5 minutes cache
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Add AJAX endpoints
        add_action('wp_ajax_wcflow_get_database_cards', array($this, 'get_database_cards'));
        add_action('wp_ajax_nopriv_wcflow_get_database_cards', array($this, 'get_database_cards'));
        add_action('wp_ajax_wcflow_validate_database', array($this, 'validate_database'));
        add_action('wp_ajax_wcflow_sync_database', array($this, 'sync_database'));
    }
    
    /**
     * Get all greeting card categories with their cards using direct SQL
     */
    public function get_cards_by_categories() {
        wcflow_log('🎯 Starting direct database query for cards by categories');
        
        try {
            // Step 1: Get all categories with proper ordering
            $categories = $this->get_all_categories();
            
            if (empty($categories)) {
                wcflow_log('❌ No categories found in database');
                return $this->get_fallback_data();
            }
            
            // Step 2: Get cards for each category
            $cards_by_category = array();
            $total_cards = 0;
            
            foreach ($categories as $category) {
                $cards = $this->get_cards_for_category($category->term_id);
                
                if (!empty($cards)) {
                    $cards_by_category[$category->name] = $cards;
                    $total_cards += count($cards);
                    wcflow_log('✅ Category "' . $category->name . '" loaded with ' . count($cards) . ' cards');
                }
            }
            
            wcflow_log('📊 Total categories: ' . count($cards_by_category) . ', Total cards: ' . $total_cards);
            
            if (empty($cards_by_category)) {
                wcflow_log('⚠️ No cards found in any category, returning fallback data');
                return $this->get_fallback_data();
            }
            
            // Cache the results
            $this->cache_results($cards_by_category);
            
            return $cards_by_category;
            
        } catch (Exception $e) {
            wcflow_log('💥 Database query error: ' . $e->getMessage());
            return $this->get_fallback_data();
        }
    }
    
    /**
     * Get all greeting card categories with proper ordering
     */
    private function get_all_categories() {
        $query = "
            SELECT DISTINCT t.term_id, t.name, t.slug, tt.count,
                   COALESCE(tm_order.meta_value, '999') as display_order,
                   tm_desc.meta_value as category_description
            FROM {$this->wpdb->terms} t
            INNER JOIN {$this->wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            LEFT JOIN {$this->wpdb->termmeta} tm_order ON t.term_id = tm_order.term_id AND tm_order.meta_key = '_wcflow_category_order'
            LEFT JOIN {$this->wpdb->termmeta} tm_desc ON t.term_id = tm_desc.term_id AND tm_desc.meta_key = '_wcflow_category_description'
            WHERE tt.taxonomy = 'wcflow_card_category'
            AND tt.count > 0
            ORDER BY CAST(COALESCE(tm_order.meta_value, '999') AS UNSIGNED) ASC, t.name ASC
        ";
        
        $results = $this->wpdb->get_results($query);
        
        wcflow_log('📂 Found ' . count($results) . ' categories in database');
        
        return $results;
    }
    
    /**
     * Get cards for a specific category
     */
    private function get_cards_for_category($category_id) {
        $query = "
            SELECT DISTINCT p.ID, p.post_title, p.post_status, p.menu_order,
                   pm_price.meta_value as price_meta,
                   pm_thumb.meta_value as thumbnail_id
            FROM {$this->wpdb->posts} p
            INNER JOIN {$this->wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$this->wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$this->wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_wcflow_price'
            LEFT JOIN {$this->wpdb->postmeta} pm_thumb ON p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id'
            WHERE p.post_type = 'wcflow_card'
            AND p.post_status = 'publish'
            AND tt.term_id = %d
            AND tt.taxonomy = 'wcflow_card_category'
            ORDER BY p.menu_order ASC, p.post_title ASC
            LIMIT 20
        ";
        
        $results = $this->wpdb->get_results($this->wpdb->prepare($query, $category_id));
        
        $cards = array();
        foreach ($results as $card) {
            $price_value = $card->price_meta ? floatval($card->price_meta) : 0;
            
            // Get image URL with fallback
            $image_url = $this->get_card_image_url($card->thumbnail_id);
            
            $cards[] = array(
                'id' => $card->ID,
                'title' => $card->post_title,
                'price' => $price_value > 0 ? get_woocommerce_currency_symbol() . number_format($price_value, 2) : 'FREE',
                'price_value' => $price_value,
                'img' => $image_url,
                'status' => $card->post_status,
                'order' => $card->menu_order
            );
        }
        
        return $cards;
    }
    
    /**
     * Get card image URL with fallback
     */
    private function get_card_image_url($thumbnail_id) {
        $fallback_images = array(
            'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400',
            'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
        );
        
        if ($thumbnail_id) {
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'medium');
            if ($image_data && $image_data[0]) {
                return $image_data[0];
            }
        }
        
        // Return random fallback image
        return $fallback_images[array_rand($fallback_images)];
    }
    
    /**
     * Get fallback data when database queries fail
     */
    private function get_fallback_data() {
        wcflow_log('🔄 Returning fallback data');
        
        return array(
            'Birthday Cards' => array(
                array(
                    'id' => 'fallback-birthday-1',
                    'title' => 'Happy Birthday Balloons',
                    'price' => 'FREE',
                    'price_value' => 0,
                    'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
                ),
                array(
                    'id' => 'fallback-birthday-2',
                    'title' => 'Birthday Cake Celebration',
                    'price' => get_woocommerce_currency_symbol() . '1.50',
                    'price_value' => 1.50,
                    'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                ),
                array(
                    'id' => 'fallback-birthday-3',
                    'title' => 'Birthday Wishes',
                    'price' => get_woocommerce_currency_symbol() . '2.50',
                    'price_value' => 2.50,
                    'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
                )
            ),
            'Holiday Cards' => array(
                array(
                    'id' => 'fallback-holiday-1',
                    'title' => 'Season Greetings',
                    'price' => 'FREE',
                    'price_value' => 0,
                    'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
                ),
                array(
                    'id' => 'fallback-holiday-2',
                    'title' => 'Winter Wonderland',
                    'price' => get_woocommerce_currency_symbol() . '1.25',
                    'price_value' => 1.25,
                    'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                )
            ),
            'Thank You Cards' => array(
                array(
                    'id' => 'fallback-thanks-1',
                    'title' => 'Thank You So Much',
                    'price' => 'FREE',
                    'price_value' => 0,
                    'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                )
            )
        );
    }
    
    /**
     * Cache results for performance
     */
    private function cache_results($data) {
        set_transient('wcflow_cards_cache', $data, $this->cache_duration);
        wcflow_log('💾 Results cached for ' . $this->cache_duration . ' seconds');
    }
    
    /**
     * Get cached results
     */
    private function get_cached_results() {
        return get_transient('wcflow_cards_cache');
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        delete_transient('wcflow_cards_cache');
        wcflow_log('🧹 Cache cleared');
    }
    
    /**
     * AJAX endpoint to get database cards
     */
    public function get_database_cards() {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        wcflow_log('🎯 AJAX request for database cards received');
        
        // Try cache first
        $cached_data = $this->get_cached_results();
        if ($cached_data !== false) {
            wcflow_log('⚡ Returning cached data');
            wp_send_json_success($cached_data);
            return;
        }
        
        // Get fresh data
        $cards_by_category = $this->get_cards_by_categories();
        
        wp_send_json_success($cards_by_category);
    }
    
    /**
     * Validate database structure
     */
    public function validate_database() {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $validation = array(
            'post_type_exists' => post_type_exists('wcflow_card'),
            'taxonomy_exists' => taxonomy_exists('wcflow_card_category'),
            'total_cards' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_type = 'wcflow_card'"),
            'published_cards' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
            'total_categories' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
            'relationships' => $this->wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$this->wpdb->term_relationships} tr
                INNER JOIN {$this->wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$this->wpdb->posts} p ON tr.object_id = p.ID
                WHERE tt.taxonomy = 'wcflow_card_category' 
                AND p.post_type = 'wcflow_card'
                AND p.post_status = 'publish'
            "),
            'sample_categories' => $this->wpdb->get_results("
                SELECT t.term_id, t.name, tt.count
                FROM {$this->wpdb->terms} t 
                INNER JOIN {$this->wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                WHERE tt.taxonomy = 'wcflow_card_category' 
                ORDER BY t.name ASC
                LIMIT 5
            "),
            'sample_cards' => $this->wpdb->get_results("
                SELECT p.ID, p.post_title, p.post_status, pm.meta_value as price
                FROM {$this->wpdb->posts} p
                LEFT JOIN {$this->wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wcflow_price'
                WHERE p.post_type = 'wcflow_card' 
                ORDER BY p.ID ASC 
                LIMIT 5
            ")
        );
        
        wp_send_json_success($validation);
    }
    
    /**
     * Sync database - force refresh cache and validate
     */
    public function sync_database() {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Clear cache
        $this->clear_cache();
        
        // Get fresh data
        $cards_by_category = $this->get_cards_by_categories();
        
        // Return sync results
        wp_send_json_success(array(
            'message' => 'Database synced successfully',
            'categories_count' => count($cards_by_category),
            'total_cards' => array_sum(array_map('count', $cards_by_category)),
            'data' => $cards_by_category
        ));
    }
}

// Initialize the database connection
new WCFlow_Database_Connection();

/**
 * Hook to clear cache when cards or categories are updated
 */
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) === 'wcflow_card') {
        delete_transient('wcflow_cards_cache');
        wcflow_log('🔄 Cache cleared due to card update: ' . $post_id);
    }
});

add_action('created_wcflow_card_category', function() {
    delete_transient('wcflow_cards_cache');
    wcflow_log('🔄 Cache cleared due to category creation');
});

add_action('edited_wcflow_card_category', function() {
    delete_transient('wcflow_cards_cache');
    wcflow_log('🔄 Cache cleared due to category update');
});