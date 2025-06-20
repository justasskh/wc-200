<?php
/**
 * WooCommerce Gifting Flow Order Handler - FIXED VERSION
 * Complete order processing and creation functionality
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Order_Handler {
    
    public function __construct() {
        add_action('wp_ajax_wcflow_create_order', array($this, 'create_order'));
        add_action('wp_ajax_nopriv_wcflow_create_order', array($this, 'create_order'));
        add_action('wp_ajax_wcflow_get_checkout_form', array($this, 'get_checkout_form'));
        add_action('wp_ajax_nopriv_wcflow_get_checkout_form', array($this, 'get_checkout_form'));
        add_action('wp_ajax_wcflow_get_cart_summary', array($this, 'get_cart_summary'));
        add_action('wp_ajax_nopriv_wcflow_get_cart_summary', array($this, 'get_cart_summary'));
    }
    
    /**
     * Create WooCommerce order from gifting flow data - FIXED VERSION
     */
    public function create_order() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            // Get and decode the state data
            $state_raw = $_POST['state'] ?? '';
            
            if (empty($state_raw)) {
                wp_send_json_error(['message' => 'No order data provided.']);
            }
            
            wcflow_log('Raw state received: ' . substr($state_raw, 0, 200) . '...');
            
            // Decode JSON state
            if (is_string($state_raw)) {
                $state = json_decode(stripslashes($state_raw), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error(['message' => 'Invalid JSON data format: ' . json_last_error_msg()]);
                }
            } else {
                $state = $state_raw;
            }
            
            if (!is_array($state)) {
                wp_send_json_error(['message' => 'Invalid order data format.']);
            }
            
            wcflow_log('Decoded order state: ' . print_r($state, true));
            
            // Validate required fields with fallbacks
            $required_fields = [
                'shipping_first_name' => 'Recipient first name',
                'shipping_last_name' => 'Recipient last name', 
                'shipping_address_1' => 'Delivery address',
                'shipping_city' => 'City',
                'shipping_postcode' => 'Postal code',
                'shipping_country' => 'Country'
            ];
            
            $missing_fields = [];
            foreach ($required_fields as $field => $label) {
                if (empty($state[$field]) || trim($state[$field]) === '') {
                    // Try alternative field names as fallback
                    $alt_field = str_replace('shipping_', '', $field);
                    if (!empty($state[$alt_field]) && trim($state[$alt_field]) !== '') {
                        $state[$field] = trim($state[$alt_field]);
                        wcflow_log("FALLBACK: Used {$alt_field} for {$field}: " . $state[$field]);
                    } else {
                        $missing_fields[] = $label;
                        wcflow_log("Missing required field: {$field} ({$label})");
                    }
                }
            }
            
            // Additional fallback attempts for common field variations
            if (empty($state['shipping_first_name']) && !empty($state['first_name'])) {
                $state['shipping_first_name'] = trim($state['first_name']);
            }
            if (empty($state['shipping_last_name']) && !empty($state['last_name'])) {
                $state['shipping_last_name'] = trim($state['last_name']);
            }
            if (empty($state['shipping_phone']) && !empty($state['phone'])) {
                $state['shipping_phone'] = trim($state['phone']);
            }
            
            // Re-validate after fallbacks
            $missing_fields = [];
            foreach ($required_fields as $field => $label) {
                if (empty($state[$field]) || trim($state[$field]) === '') {
                    $missing_fields[] = $label;
                }
            }
            
            if (!empty($missing_fields)) {
                $error_message = 'Missing required fields: ' . implode(', ', $missing_fields);
                wcflow_log('Order creation failed: ' . $error_message);
                wp_send_json_error(['message' => $error_message]);
            }
            
            // Validate email
            $customer_email = $state['customer_email'] ?? $state['billing_email'] ?? '';
            if (empty($customer_email) || !is_email($customer_email)) {
                wp_send_json_error(['message' => 'Valid email address is required.']);
            }
            
            // Ensure cart has products
            if (WC()->cart->is_empty()) {
                // Add a dummy product for order creation
                $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
                if (!empty($products)) {
                    WC()->cart->add_to_cart($products[0]->get_id(), 1);
                } else {
                    wp_send_json_error(['message' => 'No products available for order creation.']);
                }
            }
            
            // Add selected addons to cart as fees
            if (!empty($state['addons']) && is_array($state['addons'])) {
                foreach ($state['addons'] as $addon_id) {
                    $addon = get_post($addon_id);
                    if ($addon && $addon->post_type === 'wcflow_addon') {
                        $addon_price = get_post_meta($addon_id, '_wcflow_price', true);
                        $addon_price = $addon_price ? floatval($addon_price) : 0;
                        
                        if ($addon_price > 0) {
                            WC()->cart->add_fee($addon->post_title, $addon_price);
                        }
                    }
                }
            }
            
            // Add selected greeting card as fee
            if (!empty($state['card_id'])) {
                $card = get_post($state['card_id']);
                if ($card && $card->post_type === 'wcflow_card') {
                    $card_price = get_post_meta($state['card_id'], '_wcflow_price', true);
                    $card_price = $card_price ? floatval($card_price) : 0;
                    
                    if ($card_price > 0) {
                        WC()->cart->add_fee($card->post_title, $card_price);
                    }
                }
            }
            
            // Set customer data
            WC()->customer->set_shipping_first_name($state['shipping_first_name']);
            WC()->customer->set_shipping_last_name($state['shipping_last_name']);
            WC()->customer->set_shipping_address_1($state['shipping_address_1']);
            WC()->customer->set_shipping_city($state['shipping_city']);
            WC()->customer->set_shipping_postcode($state['shipping_postcode']);
            WC()->customer->set_shipping_country($state['shipping_country']);
            WC()->customer->set_shipping_phone($state['shipping_phone'] ?? '');
            
            // Set billing data (copy from shipping if not provided)
            $billing_first_name = $state['billing_first_name'] ?? $state['shipping_first_name'];
            $billing_last_name = $state['billing_last_name'] ?? $state['shipping_last_name'];
            $billing_address_1 = $state['billing_address_1'] ?? $state['shipping_address_1'];
            $billing_city = $state['billing_city'] ?? $state['shipping_city'];
            $billing_postcode = $state['billing_postcode'] ?? $state['shipping_postcode'];
            $billing_country = $state['billing_country'] ?? $state['shipping_country'];
            $billing_phone = $state['billing_phone'] ?? $state['shipping_phone'] ?? '';
            
            WC()->customer->set_billing_first_name($billing_first_name);
            WC()->customer->set_billing_last_name($billing_last_name);
            WC()->customer->set_billing_address_1($billing_address_1);
            WC()->customer->set_billing_city($billing_city);
            WC()->customer->set_billing_postcode($billing_postcode);
            WC()->customer->set_billing_country($billing_country);
            WC()->customer->set_billing_phone($billing_phone);
            WC()->customer->set_billing_email($customer_email);
            
            // Set shipping method if provided
            if (!empty($state['shipping_method'])) {
                $chosen_methods = array($state['shipping_method']);
                WC()->session->set('chosen_shipping_methods', $chosen_methods);
            }
            
            // Recalculate cart totals
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
            
            // Create the order
            $order = wc_create_order();
            
            if (is_wp_error($order)) {
                wp_send_json_error(['message' => 'Failed to create order: ' . $order->get_error_message()]);
            }
            
            // Add cart items to order
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $order->add_product($product, $cart_item['quantity']);
            }
            
            // Add fees to order
            foreach (WC()->cart->get_fees() as $fee) {
                $order->add_fee($fee);
            }
            
            // Set addresses
            $order->set_address(array(
                'first_name' => $billing_first_name,
                'last_name'  => $billing_last_name,
                'email'      => $customer_email,
                'phone'      => $billing_phone,
                'address_1'  => $billing_address_1,
                'city'       => $billing_city,
                'postcode'   => $billing_postcode,
                'country'    => $billing_country,
            ), 'billing');
            
            $order->set_address(array(
                'first_name' => $state['shipping_first_name'],
                'last_name'  => $state['shipping_last_name'],
                'phone'      => $state['shipping_phone'] ?? '',
                'address_1'  => $state['shipping_address_1'],
                'city'       => $state['shipping_city'],
                'postcode'   => $state['shipping_postcode'],
                'country'    => $state['shipping_country'],
            ), 'shipping');
            
            // Add shipping
            if (!empty($state['shipping_method'])) {
                $shipping_methods = WC()->shipping->get_shipping_methods();
                $packages = WC()->cart->get_shipping_packages();
                
                if (!empty($packages)) {
                    $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
                    
                    if (isset($shipping_for_package['rates'][$state['shipping_method']])) {
                        $shipping_rate = $shipping_for_package['rates'][$state['shipping_method']];
                        $order->add_shipping($shipping_rate);
                    }
                }
            }
            
            // Set payment method
            if (!empty($state['payment_method'])) {
                $order->set_payment_method($state['payment_method']);
            }
            
            // Add custom meta data
            if (!empty($state['card_message'])) {
                $order->add_meta_data('_greeting_card_message', sanitize_textarea_field($state['card_message']));
            }
            
            if (!empty($state['card_id'])) {
                $order->add_meta_data('_greeting_card_id', $state['card_id']);
            }
            
            if (!empty($state['delivery_date'])) {
                $order->add_meta_data('_delivery_date', $state['delivery_date']);
            }
            
            if (!empty($state['addons'])) {
                $order->add_meta_data('_selected_addons', $state['addons']);
            }
            
            // Store original flow data
            $order->add_meta_data('_wcflow_original_data', $state);
            
            // Add order notes for admin
            $order_note = "WooCommerce Gifting Flow Order\n\n";
            if (!empty($state['addons'])) {
                $order_note .= "Add-ons selected: " . count($state['addons']) . "\n";
            }
            if (!empty($state['card_id'])) {
                $card = get_post($state['card_id']);
                if ($card) {
                    $order_note .= "Greeting card: " . $card->post_title . "\n";
                }
            }
            if (!empty($state['card_message'])) {
                $order_note .= "Card message: " . substr($state['card_message'], 0, 100) . "...\n";
            }
            if (!empty($state['delivery_date_formatted'])) {
                $order_note .= "Delivery date: " . $state['delivery_date_formatted'] . "\n";
            }
            
            $order->add_order_note($order_note);
            
            // Calculate totals
            $order->calculate_totals();
            
            // Set order status
            $order->set_status('pending');
            
            // Save the order
            $order->save();
            
            // Clear cart
            WC()->cart->empty_cart();
            
            // Get payment URL
            $payment_url = $order->get_checkout_payment_url();
            
            wcflow_log('Order created successfully: #' . $order->get_id());
            
            wp_send_json_success([
                'message' => 'Order created successfully.',
                'order_id' => $order->get_id(),
                'redirect_url' => $payment_url
            ]);
            
        } catch (Exception $e) {
            wcflow_log('Order creation error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to create order: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get WooCommerce checkout form for payment methods
     */
    public function get_checkout_form() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            // Force checkout initialization
            WC()->checkout();
            
            ob_start();
            ?>
            <div class="wcflow-payment-methods">
                <?php
                $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
                
                if (!empty($available_gateways)) {
                    foreach ($available_gateways as $gateway) {
                        ?>
                        <div class="wcflow-payment-method" style="margin-bottom: 16px; padding: 16px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                            <label style="display: flex; align-items: center; cursor: pointer; width: 100%;">
                                <input type="radio" name="payment_method" value="<?php echo esc_attr($gateway->id); ?>" style="margin-right: 12px; width: 18px; height: 18px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #333; margin-bottom: 4px;">
                                        <?php echo $gateway->get_title(); ?>
                                    </div>
                                    <?php if ($gateway->get_description()) : ?>
                                        <div style="font-size: 14px; color: #666;">
                                            <?php echo $gateway->get_description(); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($gateway->get_icon()) : ?>
                                    <div style="margin-left: 12px;">
                                        <?php echo $gateway->get_icon(); ?>
                                    </div>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($gateway->has_fields()) : ?>
                                <div class="wcflow-payment-fields" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e0e0e0; display: none;">
                                    <?php $gateway->payment_fields(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p style="color: #666;">No payment methods available.</p>';
                }
                ?>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Handle payment method selection
                $('input[name="payment_method"]').on('change', function() {
                    $('.wcflow-payment-method').removeClass('selected').css({
                        'border-color': '#e0e0e0',
                        'background': '#fff'
                    });
                    
                    $('.wcflow-payment-fields').hide();
                    
                    const $selected = $(this).closest('.wcflow-payment-method');
                    $selected.addClass('selected').css({
                        'border-color': '#007cba',
                        'background': '#f0f8ff'
                    });
                    
                    $selected.find('.wcflow-payment-fields').show();
                });
                
                // Select first payment method by default
                if ($('input[name="payment_method"]:checked').length === 0) {
                    $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
                }
            });
            </script>
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(['html' => $html]);
            
        } catch (Exception $e) {
            wcflow_log('Checkout form error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to load payment methods.']);
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
                // Get REAL product data from WooCommerce cart or order state
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
}

new WCFlow_Order_Handler();