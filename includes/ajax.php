<?php
/**
 * WooCommerce Gifting Flow AJAX Handlers
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-06-18
 * @updated 2025-01-27 - Fixed data transfer and validation
 */

if (!defined('ABSPATH')) exit;

// Debug logging helper
function wcflow_log($message) {
    if (get_option('wcflow_enable_debug') === 'yes' || (defined('WP_DEBUG') && WP_DEBUG)) {
        error_log('[WooCommerce Gifting Flow 2025-01-27] ' . $message);
    }
}

// PERFORMANCE: Optimize addons data retrieval
function wcflow_get_addons_data() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    // PERFORMANCE: Use get_posts instead of WP_Query for better performance
    $addons = get_posts([
        'post_type' => 'wcflow_addon',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_wcflow_price',
                'compare' => 'EXISTS'
            ]
        ]
    ]);
    
    $addons_data = [];
    foreach ($addons as $addon) {
        $price_value = get_post_meta($addon->ID, '_wcflow_price', true);
        $image_id = get_post_thumbnail_id($addon->ID);
        $image_url = $image_id ? wp_get_attachment_image_src($image_id, 'medium')[0] : '';
        
        $addons_data[] = [
            'id' => $addon->ID,
            'title' => $addon->post_title,
            'description' => $addon->post_content,
            'price' => wc_price($price_value),
            'price_value' => floatval($price_value),
            'img' => $image_url
        ];
    }
    
    wcflow_log('Addons data retrieved: ' . count($addons_data) . ' items');
    wp_send_json_success($addons_data);
}
add_action('wp_ajax_wcflow_get_addons', 'wcflow_get_addons_data');
add_action('wp_ajax_nopriv_wcflow_get_addons', 'wcflow_get_addons_data');

// PERFORMANCE: Optimize cards data retrieval
function wcflow_get_cards_data() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $cards = get_posts([
        'post_type' => 'wcflow_card',
        'numberposts' => -1,
        'post_status' => 'publish'
    ]);
    
    $cards_by_category = [];
    foreach ($cards as $card) {
        $terms = get_the_terms($card->ID, 'wcflow_card_category');
        $category = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Uncategorized';
        
        $price_value = get_post_meta($card->ID, '_wcflow_price', true);
        $image_id = get_post_thumbnail_id($card->ID);
        $image_url = $image_id ? wp_get_attachment_image_src($image_id, 'medium')[0] : '';
        
        if (!isset($cards_by_category[$category])) {
            $cards_by_category[$category] = [];
        }
        
        $cards_by_category[$category][] = [
            'id' => $card->ID,
            'title' => $card->post_title,
            'price' => wc_price($price_value),
            'price_value' => floatval($price_value),
            'img' => $image_url
        ];
    }
    
    wcflow_log('Cards data retrieved by category: ' . json_encode(array_keys($cards_by_category)));
    wp_send_json_success($cards_by_category);
}
add_action('wp_ajax_wcflow_get_cards', 'wcflow_get_cards_data');
add_action('wp_ajax_nopriv_wcflow_get_cards', 'wcflow_get_cards_data');

// Start flow
function wcflow_start_flow() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
    }
    
    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($product_id, 1);
    
    wcflow_log('Flow started for product ID: ' . $product_id);
    wp_send_json_success(['message' => 'Flow started successfully.']);
}
add_action('wp_ajax_wcflow_start_flow', 'wcflow_start_flow');
add_action('wp_ajax_nopriv_wcflow_start_flow', 'wcflow_start_flow');

// Get step template
function wcflow_get_step() {
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
}
add_action('wp_ajax_wcflow_get_step', 'wcflow_get_step');
add_action('wp_ajax_nopriv_wcflow_get_step', 'wcflow_get_step');

// User login handler
function wcflow_user_login() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];
    
    $user = wp_authenticate($username, $password);
    
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid username or password.']);
    }
    
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    
    // Get user's shipping data
    $shipping_data = [
        'email' => $user->user_email,
        'first_name' => get_user_meta($user->ID, 'shipping_first_name', true) ?: get_user_meta($user->ID, 'first_name', true),
        'last_name' => get_user_meta($user->ID, 'shipping_last_name', true) ?: get_user_meta($user->ID, 'last_name', true),
        'address_1' => get_user_meta($user->ID, 'shipping_address_1', true),
        'city' => get_user_meta($user->ID, 'shipping_city', true),
        'postcode' => get_user_meta($user->ID, 'shipping_postcode', true),
        'country' => get_user_meta($user->ID, 'shipping_country', true),
        'phone' => get_user_meta($user->ID, 'billing_phone', true)
    ];
    
    wcflow_log('User logged in: ' . $username);
    wp_send_json_success(['shipping_data' => $shipping_data]);
}
add_action('wp_ajax_wcflow_user_login', 'wcflow_user_login');
add_action('wp_ajax_nopriv_wcflow_user_login', 'wcflow_user_login');

// Get shipping methods
function wcflow_get_shipping_methods_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    WC()->cart->calculate_shipping();
    $packages = WC()->cart->get_shipping_packages();
    $shipping_methods = [];
    
    if (!empty($packages)) {
        $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
        
        foreach ($shipping_for_package['rates'] as $rate) {
            $cost_with_tax = $rate->get_cost() + $rate->get_shipping_tax();
            $shipping_methods[] = [
                'id' => $rate->get_id(),
                'label' => $rate->get_label(),
                'cost' => $rate->get_cost(),
                'cost_with_tax' => $cost_with_tax
            ];
        }
    }
    
    wcflow_log('Shipping methods retrieved: ' . count($shipping_methods) . ' methods');
    wp_send_json_success($shipping_methods);
}
add_action('wp_ajax_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');
add_action('wp_ajax_nopriv_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');

// Get cart total
function wcflow_get_cart_total_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    WC()->cart->calculate_totals();
    
    $response = [
        'subtotal' => WC()->cart->get_subtotal(),
        'subtotal_with_tax' => WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax(),
        'shipping_total' => WC()->cart->get_shipping_total(),
        'shipping_total_with_tax' => WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax(),
        'total' => WC()->cart->get_total('edit'),
        'currency' => get_woocommerce_currency_symbol()
    ];
    
    wcflow_log('Cart total calculated: ' . $response['total']);
    wp_send_json_success($response);
}
add_action('wp_ajax_wcflow_get_cart_total', 'wcflow_get_cart_total_ajax');
add_action('wp_ajax_nopriv_wcflow_get_cart_total', 'wcflow_get_cart_total_ajax');

// Get delivery options - FIXED: Using correct setting key
function wcflow_get_delivery_options_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $processing_time = intval(get_option('wcflow_processing_time', 2));
    $allowed_days = get_option('wcflow_allowed_delivery_days', [1, 2, 3, 4, 5]);
    $method_times_raw = get_option('wcflow_shipping_method_processing', '');
    $method_times = [];
    
    // Parse shipping method processing times
    if ($method_times_raw) {
        foreach (explode("\n", $method_times_raw) as $line) {
            $line = trim($line);
            if ($line && strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $method_times[trim($parts[0])] = intval(trim($parts[1]));
                }
            }
        }
    }
    
    $response = [
        'processingTime' => $processing_time,
        'allowedDays' => array_map('intval', $allowed_days),
        'methodTimes' => $method_times
    ];
    
    wcflow_log('Delivery options retrieved: ' . json_encode($response));
    wp_send_json_success($response);
}
add_action('wp_ajax_wcflow_get_delivery_options', 'wcflow_get_delivery_options_ajax');
add_action('wp_ajax_nopriv_wcflow_get_delivery_options', 'wcflow_get_delivery_options_ajax');

// Enhanced cart summary with improved display
function wcflow_get_cart_summary_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    WC()->cart->calculate_totals();
    ob_start();
    ?>
    <div class="wcflow-basket-summary">
        <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            if (!$_product || !$_product->exists()) continue;
            
            // Get selected addons from session
            $selected_addons = WC()->session->get('wcflow_selected_addons', []);
            $selected_card = WC()->session->get('wcflow_selected_card', null);
            $card_message = WC()->session->get('wcflow_card_message', '');
            ?>
            <div class="wcflow-basket-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                <div class="wcflow-basket-item-img">
                    <?php echo $_product->get_image('thumbnail'); ?>
                </div>
                <div class="wcflow-basket-item-details">
                    <p class="wcflow-basket-item-title"><?php echo $_product->get_name(); ?></p>
                    <p class="wcflow-basket-item-qty">Qty: <?php echo $cart_item['quantity']; ?></p>
                    
                    <?php if (!empty($selected_addons)) : ?>
                        <div class="wcflow-basket-item-addons">
                            <?php foreach ($selected_addons as $addon) : 
                                if (is_array($addon) && isset($addon['id'])) :
                                    $addon_post = get_post($addon['id']);
                                    if ($addon_post) :
                            ?>
                                <span class="wcflow-addon-tag">
                                    <span class="wcflow-addon-plus">+</span>
                                    <?php echo esc_html($addon_post->post_title); ?>
                                    <strong><?php echo wc_price($addon['price_value']); ?></strong>
                                </span>
                            <?php 
                                    endif;
                                endif;
                            endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($selected_card && is_array($selected_card) && isset($selected_card['id'])) : 
                        $card_post = get_post($selected_card['id']);
                        if ($card_post) :
                    ?>
                        <div class="wcflow-basket-item-greeting">
                            <p class="wcflow-greeting-title">+ <?php echo esc_html($card_post->post_title); ?> <?php echo wc_price($selected_card['price_value']); ?></p>
                            <?php if (!empty($card_message)) : ?>
                                <p class="wcflow-greeting-message">"<?php echo esc_html(wp_trim_words($card_message, 15)); ?>"</p>
                            <?php endif; ?>
                        </div>
                    <?php 
                        endif;
                    endif; ?>
                </div>
                <div class="wcflow-basket-item-actions">
                    <div class="wcflow-basket-item-price"><?php echo WC()->cart->get_product_price($_product); ?></div>
                    <div class="wcflow-item-actions">
                        <a href="#" class="wcflow-edit-btn" data-action="edit" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">Edit</a>
                        <a href="#" class="wcflow-remove-btn" data-action="remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">Remove</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    $html = ob_get_clean();
    
    wcflow_log('Enhanced cart summary generated');
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_wcflow_get_cart_summary', 'wcflow_get_cart_summary_ajax');
add_action('wp_ajax_nopriv_wcflow_get_cart_summary', 'wcflow_get_cart_summary_ajax');

// Enhanced payment gateways
function wcflow_get_payment_gateways_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    $gateways_data = [];
    
    foreach ($available_gateways as $gateway) {
        $gateways_data[] = [
            'id' => $gateway->id,
            'title' => $gateway->get_title(),
            'description' => $gateway->get_description(),
            'icon' => $gateway->get_icon(),
            'enabled' => $gateway->is_available()
        ];
    }
    
    wcflow_log('Payment gateways retrieved: ' . count($gateways_data) . ' gateways');
    wp_send_json_success($gateways_data);
}
add_action('wp_ajax_wcflow_get_payment_gateways', 'wcflow_get_payment_gateways_ajax');
add_action('wp_ajax_nopriv_wcflow_get_payment_gateways', 'wcflow_get_payment_gateways_ajax');

// Apply discount code
function wcflow_apply_discount_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $discount_code = sanitize_text_field($_POST['discount_code']);
    
    if (empty($discount_code)) {
        wp_send_json_error(['message' => 'Please enter a discount code.']);
    }
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    $coupon = new WC_Coupon($discount_code);
    
    if (!$coupon->is_valid()) {
        wp_send_json_error(['message' => 'Invalid or expired discount code.']);
    }
    
    if (WC()->cart->has_discount($discount_code)) {
        wp_send_json_error(['message' => 'This discount code has already been applied.']);
    }
    
    $result = WC()->cart->apply_coupon($discount_code);
    
    if ($result) {
        WC()->cart->calculate_totals();
        $discount_amount = WC()->cart->get_coupon_discount_amount($discount_code);
        
        wcflow_log('Discount applied: ' . $discount_code . ' - Amount: ' . $discount_amount);
        wp_send_json_success([
            'message' => 'Discount code applied successfully!',
            'discount_amount' => wc_price($discount_amount),
            'new_total' => WC()->cart->get_total()
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to apply discount code.']);
    }
}
add_action('wp_ajax_wcflow_apply_discount', 'wcflow_apply_discount_ajax');
add_action('wp_ajax_nopriv_wcflow_apply_discount', 'wcflow_apply_discount_ajax');

// Remove cart item
function wcflow_remove_cart_item_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    
    if (empty($cart_item_key)) {
        wp_send_json_error(['message' => 'Invalid cart item.']);
    }
    
    $result = WC()->cart->remove_cart_item($cart_item_key);
    
    if ($result) {
        WC()->cart->calculate_totals();
        wcflow_log('Cart item removed: ' . $cart_item_key);
        wp_send_json_success(['message' => 'Item removed from cart.']);
    } else {
        wp_send_json_error(['message' => 'Failed to remove item from cart.']);
    }
}
add_action('wp_ajax_wcflow_remove_cart_item', 'wcflow_remove_cart_item_ajax');
add_action('wp_ajax_nopriv_wcflow_remove_cart_item', 'wcflow_remove_cart_item_ajax');

// Get terms and conditions content
function wcflow_get_terms_content_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $type = sanitize_text_field($_POST['type']); // 'terms' or 'privacy'
    
    if ($type === 'terms') {
        $page_id = wc_terms_and_conditions_page_id();
        $title = 'Terms and Conditions';
    } else {
        $page_id = wc_privacy_policy_page_id();
        $title = 'Privacy Policy';
    }
    
    if (!$page_id) {
        wp_send_json_error(['message' => ucfirst($type) . ' page not found.']);
    }
    
    $page = get_post($page_id);
    if (!$page) {
        wp_send_json_error(['message' => ucfirst($type) . ' page not found.']);
    }
    
    $content = '<h2>' . esc_html($page->post_title) . '</h2>';
    $content .= apply_filters('the_content', $page->post_content);
    
    wcflow_log('Terms content retrieved: ' . $type);
    wp_send_json_success(['content' => $content]);
}
add_action('wp_ajax_wcflow_get_terms_content', 'wcflow_get_terms_content_ajax');
add_action('wp_ajax_nopriv_wcflow_get_terms_content', 'wcflow_get_terms_content_ajax');

// FIXED: Create order with proper data validation and handling
function wcflow_create_order() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    // Get state data from POST
    $state = isset($_POST['state']) ? $_POST['state'] : [];
    
    // CRITICAL FIX: Also check session data as fallback
    $session_data = WC()->session->get('wcflow_customer_data', []);
    if (!empty($session_data)) {
        // Merge session data with state data, giving priority to state data
        $state = array_merge($session_data, $state);
        wcflow_log('Merged session data with state data');
    }
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    wcflow_log('Order creation attempt with state: ' . json_encode($state));
    
    // FIXED: Comprehensive validation with detailed error messages
    $validation_errors = [];
    
    // Validate required shipping fields
    $required_shipping_fields = [
        'shipping_first_name' => 'Shipping first name',
        'shipping_last_name' => 'Shipping last name', 
        'shipping_address_1' => 'Shipping address',
        'shipping_city' => 'Shipping city',
        'shipping_postcode' => 'Shipping postcode',
        'shipping_country' => 'Shipping country'
    ];
    
    foreach ($required_shipping_fields as $field => $label) {
        if (empty($state[$field]) || trim($state[$field]) === '') {
            $validation_errors[] = $label;
            wcflow_log('Missing required field: ' . $field . ' (value: ' . (isset($state[$field]) ? $state[$field] : 'undefined') . ')');
        }
    }
    
    // Email validation - require either customer_email or billing_email
    $has_valid_email = false;
    if (!empty($state['customer_email']) && trim($state['customer_email']) !== '' && is_email($state['customer_email'])) {
        $has_valid_email = true;
    }
    if (!empty($state['billing_email']) && trim($state['billing_email']) !== '' && is_email($state['billing_email'])) {
        $has_valid_email = true;
    }
    
    if (!$has_valid_email) {
        $validation_errors[] = 'Valid email address';
        wcflow_log('Missing or invalid email address');
    }
    
    // Payment method validation
    if (empty($state['payment_method'])) {
        $validation_errors[] = 'Payment method';
        wcflow_log('Missing payment method');
    }
    
    // If there are validation errors, return them
    if (!empty($validation_errors)) {
        $error_message = 'Required fields missing: ' . implode(', ', $validation_errors);
        wcflow_log('Validation failed: ' . $error_message);
        wp_send_json_error(['message' => $error_message]);
    }
    
    // Ensure both email fields are populated
    if (empty($state['customer_email']) && !empty($state['billing_email'])) {
        $state['customer_email'] = $state['billing_email'];
    }
    if (empty($state['billing_email']) && !empty($state['customer_email'])) {
        $state['billing_email'] = $state['customer_email'];
    }
    
    try {
        // Save customer data to session for backup
        WC()->session->set('wcflow_customer_data', $state);
        
        // Add selected addons and cards to cart
        if (!empty($state['addons'])) {
            WC()->session->set('wcflow_selected_addons', $state['addons']);
            foreach ($state['addons'] as $addon_id) {
                $addon_price = get_post_meta($addon_id, '_wcflow_price', true);
                if ($addon_price) {
                    WC()->cart->add_fee(get_the_title($addon_id), floatval($addon_price));
                }
            }
        }
        
        if (!empty($state['card_id'])) {
            $card_data = [
                'id' => $state['card_id'],
                'price_value' => get_post_meta($state['card_id'], '_wcflow_price', true)
            ];
            WC()->session->set('wcflow_selected_card', $card_data);
            WC()->session->set('wcflow_card_message', $state['card_message'] ?? '');
            
            if ($card_data['price_value']) {
                WC()->cart->add_fee(get_the_title($state['card_id']), floatval($card_data['price_value']));
            }
        }
        
        WC()->cart->calculate_totals();
        
        // Create order
        $order = wc_create_order();
        
        // Add cart items to order
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $order->add_product($product, $cart_item['quantity']);
        }
        
        // Add fees to order
        foreach (WC()->cart->get_fees() as $fee) {
            $item = new WC_Order_Item_Fee();
            $item->set_name($fee->name);
            $item->set_amount($fee->amount);
            $item->set_total($fee->total);
            $order->add_item($item);
        }
        
        // FIXED: Set customer email first
        $order->set_billing_email($state['billing_email']);
        
        // FIXED: Set shipping information (REQUIRED fields)
        $order->set_shipping_first_name($state['shipping_first_name']);
        $order->set_shipping_last_name($state['shipping_last_name']);
        $order->set_shipping_address_1($state['shipping_address_1']);
        $order->set_shipping_city($state['shipping_city']);
        $order->set_shipping_postcode($state['shipping_postcode']);
        $order->set_shipping_country($state['shipping_country']);
        
        // Set optional shipping phone
        if (!empty($state['shipping_phone'])) {
            $order->set_shipping_phone($state['shipping_phone']);
        }
        
        // Set billing information - use billing data if available, otherwise fallback to shipping
        $order->set_billing_first_name(!empty($state['billing_first_name']) ? $state['billing_first_name'] : $state['shipping_first_name']);
        $order->set_billing_last_name(!empty($state['billing_last_name']) ? $state['billing_last_name'] : $state['shipping_last_name']);
        $order->set_billing_address_1(!empty($state['billing_address_1']) ? $state['billing_address_1'] : $state['shipping_address_1']);
        $order->set_billing_city(!empty($state['billing_city']) ? $state['billing_city'] : $state['shipping_city']);
        $order->set_billing_postcode(!empty($state['billing_postcode']) ? $state['billing_postcode'] : $state['shipping_postcode']);
        $order->set_billing_country(!empty($state['billing_country']) ? $state['billing_country'] : $state['shipping_country']);
        
        // Set billing phone
        if (!empty($state['billing_phone'])) {
            $order->set_billing_phone($state['billing_phone']);
        } elseif (!empty($state['shipping_phone'])) {
            $order->set_billing_phone($state['shipping_phone']);
        }
        
        // Add shipping method
        if (!empty($state['shipping_method'])) {
            $chosen_methods = [$state['shipping_method']];
            WC()->session->set('chosen_shipping_methods', $chosen_methods);
            
            $packages = WC()->cart->get_shipping_packages();
            if (!empty($packages)) {
                $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
                if (isset($shipping_for_package['rates'][$chosen_methods[0]])) {
                    $rate = $shipping_for_package['rates'][$chosen_methods[0]];
                    $item = new WC_Order_Item_Shipping();
                    $item->set_method_title($rate->get_label());
                    $item->set_method_id($rate->get_id());
                    $item->set_total($rate->get_cost());
                    $order->add_item($item);
                }
            }
        }
        
        // Add delivery date and gifting flow metadata
        if (!empty($state['delivery_date'])) {
            $order->add_meta_data('_delivery_date', $state['delivery_date']);
            $order->add_meta_data('_wcflow_delivery_date', date('Y-m-d', strtotime($state['delivery_date'])));
        }
        
        // Add gifting flow metadata
        $order->add_meta_data('_wcflow_order', 'yes');
        $order->add_meta_data('_wcflow_version', defined('WCFLOW_VERSION') ? WCFLOW_VERSION : '4.3');
        $order->add_meta_data('_wcflow_created_at', current_time('mysql'));
        $order->add_meta_data('_wcflow_created_by', 'wcflow_plugin');
        
        if (is_user_logged_in()) {
            $order->add_meta_data('_wcflow_customer_id', get_current_user_id());
        }
        
        // Store all customer data as order meta for future reference
        $order->add_meta_data('_wcflow_customer_data', $state);
        
        $order->calculate_totals();
        
        // Set payment method
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($state['payment_method']) && isset($payment_gateways[$state['payment_method']])) {
            $order->set_payment_method($payment_gateways[$state['payment_method']]);
        }
        
        $order->update_status('pending', 'Order created via WooCommerce Gifting Flow');
        $order->save();
        
        wcflow_log('Order created successfully: #' . $order->get_id() . ' - Total: ' . $order->get_total() . ' - Email: ' . $state['billing_email']);
        
        WC()->cart->empty_cart();
        
        // Get payment URL for direct redirect
        $payment_url = $order->get_checkout_payment_url();
        
        wp_send_json_success([
            'order_id' => $order->get_id(),
            'payment_url' => $payment_url,
            'redirect_url' => $payment_url
        ]);
        
    } catch (Exception $e) {
        wcflow_log('Order creation failed: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Failed to create order: ' . $e->getMessage()]);
    }
}
add_action('wp_ajax_wcflow_create_order', 'wcflow_create_order');
add_action('wp_ajax_nopriv_wcflow_create_order', 'wcflow_create_order');