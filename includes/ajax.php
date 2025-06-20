<?php
/**
 * WooCommerce Gifting Flow AJAX Handler - FIXED VERSION
 * Handles all AJAX requests for the gifting flow
 */

if (!defined('ABSPATH')) exit;

class WCFlow_AJAX_Handler {
    
    public function __construct() {
        add_action('wp_ajax_wcflow_start_flow', array($this, 'start_flow'));
        add_action('wp_ajax_nopriv_wcflow_start_flow', array($this, 'start_flow'));
        add_action('wp_ajax_wcflow_get_step', array($this, 'get_step'));
        add_action('wp_ajax_nopriv_wcflow_get_step', array($this, 'get_step'));
        add_action('wp_ajax_wcflow_get_addons', array($this, 'get_addons_data'));
        add_action('wp_ajax_nopriv_wcflow_get_addons', array($this, 'get_addons_data'));
        add_action('wp_ajax_wcflow_get_cards', array($this, 'get_cards_data'));
        add_action('wp_ajax_nopriv_wcflow_get_cards', array($this, 'get_cards_data'));
        add_action('wp_ajax_wcflow_get_shipping_methods', array($this, 'get_shipping_methods'));
        add_action('wp_ajax_nopriv_wcflow_get_shipping_methods', array($this, 'get_shipping_methods'));
        add_action('wp_ajax_wcflow_get_cart_summary', array($this, 'get_cart_summary'));
        add_action('wp_ajax_nopriv_wcflow_get_cart_summary', array($this, 'get_cart_summary'));
        add_action('wp_ajax_wcflow_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_nopriv_wcflow_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_wcflow_get_checkout_form', array($this, 'get_checkout_form'));
        add_action('wp_ajax_nopriv_wcflow_get_checkout_form', array($this, 'get_checkout_form'));
        add_action('wp_ajax_wcflow_create_order', array($this, 'create_order'));
        add_action('wp_ajax_nopriv_wcflow_create_order', array($this, 'create_order'));
    }
    
    /**
     * Start the gifting flow
     */
    public function start_flow() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $product_id = intval($_POST['product_id']);
            
            if (!$product_id) {
                wp_send_json_error(['message' => 'Invalid product ID.']);
            }
            
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => 'Product not found.']);
            }
            
            // Clear cart and add product
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id, 1);
            
            $product_price = floatval($product->get_price());
            
            // Get default shipping cost
            $default_shipping_cost = 0;
            $shipping_methods = WC()->shipping() ? WC()->shipping()->get_shipping_methods() : [];
            if (!empty($shipping_methods)) {
                $first_method = reset($shipping_methods);
                if ($first_method && isset($first_method->id)) {
                    $method_id = $first_method->id;
                    $cost_setting = get_option('woocommerce_' . $method_id . '_settings');
                    if ($cost_setting && isset($cost_setting['cost'])) {
                        $default_shipping_cost = floatval($cost_setting['cost']);
                    }
                }
            }
            
            wcflow_log('Flow started - Product: ' . $product_id . ', Price: ' . $product_price . ', Default Shipping: ' . $default_shipping_cost);
            
            wp_send_json_success([
                'message' => 'Flow started successfully.',
                'product_price' => $product_price,
                'shipping_cost' => $default_shipping_cost,
                'cart_total' => WC()->cart->get_total('edit')
            ]);
            
        } catch (Exception $e) {
            wcflow_log('Start flow error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to start flow: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get step template
     */
    public function get_step() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $step = intval($_POST['step']);
            $template_file = WCFLOW_PATH . "templates/step{$step}.php";
            
            if (!file_exists($template_file)) {
                wp_send_json_error(['message' => 'Step template not found.']);
            }
            
            ob_start();
            include $template_file;
            $html = ob_get_clean();
            
            wcflow_log('Step ' . $step . ' template loaded');
            wp_send_json_success(['html' => $html]);
            
        } catch (Exception $e) {
            wcflow_log('Get step error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to load step: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get addons data
     */
    public function get_addons_data() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            wcflow_log('Loading addons');
            
            if (!post_type_exists('wcflow_addon')) {
                wcflow_log('wcflow_addon post type does not exist');
                wp_send_json_success(wcflow_get_mock_addons());
                return;
            }
            
            $addons = get_posts([
                'post_type' => 'wcflow_addon',
                'numberposts' => 10,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ]);
            
            wcflow_log('Found ' . count($addons) . ' addons in database');
            
            if (empty($addons)) {
                wp_send_json_success(wcflow_get_mock_addons());
                return;
            }
            
            $addons_data = [];
            foreach ($addons as $addon) {
                $price_value = get_post_meta($addon->ID, '_wcflow_price', true);
                $price_value = $price_value ? floatval($price_value) : 0;
                
                $image_id = get_post_thumbnail_id($addon->ID);
                $image_url = '';
                if ($image_id) {
                    $image_data = wp_get_attachment_image_src($image_id, 'medium');
                    $image_url = $image_data ? $image_data[0] : '';
                }
                
                // Use fallback image if none exists
                if (empty($image_url)) {
                    $fallback_images = [
                        'https://images.pexels.com/photos/264771/pexels-photo-264771.jpeg?auto=compress&cs=tinysrgb&w=400',
                        'https://images.pexels.com/photos/65882/chocolate-dark-coffee-confiserie-65882.jpeg?auto=compress&cs=tinysrgb&w=400',
                        'https://images.pexels.com/photos/6192337/pexels-photo-6192337.jpeg?auto=compress&cs=tinysrgb&w=400'
                    ];
                    $image_url = $fallback_images[array_rand($fallback_images)];
                }
                
                $addons_data[] = [
                    'id' => $addon->ID,
                    'title' => $addon->post_title,
                    'description' => $addon->post_content ?: 'No description available',
                    'price' => $price_value > 0 ? wc_price($price_value) : 'FREE',
                    'price_value' => $price_value,
                    'img' => $image_url
                ];
                
                wcflow_log('Processed addon: ' . $addon->post_title . ' (ID: ' . $addon->ID . ')');
            }
            
            wcflow_log('Addons data retrieved: ' . count($addons_data) . ' items');
            wp_send_json_success($addons_data);
            
        } catch (Exception $e) {
            wcflow_log('Error loading addons: ' . $e->getMessage());
            wp_send_json_success(wcflow_get_mock_addons());
        }
    }
    
    /**
     * Get cards data with guaranteed database connection
     */
    public function get_cards_data() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            global $wpdb;
            
            wcflow_log('Getting greeting cards data');
            
            // Check for cached data first
            $cached_data = get_transient('wcflow_cards_cache');
            if ($cached_data !== false) {
                wcflow_log('Using cached cards data');
                wp_send_json_success($cached_data);
                return;
            }
            
            // Direct database query to get all greeting card categories
            $categories_query = "
                SELECT DISTINCT t.term_id, t.name, t.slug, tt.count,
                       COALESCE(tm_order.meta_value, '999') as display_order,
                       tm_desc.meta_value as category_description
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                LEFT JOIN {$wpdb->termmeta} tm_order ON t.term_id = tm_order.term_id AND tm_order.meta_key = '_wcflow_category_order'
                LEFT JOIN {$wpdb->termmeta} tm_desc ON t.term_id = tm_desc.term_id AND tm_desc.meta_key = '_wcflow_category_description'
                WHERE tt.taxonomy = 'wcflow_card_category'
                ORDER BY CAST(COALESCE(tm_order.meta_value, '999') AS UNSIGNED) ASC, t.name ASC
            ";
            
            $categories = $wpdb->get_results($categories_query);
            
            wcflow_log('Found ' . count($categories) . ' categories');
            
            if (empty($categories)) {
                wcflow_log('No categories found, using mock data');
                $mock_data = wcflow_get_mock_cards();
                set_transient('wcflow_cards_cache', $mock_data, HOUR_IN_SECONDS);
                wp_send_json_success($mock_data);
                return;
            }
            
            // For each category, get cards using direct SQL
            $cards_by_category = [];
            
            foreach ($categories as $category) {
                wcflow_log('Processing category: ' . $category->name . ' (ID: ' . $category->term_id . ')');
                
                $cards_query = "
                    SELECT DISTINCT p.ID, p.post_title, p.post_status, p.menu_order,
                           pm_price.meta_value as price_meta,
                           pm_thumb.meta_value as thumbnail_id
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_wcflow_price'
                    LEFT JOIN {$wpdb->postmeta} pm_thumb ON p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id'
                    WHERE p.post_type = 'wcflow_card'
                    AND p.post_status = 'publish'
                    AND tt.term_id = %d
                    AND tt.taxonomy = 'wcflow_card_category'
                    ORDER BY p.menu_order ASC, p.post_title ASC
                    LIMIT 20
                ";
                
                $cards = $wpdb->get_results($wpdb->prepare($cards_query, $category->term_id));
                
                wcflow_log('Found ' . count($cards) . ' cards for category: ' . $category->name);
                
                if (!empty($cards)) {
                    $category_cards = [];
                    
                    foreach ($cards as $card) {
                        $price_value = $card->price_meta ? floatval($card->price_meta) : 0;
                        
                        // Get image URL with fallback
                        $image_url = 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400';
                        if ($card->thumbnail_id) {
                            $image_data = wp_get_attachment_image_src($card->thumbnail_id, 'medium');
                            if ($image_data && $image_data[0]) {
                                $image_url = $image_data[0];
                            }
                        }
                        
                        $category_cards[] = [
                            'id' => $card->ID,
                            'title' => $card->post_title,
                            'price' => $price_value > 0 ? wc_price($price_value) : 'FREE',
                            'price_value' => $price_value,
                            'img' => $image_url
                        ];
                        
                        wcflow_log('Processed card: ' . $card->post_title . ' (ID: ' . $card->ID . ')');
                    }
                    
                    $cards_by_category[$category->name] = $category_cards;
                }
            }
            
            if (empty($cards_by_category)) {
                wcflow_log('No cards found in any category, using mock data');
                $mock_data = wcflow_get_mock_cards();
                set_transient('wcflow_cards_cache', $mock_data, HOUR_IN_SECONDS);
                wp_send_json_success($mock_data);
                return;
            }
            
            // Cache the results
            set_transient('wcflow_cards_cache', $cards_by_category, HOUR_IN_SECONDS);
            
            wp_send_json_success($cards_by_category);
            
        } catch (Exception $e) {
            wcflow_log('Error getting cards data: ' . $e->getMessage());
            $mock_data = wcflow_get_mock_cards();
            wp_send_json_success($mock_data);
        }
    }
    
    /**
     * Get shipping methods
     */
    public function get_shipping_methods() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : 'US';
            
            wcflow_log('Getting shipping methods for country: ' . $country);
            
            // Get shipping methods using the helper function
            $shipping_methods = wcflow_get_real_shipping_methods($country);
            
            if (!empty($shipping_methods)) {
                wp_send_json_success($shipping_methods);
            } else {
                wp_send_json_error(['message' => 'No shipping methods available for your location.']);
            }
            
        } catch (Exception $e) {
            wcflow_log('Shipping methods error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to get shipping methods.']);
        }
    }
    
    /**
     * Create order from gifting flow data
     */
    public function create_order() {
        // This is now handled in the order-handler.php file
        do_action('wcflow_create_order');
    }
    
    /**
     * Get cart summary for step 3
     */
    public function get_cart_summary() {
        // This is now handled in the order-handler.php file
        do_action('wcflow_get_cart_summary');
    }
    
    /**
     * Get product data
     */
    public function get_product_data() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $product_id = intval($_POST['product_id']);
            
            if (!$product_id) {
                wp_send_json_error(['message' => 'Invalid product ID.']);
            }
            
            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => 'Product not found.']);
            }
            
            $image_id = $product->get_image_id();
            $image_url = '';
            if ($image_id) {
                $image_data = wp_get_attachment_image_src($image_id, 'medium');
                $image_url = $image_data ? $image_data[0] : '';
            }
            
            wp_send_json_success([
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'price_formatted' => wc_price($product->get_price()),
                'image_url' => $image_url,
                'description' => $product->get_short_description()
            ]);
            
        } catch (Exception $e) {
            wcflow_log('Get product data error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to get product data.']);
        }
    }
    
    /**
     * Get WooCommerce checkout form with payment methods
     */
    public function get_checkout_form() {
        // This is now handled in the order-handler.php file
        do_action('wcflow_get_checkout_form');
    }
}

new WCFlow_AJAX_Handler();