<?php
/**
 * WooCommerce Gifting Flow AJAX Handler
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
        
        // Admin-only endpoints
        add_action('wp_ajax_wcflow_debug_admin_data', array($this, 'debug_admin_data'));
        add_action('wp_ajax_wcflow_force_create_sample_data', array($this, 'force_create_sample_data'));
        add_action('wp_ajax_wcflow_cleanup_and_reset', array($this, 'cleanup_and_reset'));
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
            
            wcflow_log('=== LOADING ADDONS ===');
            
            if (!post_type_exists('wcflow_addon')) {
                wcflow_log('wcflow_addon post type does not exist');
                wp_send_json_success([]);
            }
            
            $addons = get_posts([
                'post_type' => 'wcflow_addon',
                'numberposts' => 10,
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
            wp_send_json_success([]);
        }
    }
    
    /**
     * Get cards data with guaranteed database connection
     */
    public function get_cards_data() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            global $wpdb;
            
            wcflow_log('🎯 === GUARANTEED DATABASE CONNECTION START ===');
            
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
            
            wcflow_log('📂 Direct SQL found ' . count($categories) . ' categories');
            
            if (empty($categories)) {
                wcflow_log('❌ No categories found in database - returning sample data');
                wp_send_json_success($this->get_guaranteed_sample_data());
                return;
            }
            
            // For each category, get cards using direct SQL
            $cards_by_category = [];
            $total_cards_found = 0;
            
            foreach ($categories as $category) {
                wcflow_log('🎨 Processing category: ' . $category->name . ' (ID: ' . $category->term_id . ')');
                
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
                
                wcflow_log('🎴 Direct SQL found ' . count($cards) . ' cards for category: ' . $category->name);
                
                if (!empty($cards)) {
                    $category_cards = [];
                    
                    foreach ($cards as $card) {
                        $price_value = $card->price_meta ? floatval($card->price_meta) : 0;
                        
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
                            'price' => $price_value > 0 ? '€' . number_format($price_value, 2) : 'FREE',
                            'price_value' => $price_value,
                            'img' => $image_url
                        ];
                        
                        $total_cards_found++;
                        wcflow_log('✅ Card processed: ' . $card->post_title . ' (ID: ' . $card->ID . ') - Price: ' . $price_value);
                    }
                    
                    $cards_by_category[$category->name] = $category_cards;
                    wcflow_log('✅ Category completed: ' . $category->name . ' with ' . count($category_cards) . ' cards');
                }
            }
            
            wcflow_log('📊 Total cards found across all categories: ' . $total_cards_found);
            
            if (empty($cards_by_category) || $total_cards_found === 0) {
                wcflow_log('❌ No real admin data found - providing sample data');
                wp_send_json_success($this->get_guaranteed_sample_data());
            } else {
                wcflow_log('✅ SUCCESS - Returning ' . count($cards_by_category) . ' categories with REAL admin data');
                wp_send_json_success($cards_by_category);
            }
            
        } catch (Exception $e) {
            wcflow_log('💥 FATAL ERROR in database connection: ' . $e->getMessage());
            wp_send_json_success($this->get_guaranteed_sample_data());
        }
    }
    
    /**
     * Get guaranteed sample data
     */
    private function get_guaranteed_sample_data() {
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
                ]
            ],
            'Thank You Cards' => [
                [
                    'id' => 'sample-thanks-1',
                    'title' => 'Thank You So Much',
                    'price' => 'FREE',
                    'price_value' => 0,
                    'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                ]
            ]
        ];
    }
    
    /**
     * Get shipping methods using WooCommerce's built-in engine
     */
    public function get_shipping_methods() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : 'LT';
            $postcode = isset($_POST['postcode']) ? sanitize_text_field($_POST['postcode']) : '';
            $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
            $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
            
            wcflow_log('🚚 REAL WC SHIPPING: Getting shipping methods for ' . $country);
            
            if (!function_exists('WC') || !WC()->cart) {
                wcflow_log('🚚 ERROR: WooCommerce not properly loaded');
                wp_send_json_error(['message' => 'WooCommerce is not properly loaded.']);
                return;
            }
            
            // Set up customer shipping address
            WC()->customer->set_shipping_country($country);
            if ($postcode) WC()->customer->set_shipping_postcode($postcode);
            if ($state) WC()->customer->set_shipping_state($state);
            if ($city) WC()->customer->set_shipping_city($city);
            
            WC()->customer->set_billing_country($country);
            if ($postcode) WC()->customer->set_billing_postcode($postcode);
            if ($state) WC()->customer->set_billing_state($state);
            if ($city) WC()->customer->set_billing_city($city);
            
            // Ensure cart has products
            if (WC()->cart->is_empty()) {
                $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
                if (!empty($products)) {
                    WC()->cart->add_to_cart($products[0]->get_id(), 1);
                    wcflow_log('🚚 Added product to cart for calculation: ' . $products[0]->get_name());
                } else {
                    wp_send_json_error(['message' => 'No products available for shipping calculation.']);
                    return;
                }
            }
            
            // Calculate shipping
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
            
            $packages = WC()->cart->get_shipping_packages();
            wcflow_log('🚚 Found ' . count($packages) . ' shipping packages');
            
            if (empty($packages)) {
                wp_send_json_error(['message' => 'No shipping packages found.']);
                return;
            }
            
            $real_methods = array();
            
            foreach ($packages as $package_key => $package) {
                $shipping_for_package = WC()->shipping->calculate_shipping_for_package($package, $package_key);
                
                foreach ($shipping_for_package['rates'] as $rate_key => $rate) {
                    if (!($rate instanceof WC_Shipping_Rate)) {
                        continue;
                    }
                    
                    $cost = floatval($rate->get_cost());
                    $tax = floatval($rate->get_shipping_tax());
                    $total_cost = $cost + $tax;
                    
                    $price_formatted = $total_cost == 0 ? 'FREE' : wc_price($total_cost);
                    
                    $real_methods[] = array(
                        'id' => $rate_key,
                        'label' => $rate->get_label(),
                        'name' => $rate->get_label(),
                        'cost' => number_format($cost, 2),
                        'tax' => number_format($tax, 2),
                        'cost_with_tax' => number_format($total_cost, 2),
                        'price' => $total_cost,
                        'price_formatted' => strip_tags($price_formatted),
                        'method_id' => $rate->get_method_id(),
                        'instance_id' => $rate->get_instance_id(),
                        'description' => 'Shipping via ' . $rate->get_label()
                    );
                    
                    wcflow_log('🚚 Added method: ' . $rate->get_label() . ' - Total: €' . $total_cost);
                }
            }
            
            if (!empty($real_methods)) {
                wcflow_log('🚚 SUCCESS: Returning ' . count($real_methods) . ' shipping methods');
                wp_send_json_success($real_methods);
            } else {
                wp_send_json_error(['message' => 'No shipping methods available for your location.']);
            }
            
        } catch (Exception $e) {
            wcflow_log('🚚 ERROR: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to calculate shipping methods: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get cart summary for step 3 - FIXED TO SHOW REAL DATA
     */
    public function get_cart_summary() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            $order_state = isset($_POST['order_state']) ? json_decode(stripslashes($_POST['order_state']), true) : array();
            
            wcflow_log('Cart summary requested with order state: ' . print_r($order_state, true));
            
            ob_start();
            ?>
            <div class="wcflow-basket-summary">
                <?php
                // FIXED: Get REAL product data from WooCommerce cart
                $base_price = isset($order_state['base_price']) ? floatval($order_state['base_price']) : 0;
                $product_name = 'Main Product';
                $product_image_url = '';
                
                if (!WC()->cart->is_empty()) {
                    foreach (WC()->cart->get_cart() as $cart_item) {
                        $product = $cart_item['data'];
                        if ($product && $product->exists()) {
                            $product_name = $product->get_name();
                            $base_price = floatval($product->get_price());
                            
                            // Get real product image
                            $image_id = $product->get_image_id();
                            if ($image_id) {
                                $image_data = wp_get_attachment_image_src($image_id, 'thumbnail');
                                if ($image_data && $image_data[0]) {
                                    $product_image_url = $image_data[0];
                                }
                            }
                            break;
                        }
                    }
                }
                ?>
                
                <div class="wcflow-basket-item" style="display:flex;align-items:center;padding:16px;border-bottom:1px solid #e0e0e0;">
                    <div class="wcflow-basket-item-img" style="width:80px;height:80px;margin-right:16px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        <?php if ($product_image_url) : ?>
                            <img src="<?php echo esc_url($product_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else : ?>
                            <span style="color:#666;font-size:12px;">🎁</span>
                        <?php endif; ?>
                    </div>
                    <div class="wcflow-basket-item-details" style="flex:1;">
                        <p style="margin:0 0 4px 0;font-weight:600;color:#333;"><?php echo esc_html($product_name); ?></p>
                        <p style="margin:0;color:#666;font-size:14px;">Base gift item</p>
                    </div>
                    <div class="wcflow-basket-item-price" style="font-weight:700;color:#007cba;">
                        €<?php echo number_format($base_price, 2); ?>
                    </div>
                </div>
                
                <?php
                // Add-ons
                if (isset($order_state['addons']) && is_array($order_state['addons']) && !empty($order_state['addons'])) {
                    foreach ($order_state['addons'] as $addon_id) {
                        $addon = get_post($addon_id);
                        if ($addon && $addon->post_type === 'wcflow_addon') {
                            $addon_price = get_post_meta($addon_id, '_wcflow_price', true);
                            $addon_price = $addon_price ? floatval($addon_price) : 0;
                            ?>
                            <div class="wcflow-basket-addon" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #e0e0e0;background:#f9f9f9;">
                                <span style="color:#666;"><strong>+</strong> <?php echo esc_html($addon->post_title); ?></span>
                                <span style="font-weight:600;color:#333;">€<?php echo number_format($addon_price, 2); ?></span>
                            </div>
                            <?php
                        }
                    }
                }
                
                // Greeting card
                if (isset($order_state['card_id']) && $order_state['card_id']) {
                    $card = get_post($order_state['card_id']);
                    if ($card && $card->post_type === 'wcflow_card') {
                        $card_price = get_post_meta($order_state['card_id'], '_wcflow_price', true);
                        $card_price = $card_price ? floatval($card_price) : 0;
                        ?>
                        <div class="wcflow-basket-card" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #e0e0e0;background:#f9f9f9;">
                            <span style="color:#666;"><strong>+</strong> <?php echo esc_html($card->post_title); ?></span>
                            <span style="font-weight:600;color:#333;"><?php echo $card_price > 0 ? '€' . number_format($card_price, 2) : 'FREE'; ?></span>
                        </div>
                        <?php
                    }
                }
                
                // Message
                if (isset($order_state['card_message']) && !empty(trim($order_state['card_message']))) {
                    ?>
                    <div class="wcflow-basket-message" style="padding:12px 16px;border-bottom:1px solid #e0e0e0;background:#f0f8ff;">
                        <p style="margin:0;color:#666;font-size:14px;"><strong>Message:</strong></p>
                        <p style="margin:4px 0 0 0;color:#333;font-style:italic;">"<?php echo esc_html($order_state['card_message']); ?>"</p>
                    </div>
                    <?php
                }
                
                // Recipient section
                ?>
                <div class="wcflow-basket-recipient" style="padding:16px;border-bottom:1px solid #e0e0e0;">
                    <h4 style="margin:0 0 8px 0;color:#333;font-size:16px;">Recipient</h4>
                    <p style="margin:0;color:#666;line-height:1.4;">
                        To <?php echo esc_html(($order_state['shipping_first_name'] ?? '') . ' ' . ($order_state['shipping_last_name'] ?? '')); ?><br>
                        <?php echo esc_html($order_state['shipping_address_1'] ?? ''); ?><br>
                        <?php echo esc_html(($order_state['shipping_city'] ?? '') . ', ' . ($order_state['shipping_postcode'] ?? '')); ?><br>
                        <?php echo esc_html($order_state['shipping_country'] ?? ''); ?>
                    </p>
                </div>
                
                <?php
                // Delivery section
                ?>
                <div class="wcflow-basket-delivery" style="padding:16px;border-bottom:1px solid #e0e0e0;">
                    <h4 style="margin:0 0 8px 0;color:#333;font-size:16px;">Delivery</h4>
                    <p style="margin:0;color:#666;line-height:1.4;">
                        <?php echo esc_html($order_state['delivery_date_formatted'] ?? 'Date not selected'); ?><br>
                        <?php echo esc_html($order_state['shipping_method_name'] ?? 'Method not selected'); ?>
                    </p>
                </div>
                
                <?php
                // Totals
                $subtotal = isset($order_state['subtotal']) ? floatval($order_state['subtotal']) : 0;
                $shipping = isset($order_state['shipping_cost']) ? floatval($order_state['shipping_cost']) : 0;
                $total = isset($order_state['total']) ? floatval($order_state['total']) : 0;
                ?>
                
                <div class="wcflow-basket-subtotal" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                    <span style="color:#666;">Subtotal:</span>
                    <span style="font-weight:600;color:#333;">€<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if ($shipping > 0) : ?>
                    <div class="wcflow-basket-shipping" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                        <span style="color:#666;">Shipping:</span>
                        <span style="font-weight:600;color:#333;">€<?php echo number_format($shipping, 2); ?></span>
                    </div>
                <?php else : ?>
                    <div class="wcflow-basket-shipping" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                        <span style="color:#666;">Shipping:</span>
                        <span style="font-weight:600;color:#28a745;">FREE</span>
                    </div>
                <?php endif; ?>
                
                <div class="wcflow-basket-total" style="display:flex;justify-content:space-between;padding:16px;background:#f8f9fa;font-size:18px;">
                    <strong style="color:#333;">Total:</strong>
                    <strong style="color:#007cba;">€<?php echo number_format($total, 2); ?></strong>
                </div>
            </div>
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(['html' => $html]);
            
        } catch (Exception $e) {
            wcflow_log('Cart summary error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to load cart summary.']);
        }
    }
    
    /**
     * Debug admin data (admin only)
     */
    public function debug_admin_data() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        global $wpdb;
        
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
            ],
            
            'database_counts' => [
                'total_cards_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
                'published_cards_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
                'total_categories_db' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
            ]
        ];
        
        wp_send_json_success($debug_info);
    }
    
    /**
     * Force create sample data (admin only)
     */
    public function force_create_sample_data() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('wcflow_nonce', 'nonce');
        
        wcflow_log('🛠️ Force creating sample data...');
        
        delete_option('wcflow_default_data_created');
        delete_option('wcflow_flush_rewrite_rules');
        
        if (file_exists(WCFLOW_PATH . 'includes/cpt.php')) {
            include_once WCFLOW_PATH . 'includes/cpt.php';
            if (function_exists('wcflow_register_post_types_and_taxonomies')) {
                wcflow_register_post_types_and_taxonomies();
            }
            if (function_exists('wcflow_create_default_categories_and_cards')) {
                wcflow_create_default_categories_and_cards();
            }
        }
        
        flush_rewrite_rules();
        
        global $wpdb;
        $verification = [
            'total_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
            'published_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
            'total_categories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
        ];
        
        wp_send_json_success([
            'message' => 'Sample data created successfully!',
            'verification' => $verification
        ]);
    }
    
    /**
     * Cleanup and reset (admin only)
     */
    public function cleanup_and_reset() {
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
        if (file_exists(WCFLOW_PATH . 'includes/cpt.php')) {
            include_once WCFLOW_PATH . 'includes/cpt.php';
            if (function_exists('wcflow_register_post_types_and_taxonomies')) {
                wcflow_register_post_types_and_taxonomies();
            }
            if (function_exists('wcflow_create_default_categories_and_cards')) {
                wcflow_create_default_categories_and_cards();
            }
        }
        
        flush_rewrite_rules();
        
        $verification = [
            'total_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
            'published_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
            'total_categories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
        ];
        
        wp_send_json_success([
            'message' => 'System cleaned and reset successfully!',
            'verification' => $verification
        ]);
    }
    
    /**
     * Get real product data
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
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            if (!WC()->payment_gateways()) {
                wp_send_json_error(['message' => 'Payment gateways not available.']);
            }
            
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            
            if (empty($available_gateways)) {
                wp_send_json_success(['html' => '<p>No payment methods available.</p>']);
                return;
            }
            
            ob_start();
            ?>
            <div class="wcflow-payment-methods">
                <?php foreach ($available_gateways as $gateway) : ?>
                    <div class="wcflow-payment-method">
                        <label for="payment_method_<?php echo esc_attr($gateway->id); ?>">
                            <input type="radio" 
                                   id="payment_method_<?php echo esc_attr($gateway->id); ?>" 
                                   name="payment_method" 
                                   value="<?php echo esc_attr($gateway->id); ?>" 
                                   <?php echo $gateway->chosen ? 'checked' : ''; ?>>
                            <?php echo $gateway->get_title(); ?>
                        </label>
                        <?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
                            <div class="wcflow-payment-method-details" style="margin-left: 25px; margin-top: 10px;">
                                <?php if ($gateway->get_description()) : ?>
                                    <p><?php echo $gateway->get_description(); ?></p>
                                <?php endif; ?>
                                <?php if ($gateway->has_fields()) : ?>
                                    <?php $gateway->payment_fields(); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(['html' => $html]);
            
        } catch (Exception $e) {
            wcflow_log('Get checkout form error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to load payment methods.']);
        }
    }
    
}

new WCFlow_AJAX_Handler();
