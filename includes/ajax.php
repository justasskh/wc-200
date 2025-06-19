<?php
/**
 * WooCommerce Gifting Flow AJAX Handlers
 * FIXED: 2025-01-27 - Added proper pricing and shipping methods
 */

if (!defined('ABSPATH')) exit;

// Debug logging helper
function wcflow_log($message) {
    if (get_option('wcflow_enable_debug') === 'yes' || (defined('WP_DEBUG') && WP_DEBUG)) {
        error_log('[WooCommerce Gifting Flow] ' . $message);
    }
}

// FIXED: Start flow with product price
function wcflow_start_flow() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
    }
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found.']);
    }
    
    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($product_id, 1);
    
    wcflow_log('Flow started for product ID: ' . $product_id);
    wp_send_json_success([
        'message' => 'Flow started successfully.',
        'product_price' => $product->get_price()
    ]);
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

// Get addons data
function wcflow_get_addons_data() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
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

// FIXED: Get cards data with proper categories
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
        $category = $terms && !is_wp_error($terms) ? $terms[0]->name : 'Gimtadienio ir švenčių atvirukai';
        
        $price_value = get_post_meta($card->ID, '_wcflow_price', true);
        $image_id = get_post_thumbnail_id($card->ID);
        $image_url = $image_id ? wp_get_attachment_image_src($image_id, 'medium')[0] : 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400';
        
        if (!isset($cards_by_category[$category])) {
            $cards_by_category[$category] = [];
        }
        
        $cards_by_category[$category][] = [
            'id' => $card->ID,
            'title' => $card->post_title,
            'price' => $price_value > 0 ? wc_price($price_value) : 'NEMOKAMA',
            'price_value' => floatval($price_value),
            'img' => $image_url
        ];
    }
    
    // FIXED: Create proper sample data with Lithuanian categories
    if (empty($cards_by_category)) {
        // First category: Populiariausi atvirukai
        $cards_by_category['Populiariausi atvirukai'] = [
            [
                'id' => 'sample-pop-1',
                'title' => 'Gimtadienio apkabinimai',
                'price' => 'NEMOKAMA',
                'price_value' => 0,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-pop-2',
                'title' => 'Birželio gimimo gėlė',
                'price' => wc_price(1.50),
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-pop-3',
                'title' => 'Su gimtadieniu!',
                'price' => wc_price(2.50),
                'price_value' => 2.50,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-pop-4',
                'title' => 'Šventinis sveikinimas',
                'price' => wc_price(1.75),
                'price_value' => 1.75,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ];
        
        // Second category: Gimtadienio ir švenčių atvirukai
        $cards_by_category['Gimtadienio ir švenčių atvirukai'] = [
            [
                'id' => 'sample-bday-1',
                'title' => 'Linksmų gimtadienio',
                'price' => wc_price(1.50),
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-bday-2',
                'title' => 'Šventinis tortas',
                'price' => wc_price(2.00),
                'price_value' => 2.00,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-bday-3',
                'title' => 'Gėlių puokštė',
                'price' => wc_price(1.75),
                'price_value' => 1.75,
                'img' => 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-bday-4',
                'title' => 'Šventinis balionas',
                'price' => wc_price(1.25),
                'price_value' => 1.25,
                'img' => 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
            ],
            [
                'id' => 'sample-bday-5',
                'title' => 'Gimtadienio linkėjimai',
                'price' => wc_price(1.50),
                'price_value' => 1.50,
                'img' => 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
            ]
        ];
    }
    
    wcflow_log('Cards data retrieved by category: ' . json_encode(array_keys($cards_by_category)));
    wp_send_json_success($cards_by_category);
}
add_action('wp_ajax_wcflow_get_cards', 'wcflow_get_cards_data');
add_action('wp_ajax_nopriv_wcflow_get_cards', 'wcflow_get_cards_data');

// FIXED: Get shipping methods with proper calculation
function wcflow_get_shipping_methods_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    // Set a default shipping address for calculation
    WC()->customer->set_shipping_country('GB');
    WC()->customer->set_shipping_postcode('SW1A 1AA');
    
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
                'cost_with_tax' => number_format($cost_with_tax, 2)
            ];
        }
    }
    
    // If no shipping methods, provide defaults
    if (empty($shipping_methods)) {
        $shipping_methods = [
            [
                'id' => 'flat_rate:1',
                'label' => 'Standard Delivery',
                'cost' => '4.99',
                'cost_with_tax' => '4.99'
            ],
            [
                'id' => 'free_shipping:1',
                'label' => 'Free Delivery',
                'cost' => '0.00',
                'cost_with_tax' => '0.00'
            ]
        ];
    }
    
    wcflow_log('Shipping methods retrieved: ' . count($shipping_methods) . ' methods');
    wp_send_json_success($shipping_methods);
}
add_action('wp_ajax_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');
add_action('wp_ajax_nopriv_wcflow_get_shipping_methods', 'wcflow_get_shipping_methods_ajax');

// Get cart summary
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
            ?>
            <div class="wcflow-basket-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                <div class="wcflow-basket-item-img">
                    <?php echo $_product->get_image('thumbnail'); ?>
                </div>
                <div class="wcflow-basket-item-details">
                    <p class="wcflow-basket-item-title"><?php echo $_product->get_name(); ?></p>
                    <p class="wcflow-basket-item-qty">Qty: <?php echo $cart_item['quantity']; ?></p>
                </div>
                <div class="wcflow-basket-item-actions">
                    <div class="wcflow-basket-item-price"><?php echo WC()->cart->get_product_price($_product); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="wcflow-basket-total">
            <strong>Total: <?php echo WC()->cart->get_total(); ?></strong>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    wcflow_log('Cart summary generated');
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_wcflow_get_cart_summary', 'wcflow_get_cart_summary_ajax');
add_action('wp_ajax_nopriv_wcflow_get_cart_summary', 'wcflow_get_cart_summary_ajax');

// Get checkout form
function wcflow_get_checkout_form_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }

    // Ensure we have a checkout object
    WC()->checkout();
    
    // Get payment gateways
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    ob_start();
    ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
        
        <!-- Hidden fields required for WooCommerce checkout -->
        <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
        <?php wp_nonce_field('wc_checkout_nonce', '_wpnonce'); ?>
        <input type="hidden" name="woocommerce_checkout_update_totals" value="1" />
        
        <!-- Customer data fields -->
        <input type="hidden" name="billing_email" id="billing_email" value="" />
        <input type="hidden" name="billing_first_name" id="billing_first_name" value="" />
        <input type="hidden" name="billing_last_name" id="billing_last_name" value="" />
        <input type="hidden" name="billing_phone" id="billing_phone" value="" />
        <input type="hidden" name="billing_address_1" id="billing_address_1" value="" />
        <input type="hidden" name="billing_city" id="billing_city" value="" />
        <input type="hidden" name="billing_postcode" id="billing_postcode" value="" />
        <input type="hidden" name="billing_country" id="billing_country" value="" />
        
        <!-- Shipping fields -->
        <input type="hidden" name="shipping_first_name" id="shipping_first_name" value="" />
        <input type="hidden" name="shipping_last_name" id="shipping_last_name" value="" />
        <input type="hidden" name="shipping_address_1" id="shipping_address_1" value="" />
        <input type="hidden" name="shipping_city" id="shipping_city" value="" />
        <input type="hidden" name="shipping_postcode" id="shipping_postcode" value="" />
        <input type="hidden" name="shipping_country" id="shipping_country" value="" />
        
        <!-- Payment Methods -->
        <div class="wcflow-payment-methods">
            <?php if (!empty($available_gateways)) : ?>
                <ul class="wc_payment_methods payment_methods methods">
                    <?php foreach ($available_gateways as $gateway) : ?>
                        <li class="wc_payment_method payment_method_<?php echo esc_attr($gateway->id); ?>">
                            <input id="payment_method_<?php echo esc_attr($gateway->id); ?>" 
                                   type="radio" 
                                   class="input-radio" 
                                   name="payment_method" 
                                   value="<?php echo esc_attr($gateway->id); ?>" 
                                   <?php checked($gateway->chosen, true); ?> />
                            
                            <label for="payment_method_<?php echo esc_attr($gateway->id); ?>" class="wcflow-payment-option">
                                <div class="wcflow-payment-method-title">
                                    <?php echo $gateway->get_title(); ?>
                                    <?php echo $gateway->get_icon(); ?>
                                </div>
                            </label>
                            
                            <?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
                                <div class="payment_box payment_method_<?php echo esc_attr($gateway->id); ?>" 
                                     style="display: none;">
                                    <?php $gateway->payment_fields(); ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="woocommerce-notice woocommerce-notice--error">
                    <?php esc_html_e('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Terms and conditions -->
        <div class="woocommerce-terms-and-conditions-wrapper">
            <?php if (wc_terms_and_conditions_checkbox_enabled()) : ?>
                <p class="form-row validate-required">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms" id="terms" />
                        <span class="woocommerce-terms-and-conditions-checkbox-text">
                            <?php printf(
                                esc_html__('I have read and agree to the website %s', 'woocommerce'),
                                '<a href="' . esc_url(wc_terms_and_conditions_page_url()) . '" class="woocommerce-terms-and-conditions-link" target="_blank">' . esc_html__('terms and conditions', 'woocommerce') . '</a>'
                            ); ?>
                        </span>
                        <span class="required">*</span>
                    </label>
                </p>
            <?php endif; ?>
        </div>
        
    </form>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize WooCommerce checkout
            $(document.body).trigger('init_checkout');
            $(document.body).trigger('update_checkout');
            $(document.body).trigger('wc-credit-card-form-init');
            
            // Select first payment method
            if ($('input[name="payment_method"]:checked').length === 0) {
                $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
            }
            
            // Payment method change handler
            $(document).on('change', 'input[name="payment_method"]', function() {
                var method = $(this).val();
                $('.payment_box').hide();
                $('.payment_method_' + method + ' .payment_box').show();
                $(document.body).trigger('payment_method_selected');
            });
        });
    </script>
    <?php
    
    $html = ob_get_clean();
    
    wcflow_log('Checkout form generated with ' . count($available_gateways) . ' payment gateways');
    wp_send_json_success([
        'html' => $html,
        'payment_methods' => array_keys($available_gateways)
    ]);
}
add_action('wp_ajax_wcflow_get_checkout_form', 'wcflow_get_checkout_form_ajax');
add_action('wp_ajax_nopriv_wcflow_get_checkout_form', 'wcflow_get_checkout_form_ajax');

// Create order
function wcflow_create_order() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $state = isset($_POST['state']) ? $_POST['state'] : [];
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }
    
    wcflow_log('Order creation attempt with state: ' . json_encode($state));
    
    // Validate required fields
    $validation_errors = [];
    
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
        }
    }
    
    // Email validation
    $has_valid_email = false;
    if (!empty($state['customer_email']) && is_email($state['customer_email'])) {
        $has_valid_email = true;
    }
    if (!empty($state['billing_email']) && is_email($state['billing_email'])) {
        $has_valid_email = true;
    }
    
    if (!$has_valid_email) {
        $validation_errors[] = 'Valid email address';
    }
    
    // Payment method validation
    if (empty($state['payment_method'])) {
        $validation_errors[] = 'Payment method';
    }
    
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
        // Add selected addons and cards to cart
        if (!empty($state['addons'])) {
            foreach ($state['addons'] as $addon_id) {
                $addon_price = get_post_meta($addon_id, '_wcflow_price', true);
                if ($addon_price) {
                    WC()->cart->add_fee(get_the_title($addon_id), floatval($addon_price));
                }
            }
        }
        
        if (!empty($state['card_id'])) {
            $card_price = get_post_meta($state['card_id'], '_wcflow_price', true);
            if ($card_price) {
                WC()->cart->add_fee(get_the_title($state['card_id']), floatval($card_price));
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
        
        // Set customer email
        $order->set_billing_email($state['billing_email']);
        
        // Set shipping information
        $order->set_shipping_first_name($state['shipping_first_name']);
        $order->set_shipping_last_name($state['shipping_last_name']);
        $order->set_shipping_address_1($state['shipping_address_1']);
        $order->set_shipping_city($state['shipping_city']);
        $order->set_shipping_postcode($state['shipping_postcode']);
        $order->set_shipping_country($state['shipping_country']);
        
        if (!empty($state['shipping_phone'])) {
            $order->set_shipping_phone($state['shipping_phone']);
        }
        
        // Set billing information
        $order->set_billing_first_name(!empty($state['billing_first_name']) ? $state['billing_first_name'] : $state['shipping_first_name']);
        $order->set_billing_last_name(!empty($state['billing_last_name']) ? $state['billing_last_name'] : $state['shipping_last_name']);
        $order->set_billing_address_1(!empty($state['billing_address_1']) ? $state['billing_address_1'] : $state['shipping_address_1']);
        $order->set_billing_city(!empty($state['billing_city']) ? $state['billing_city'] : $state['shipping_city']);
        $order->set_billing_postcode(!empty($state['billing_postcode']) ? $state['billing_postcode'] : $state['shipping_postcode']);
        $order->set_billing_country(!empty($state['billing_country']) ? $state['billing_country'] : $state['shipping_country']);
        
        if (!empty($state['billing_phone'])) {
            $order->set_billing_phone($state['billing_phone']);
        } elseif (!empty($state['shipping_phone'])) {
            $order->set_billing_phone($state['shipping_phone']);
        }
        
        // Add delivery date
        if (!empty($state['delivery_date'])) {
            $order->add_meta_data('_delivery_date', $state['delivery_date']);
        }
        
        // Add gifting flow metadata
        $order->add_meta_data('_wcflow_order', 'yes');
        $order->add_meta_data('_wcflow_version', WCFLOW_VERSION);
        $order->add_meta_data('_wcflow_customer_data', $state);
        
        $order->calculate_totals();
        
        // Set payment method
        $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!empty($state['payment_method']) && isset($payment_gateways[$state['payment_method']])) {
            $order->set_payment_method($payment_gateways[$state['payment_method']]);
        }
        
        $order->update_status('pending', 'Order created via WooCommerce Gifting Flow');
        $order->save();
        
        wcflow_log('Order created successfully: #' . $order->get_id());
        
        WC()->cart->empty_cart();
        
        // Get payment URL
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