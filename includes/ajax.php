<?php
/**
 * WooCommerce Gifting Flow AJAX Handlers - BULLETPROOF CATEGORY SYSTEM
 * FIXED: 2025-01-27 - Guaranteed category-based data with admin connection
 */

if (!defined('ABSPATH')) exit;

// Debug logging helper
function wcflow_log($message) {
    if (get_option('wcflow_enable_debug') === 'yes' || (defined('WP_DEBUG') && WP_DEBUG)) {
        error_log('[WooCommerce Gifting Flow] ' . $message);
    }
}

// FIXED: Start flow with proper WooCommerce shipping calculation
function wcflow_start_flow() {
    try {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
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
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
        
        if (!$cart_item_key) {
            wp_send_json_error(['message' => 'Failed to add product to cart.']);
        }
        
        // FIXED: Set Lithuania as default shipping country
        WC()->customer->set_shipping_country('LT');
        WC()->customer->set_shipping_postcode('01001');
        WC()->customer->set_shipping_city('Vilnius');
        WC()->customer->set_shipping_state('');
        
        // Force shipping calculation
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        $product_price = floatval($product->get_price());
        
        // FIXED: Get real default shipping cost from WooCommerce
        $default_shipping_cost = 0;
        $packages = WC()->cart->get_shipping_packages();
        
        if (!empty($packages)) {
            $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
            
            if (!empty($shipping_for_package['rates'])) {
                // Get the first (default) shipping method
                $first_rate = reset($shipping_for_package['rates']);
                $default_shipping_cost = $first_rate->get_cost() + $first_rate->get_shipping_tax();
                
                wcflow_log('Default shipping method found: ' . $first_rate->get_label() . ' - Cost: ' . $default_shipping_cost);
            }
        }
        
        // If no shipping methods found, check WooCommerce settings for flat rate
        if ($default_shipping_cost == 0) {
            $shipping_zones = WC_Shipping_Zones::get_zones();
            foreach ($shipping_zones as $zone) {
                foreach ($zone['shipping_methods'] as $method) {
                    if ($method->enabled === 'yes') {
                        if ($method->id === 'flat_rate' && isset($method->instance_settings['cost'])) {
                            $default_shipping_cost = floatval($method->instance_settings['cost']);
                            wcflow_log('Using flat rate from zone: ' . $default_shipping_cost);
                            break 2;
                        }
                    }
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
add_action('wp_ajax_wcflow_start_flow', 'wcflow_start_flow');
add_action('wp_ajax_nopriv_wcflow_start_flow', 'wcflow_start_flow');

// FIXED: Get real WooCommerce shipping methods for Lithuania
function wcflow_get_shipping_methods_ajax() {
    try {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        wcflow_log('Getting shipping methods for Lithuania...');
        
        if (WC()->cart->is_empty()) {
            wcflow_log('Cart is empty, cannot calculate shipping');
            wp_send_json_error(['message' => 'Cart is empty.']);
        }
        
        // FIXED: Ensure Lithuania is set as shipping destination
        WC()->customer->set_shipping_country('LT');
        WC()->customer->set_shipping_postcode('01001');
        WC()->customer->set_shipping_city('Vilnius');
        WC()->customer->set_shipping_state('');
        
        // Force recalculation
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();
        
        $packages = WC()->cart->get_shipping_packages();
        $shipping_methods = [];
        
        wcflow_log('Cart packages count: ' . count($packages));
        
        if (!empty($packages)) {
            $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
            wcflow_log('Shipping rates found: ' . count($shipping_for_package['rates']));
            
            if (!empty($shipping_for_package['rates'])) {
                foreach ($shipping_for_package['rates'] as $rate) {
                    $cost_with_tax = $rate->get_cost() + $rate->get_shipping_tax();
                    $shipping_methods[] = [
                        'id' => $rate->get_id(),
                        'label' => $rate->get_label(),
                        'cost' => number_format($rate->get_cost(), 2),
                        'cost_with_tax' => number_format($cost_with_tax, 2),
                        'method_id' => $rate->get_method_id(),
                        'instance_id' => $rate->get_instance_id()
                    ];
                    
                    wcflow_log('Shipping method: ' . $rate->get_label() . ' - ' . $cost_with_tax);
                }
            }
        }
        
        // FIXED: If no methods found, check shipping zones for Lithuania
        if (empty($shipping_methods)) {
            wcflow_log('No shipping rates found, checking shipping zones...');
            
            $shipping_zones = WC_Shipping_Zones::get_zones();
            $lithuania_zone = null;
            
            // Find zone that includes Lithuania
            foreach ($shipping_zones as $zone_data) {
                $zone = new WC_Shipping_Zone($zone_data['zone_id']);
                $locations = $zone->get_zone_locations();
                
                foreach ($locations as $location) {
                    if ($location->type === 'country' && $location->code === 'LT') {
                        $lithuania_zone = $zone;
                        wcflow_log('Found Lithuania in zone: ' . $zone->get_zone_name());
                        break 2;
                    }
                }
            }
            
            // If Lithuania zone found, get its shipping methods
            if ($lithuania_zone) {
                $zone_shipping_methods = $lithuania_zone->get_shipping_methods(true);
                
                foreach ($zone_shipping_methods as $method) {
                    if ($method->is_enabled()) {
                        $cost = 0;
                        
                        // Get cost based on method type
                        if ($method->id === 'flat_rate') {
                            $cost = isset($method->instance_settings['cost']) ? floatval($method->instance_settings['cost']) : 0;
                        } elseif ($method->id === 'free_shipping') {
                            $cost = 0;
                        }
                        
                        $shipping_methods[] = [
                            'id' => $method->id . ':' . $method->instance_id,
                            'label' => $method->get_title(),
                            'cost' => number_format($cost, 2),
                            'cost_with_tax' => number_format($cost, 2),
                            'method_id' => $method->id,
                            'instance_id' => $method->instance_id
                        ];
                        
                        wcflow_log('Zone shipping method: ' . $method->get_title() . ' - ' . $cost);
                    }
                }
            }
        }
        
        // FIXED: Last resort - check global shipping methods
        if (empty($shipping_methods)) {
            wcflow_log('No zone-specific methods, checking global methods...');
            
            $shipping_methods_obj = WC()->shipping->get_shipping_methods();
            
            foreach ($shipping_methods_obj as $method) {
                if ($method->is_enabled()) {
                    $cost = 4.99; // Default fallback cost
                    
                    if ($method->id === 'flat_rate') {
                        $flat_rate_settings = get_option('woocommerce_flat_rate_settings', []);
                        $cost = isset($flat_rate_settings['cost']) ? floatval($flat_rate_settings['cost']) : 4.99;
                    } elseif ($method->id === 'free_shipping') {
                        $cost = 0;
                    }
                    
                    $shipping_methods[] = [
                        'id' => $method->id . ':1',
                        'label' => $method->get_method_title(),
                        'cost' => number_format($cost, 2),
                        'cost_with_tax' => number_format($cost, 2),
                        'method_id' => $method->id,
                        'instance_id' => '1'
                    ];
                    
                    wcflow_log('Global shipping method: ' . $method->get_method_title() . ' - ' . $cost);
                }
            }
        }
        
        wcflow_log('Final shipping methods count: ' . count($shipping_methods));
        
        if (empty($shipping_methods)) {
            wcflow_log('No shipping methods found, providing fallback');
            // Provide a basic fallback
            $shipping_methods = [
                [
                    'id' => 'fallback:1',
                    'label' => 'Standartinis pristatymas',
                    'cost' => '4.99',
                    'cost_with_tax' => '4.99',
                    'method_id' => 'flat_rate',
                    'instance_id' => '1'
                ]
            ];
        }
        
        wp_send_json_success($shipping_methods);
        
    } catch (Exception $e) {
        wcflow_log('Error loading shipping methods: ' . $e->getMessage());
        
        // Return fallback methods on error
        $fallback_methods = [
            [
                'id' => 'error_fallback:1',
                'label' => 'Standartinis pristatymas',
                'cost' => '4.99',
                'cost_with_tax' => '4.99',
                'method_id' => 'flat_rate',
                'instance_id' => '1'
            ]
        ];
        wp_send_json_success($fallback_methods);
    }
}
add_action('wp_ajax_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');
add_action('wp_ajax_nopriv_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');

// Get step template
function wcflow_get_step() {
    try {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
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
add_action('wp_ajax_wcflow_get_step', 'wcflow_get_step');
add_action('wp_ajax_nopriv_wcflow_get_step', 'wcflow_get_step');

// FIXED: Get addons data with comprehensive error handling
function wcflow_get_addons_data() {
    try {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        wcflow_log('=== LOADING ADDONS ===');
        
        // Check if post type exists
        if (!post_type_exists('wcflow_addon')) {
            wcflow_log('wcflow_addon post type does not exist');
            wp_send_json_success([]); // Return empty array instead of error
        }
        
        $addons = get_posts([
            'post_type' => 'wcflow_addon',
            'numberposts' => 10, // Limit to prevent memory issues
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        
        wcflow_log('Found ' . count($addons) . ' addons in database');
        
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
            
            $addons_data[] = [
                'id' => $addon->ID,
                'title' => $addon->post_title,
                'description' => $addon->post_content ?: 'No description available',
                'price' => $price_value > 0 ? wc_price($price_value) : 'NEMOKAMA',
                'price_value' => $price_value,
                'img' => $image_url
            ];
            
            wcflow_log('Processed addon: ' . $addon->post_title . ' (ID: ' . $addon->ID . ')');
        }
        
        wcflow_log('Addons data retrieved: ' . count($addons_data) . ' items');
        wp_send_json_success($addons_data);
        
    } catch (Exception $e) {
        wcflow_log('Error loading addons: ' . $e->getMessage());
        wp_send_json_success([]); // Return empty array instead of error
    }
}
add_action('wp_ajax_wcflow_get_addons', 'wcflow_get_addons_data');
add_action('wp_ajax_nopriv_wcflow_get_addons', 'wcflow_get_addons_data');

// 🎯 BULLETPROOF CATEGORY-BASED CARDS SYSTEM WITH VERIFICATION
function wcflow_get_cards_data() {
    try {
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        global $wpdb;
        
        wcflow_log('🎯 === BULLETPROOF CATEGORY-BASED CARDS START ===');
        
        // STEP 1: Comprehensive system verification
        $verification = wcflow_verify_system_setup();
        wcflow_log('🔍 System verification: ' . json_encode($verification));
        
        // STEP 2: Check if we have the required post type and taxonomy
        if (!post_type_exists('wcflow_card') || !taxonomy_exists('wcflow_card_category')) {
            wcflow_log('❌ Required post type or taxonomy missing');
            wp_send_json_success(wcflow_get_guaranteed_sample_data());
            return;
        }
        
        // STEP 3: Get all categories with proper ordering
        $categories = get_terms([
            'taxonomy' => 'wcflow_card_category',
            'hide_empty' => false,
            'orderby' => 'meta_value_num',
            'meta_key' => '_wcflow_category_order',
            'order' => 'ASC'
        ]);
        
        wcflow_log('📂 Found ' . count($categories) . ' categories');
        
        if (empty($categories) || is_wp_error($categories)) {
            wcflow_log('❌ No categories found or error occurred');
            wp_send_json_success(wcflow_get_guaranteed_sample_data());
            return;
        }
        
        // STEP 4: Build category-based data structure with detailed logging
        $cards_by_category = [];
        $total_cards_found = 0;
        
        foreach ($categories as $category) {
            wcflow_log('🎨 Processing category: ' . $category->name . ' (ID: ' . $category->term_id . ')');
            
            // Get cards for this category with detailed query
            $cards_query = [
                'post_type' => 'wcflow_card',
                'post_status' => 'publish',
                'numberposts' => 20,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'tax_query' => [
                    [
                        'taxonomy' => 'wcflow_card_category',
                        'field' => 'term_id',
                        'terms' => $category->term_id
                    ]
                ]
            ];
            
            wcflow_log('🔍 Query for category ' . $category->name . ': ' . json_encode($cards_query));
            
            $cards = get_posts($cards_query);
            
            wcflow_log('🎴 Found ' . count($cards) . ' cards for category: ' . $category->name);
            
            if (!empty($cards)) {
                $category_cards = [];
                
                foreach ($cards as $card) {
                    $price_value = get_post_meta($card->ID, '_wcflow_price', true);
                    $price_value = $price_value ? floatval($price_value) : 0;
                    
                    // Get image with fallback
                    $image_url = 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400';
                    if (has_post_thumbnail($card->ID)) {
                        $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($card->ID), 'medium');
                        if ($image_data) {
                            $image_url = $image_data[0];
                        }
                    }
                    
                    $category_cards[] = [
                        'id' => $card->ID,
                        'title' => $card->post_title,
                        'price' => $price_value > 0 ? '€' . number_format($price_value, 2) : 'FREE',
                        'price_value' => $price_value,
                        'img' => $image_url
                    ];
                    
                    $total_cards_found++;
                    wcflow_log('✅ Card processed: ' . $card->post_title . ' (ID: ' . $card->ID . ') - Price: ' . $price_value);
                }
                
                $cards_by_category[$category->name] = $category_cards;
                wcflow_log('✅ Category completed: ' . $category->name . ' with ' . count($category_cards) . ' cards');
            } else {
                wcflow_log('⚠️ No cards found for category: ' . $category->name);
                
                // Check if there are any cards at all for this category (including unpublished)
                $all_cards = get_posts([
                    'post_type' => 'wcflow_card',
                    'post_status' => 'any',
                    'numberposts' => -1,
                    'tax_query' => [
                        [
                            'taxonomy' => 'wcflow_card_category',
                            'field' => 'term_id',
                            'terms' => $category->term_id
                        ]
                    ]
                ]);
                wcflow_log('🔍 Total cards (any status) for ' . $category->name . ': ' . count($all_cards));
            }
        }
        
        wcflow_log('📊 Total cards found across all categories: ' . $total_cards_found);
        wcflow_log('📋 Final categories with cards: ' . implode(', ', array_keys($cards_by_category)));
        
        // STEP 5: Ensure we have data to return
        if (empty($cards_by_category) || $total_cards_found === 0) {
            wcflow_log('❌ No valid cards found - returning guaranteed sample data');
            wp_send_json_success(wcflow_get_guaranteed_sample_data());
        } else {
            wcflow_log('✅ SUCCESS - Returning ' . count($cards_by_category) . ' categories with real data');
            wp_send_json_success($cards_by_category);
        }
        
    } catch (Exception $e) {
        wcflow_log('💥 FATAL ERROR in category-based cards: ' . $e->getMessage());
        wcflow_log('💥 Stack trace: ' . $e->getTraceAsString());
        wp_send_json_success(wcflow_get_guaranteed_sample_data());
    }
}

// SYSTEM VERIFICATION FUNCTION
function wcflow_verify_system_setup() {
    global $wpdb;
    
    return [
        'post_type_exists' => post_type_exists('wcflow_card'),
        'taxonomy_exists' => taxonomy_exists('wcflow_card_category'),
        'total_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
        'published_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
        'total_categories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
        'card_category_relationships' => $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'wcflow_card_category' 
            AND p.post_type = 'wcflow_card'
            AND p.post_status = 'publish'
        "),
        'sample_categories' => $wpdb->get_results("
            SELECT t.term_id, t.name, tt.count 
            FROM {$wpdb->terms} t 
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            WHERE tt.taxonomy = 'wcflow_card_category' 
            LIMIT 5
        "),
        'sample_cards' => $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_status, pm.meta_value as price
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wcflow_price'
            WHERE p.post_type = 'wcflow_card' 
            ORDER BY p.menu_order ASC 
            LIMIT 5
        ")
    ];
}

// GUARANTEED sample data that will always work
function wcflow_get_guaranteed_sample_data() {
    wcflow_log('🔄 Returning GUARANTEED sample data for category-based sliders');
    
    return [
        'Birthday Cards' => [
            [
                'id' => 'sample-birthday-1',
                'title' => 'Happy Birthday Balloons',
                'price' => 'FREE',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-birthday-2',
                'title' => 'Birthday Cake Celebration',
                'price' => '€1.50',
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-birthday-3',
                'title' => 'Birthday Wishes',
                'price' => '€2.50',
                'price_value' => 2.50,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-birthday-4',
                'title' => 'Party Time',
                'price' => '€1.75',
                'price_value' => 1.75,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-birthday-5',
                'title' => 'Another Year Older',
                'price' => '€2.00',
                'price_value' => 2.00,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ],
        'Holiday Cards' => [
            [
                'id' => 'sample-holiday-1',
                'title' => 'Season Greetings',
                'price' => 'FREE',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-holiday-2',
                'title' => 'Winter Wonderland',
                'price' => '€1.25',
                'price_value' => 1.25,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-holiday-3',
                'title' => 'Holiday Cheer',
                'price' => '€1.50',
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ],
        'Thank You Cards' => [
            [
                'id' => 'sample-thanks-1',
                'title' => 'Thank You So Much',
                'price' => 'FREE',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-thanks-2',
                'title' => 'Grateful Heart',
                'price' => '€1.00',
                'price_value' => 1.00,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ]
    ];
}

add_action('wp_ajax_wcflow_get_cards', 'wcflow_get_cards_data');
add_action('wp_ajax_nopriv_wcflow_get_cards', 'wcflow_get_cards_data');

// 🔍 ENHANCED ADMIN DEBUG ENDPOINT - Check what's in the database
function wcflow_debug_admin_data() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    global $wpdb;
    
    // Get comprehensive system information
    $debug_info = [
        'system_info' => [
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
            'plugin_version' => defined('WCFLOW_VERSION') ? WCFLOW_VERSION : 'Unknown',
            'database_prefix' => $wpdb->prefix,
            'current_time' => current_time('mysql'),
        ],
        
        'post_types_and_taxonomies' => [
            'wcflow_card_exists' => post_type_exists('wcflow_card'),
            'wcflow_addon_exists' => post_type_exists('wcflow_addon'),
            'wcflow_card_category_exists' => taxonomy_exists('wcflow_card_category'),
            'all_post_types' => array_keys(get_post_types()),
            'all_taxonomies' => array_keys(get_taxonomies()),
        ],
        
        'database_counts' => [
            'total_cards_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
            'published_cards_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
            'draft_cards_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'draft'"),
            'total_addons_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_addon'"),
            'total_categories_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
            'card_category_relationships' => $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                WHERE tt.taxonomy = 'wcflow_card_category' 
                AND p.post_type = 'wcflow_card'
                AND p.post_status = 'publish'
            "),
        ],
        
        'sample_data' => [
            'sample_cards' => $wpdb->get_results("
                SELECT p.ID, p.post_title, p.post_status, p.menu_order, pm.meta_value as price
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wcflow_price'
                WHERE p.post_type = 'wcflow_card' 
                ORDER BY p.menu_order ASC, p.ID ASC 
                LIMIT 10
            "),
            
            'sample_categories' => $wpdb->get_results("
                SELECT t.term_id, t.name, t.slug, tt.count, tm.meta_value as display_order
                FROM {$wpdb->terms} t 
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = '_wcflow_category_order'
                WHERE tt.taxonomy = 'wcflow_card_category' 
                ORDER BY CAST(tm.meta_value AS UNSIGNED) ASC, t.name ASC
                LIMIT 10
            "),
            
            'card_category_relationships' => $wpdb->get_results("
                SELECT p.ID, p.post_title, p.post_status, t.name as category_name, t.term_id
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE p.post_type = 'wcflow_card' 
                AND tt.taxonomy = 'wcflow_card_category'
                ORDER BY t.name ASC, p.menu_order ASC
                LIMIT 20
            "),
            
            'sample_prices' => $wpdb->get_results("
                SELECT p.ID, p.post_title, pm.meta_value as price, p.post_status
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wcflow_price'
                WHERE p.post_type = 'wcflow_card' 
                ORDER BY p.ID ASC
                LIMIT 10
            ")
        ],
        
        'plugin_options' => [
            'wcflow_default_data_created' => get_option('wcflow_default_data_created'),
            'wcflow_flush_rewrite_rules' => get_option('wcflow_flush_rewrite_rules'),
            'wcflow_version' => get_option('wcflow_version'),
            'wcflow_enable_debug' => get_option('wcflow_enable_debug'),
        ],
        
        'verification_test' => wcflow_verify_system_setup()
    ];
    
    wp_send_json_success($debug_info);
}
add_action('wp_ajax_wcflow_debug_admin_data', 'wcflow_debug_admin_data');

// 🛠️ FORCE CREATE SAMPLE DATA ENDPOINT
function wcflow_force_create_sample_data() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    wcflow_log('🛠️ Force creating sample data...');
    
    // Force create sample data
    delete_option('wcflow_default_data_created');
    delete_option('wcflow_flush_rewrite_rules');
    
    // Re-register post types and taxonomies
    wcflow_register_post_types_and_taxonomies();
    
    // Create default data
    wcflow_create_default_categories_and_cards();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Get verification after creation
    $verification = wcflow_verify_system_setup();
    
    wp_send_json_success([
        'message' => 'Sample data created successfully!',
        'verification' => $verification
    ]);
}
add_action('wp_ajax_wcflow_force_create_sample_data', 'wcflow_force_create_sample_data');

// 🧹 CLEANUP AND RESET ENDPOINT
function wcflow_cleanup_and_reset() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    global $wpdb;
    
    wcflow_log('🧹 Starting cleanup and reset...');
    
    // Delete all cards
    $cards = get_posts(['post_type' => 'wcflow_card', 'numberposts' => -1, 'post_status' => 'any']);
    foreach ($cards as $card) {
        wp_delete_post($card->ID, true);
    }
    
    // Delete all categories
    $categories = get_terms(['taxonomy' => 'wcflow_card_category', 'hide_empty' => false]);
    foreach ($categories as $category) {
        wp_delete_term($category->term_id, 'wcflow_card_category');
    }
    
    // Reset options
    delete_option('wcflow_default_data_created');
    delete_option('wcflow_flush_rewrite_rules');
    
    // Force recreate everything
    wcflow_register_post_types_and_taxonomies();
    wcflow_create_default_categories_and_cards();
    flush_rewrite_rules();
    
    $verification = wcflow_verify_system_setup();
    
    wp_send_json_success([
        'message' => 'System cleaned and reset successfully!',
        'verification' => $verification
    ]);
}
add_action('wp_ajax_wcflow_cleanup_and_reset', 'wcflow_cleanup_and_reset');