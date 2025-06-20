<?php
/**
 * WooCommerce Gifting Flow Order Handler
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
     * Create WooCommerce order from gifting flow data
     */
    public function create_order() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
            // CRITICAL FIX: Properly decode the state data
            $state_raw = $_POST['state'] ?? '';
            
            if (empty($state_raw)) {
                wp_send_json_error(['message' => 'No order data provided.']);
            }
            
            wcflow_log('Raw state received: ' . print_r($state_raw, true));
            
            // ENHANCED: Handle both JSON string and array formats
            if (is_string($state_raw)) {
                // Try to decode as JSON first
                $state = json_decode(stripslashes($state_raw), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wcflow_log('JSON decode failed: ' . json_last_error_msg());
                    // If JSON decode fails, check if it's already an array in string format
                    if (is_array($state_raw)) {
                        $state = $state_raw;
                    } else {
                        wp_send_json_error(['message' => 'Invalid JSON data format: ' . json_last_error_msg()]);
                    }
                } else {
                    wcflow_log('JSON decoded successfully');
                }
            } else if (is_array($state_raw)) {
                $state = $state_raw;
                wcflow_log('State received as array');
            } else {
                wp_send_json_error(['message' => 'Invalid order data format - not string or array.']);
            }
            
            // Ensure we have an array
            if (!is_array($state)) {
                wcflow_log('Final state is not an array: ' . gettype($state));
                wp_send_json_error(['message' => 'Invalid order data format - final state not array.']);
            }
            
            // Log the received data for debugging
            wcflow_log('Received order state: ' . print_r($state, true));
            
            // ENHANCED: Validate required fields with better error messages and fallbacks
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
                    // CRITICAL: Try alternative field names as fallback
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
            
            // CRITICAL: Additional fallback attempts for common field variations
            if (empty($state['shipping_first_name']) && !empty($state['first_name'])) {
                $state['shipping_first_name'] = trim($state['first_name']);
                wcflow_log("FALLBACK: Used first_name for shipping_first_name");
            }
            if (empty($state['shipping_last_name']) && !empty($state['last_name'])) {
                $state['shipping_last_name'] = trim($state['last_name']);
                wcflow_log("FALLBACK: Used last_name for shipping_last_name");
            }
            if (empty($state['shipping_address_1']) && !empty($state['address_1'])) {
                $state['shipping_address_1'] = trim($state['address_1']);
                wcflow_log("FALLBACK: Used address_1 for shipping_address_1");
            }
            if (empty($state['shipping_city']) && !empty($state['city'])) {
                $state['shipping_city'] = trim($state['city']);
                wcflow_log("FALLBACK: Used city for shipping_city");
            }
            if (empty($state['shipping_postcode']) && !empty($state['postcode'])) {
                $state['shipping_postcode'] = trim($state['postcode']);
                wcflow_log("FALLBACK: Used postcode for shipping_postcode");
            }
            if (empty($state['shipping_country']) && !empty($state['country'])) {
                $state['shipping_country'] = trim($state['country']);
                wcflow_log("FALLBACK: Used country for shipping_country");
            }
            if (empty($state['shipping_phone']) && !empty($state['phone'])) {
                $state['shipping_phone'] = trim($state['phone']);
                wcflow_log("FALLBACK: Used phone for shipping_phone");
            }
            
            // Re-validate after fallbacks
            $missing_fields = [];
            foreach ($required_fields as $field => $label) {
                if (empty($state[$field]) || trim($state[$field]) === '') {
                    $missing_fields[] = $label;
                    wcflow_log("FINAL CHECK - Missing required field: {$field} ({$label})");
                }
            }
            
            if (!empty($missing_fields)) {
                $error_message = 'Missing required fields: ' . implode(', ', $missing_fields);
                wcflow_log('Order creation failed: ' . $error_message);
                wcflow_log('Complete state data: ' . print_r($state, true));
                wp_send_json_error(['message' => $error_message]);
            }
            
            // Ensure we have an email
            $customer_email = $state['customer_email'] ?? $state['billing_email'] ?? '';
            if (empty($customer_email) || !is_email($customer_email)) {
                wp_send_json_error(['message' => 'Valid email address is required.']);
            }
            
            // Check if cart is empty
            if (WC()->cart->is_empty()) {
                wp_send_json_error(['message' => 'Cart is empty.']);
            }
            
            // Add selected addons to cart
            if (!empty($state['addons']) && is_array($state['addons'])) {
                foreach ($state['addons'] as $addon_id) {
                    $addon = get_post($addon_id);
                    if ($addon && $addon->post_type === 'wcflow_addon') {
                        $addon_price = get_post_meta($addon_id, '_wcflow_price', true);
                        $addon_price = $addon_price ? floatval($addon_price) : 0;
                        
                        // Add addon as fee
                        WC()->cart->add_fee($addon->post_title, $addon_price);
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
            
            // Set shipping method if provided
            if (!empty($state['shipping_method'])) {
                $chosen_methods = array($state['shipping_method']);
                WC()->session->set('chosen_shipping_methods', $chosen_methods);
            }
            
            // Set customer data
            WC()->customer->set_shipping_first_name($state['shipping_first_name']);
            WC()->customer->set_shipping_last_name($state['shipping_last_name']);
            WC()->customer->set_shipping_address_1($state['shipping_address_1']);
            WC()->customer->set_shipping_city($state['shipping_city']);
            WC()->customer->set_shipping_postcode($state['shipping_postcode']);
            WC()->customer->set_shipping_country($state['shipping_country']);
            WC()->customer->set_shipping_phone($state['shipping_phone'] ?? '');
            
            // Set billing data
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
            
            if (WC()->cart->is_empty()) {
                wp_send_json_error(['message' => 'Cart is empty.']);
            }
            
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
     * Get cart summary for step 3
     */
    public function get_cart_summary() {
        try {
            check_ajax_referer('wcflow_frontend_nonce', 'nonce');
            
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
                    <div class="wcflow-basket-item" style="display:flex;align-items:center;padding:16px;border-bottom:1px solid #e0e0e0;">
                        <div class="wcflow-basket-item-img" style="width:60px;height:60px;margin-right:16px;">
                            <?php echo $_product->get_image('thumbnail'); ?>
                        </div>
                        <div class="wcflow-basket-item-details" style="flex:1;">
                            <p style="margin:0 0 4px 0;font-weight:600;color:#333;"><?php echo $_product->get_name(); ?></p>
                            <p style="margin:0;color:#666;font-size:14px;">Quantity: <?php echo $cart_item['quantity']; ?></p>
                        </div>
                        <div class="wcflow-basket-item-price" style="font-weight:700;color:#007cba;">
                            <?php echo WC()->cart->get_product_price($_product); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (WC()->cart->get_fees()) : ?>
                    <?php foreach (WC()->cart->get_fees() as $fee) : ?>
                        <div class="wcflow-basket-fee" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                            <span style="color:#666;"><?php echo esc_html($fee->name); ?></span>
                            <span style="font-weight:600;color:#333;"><?php echo wc_price($fee->total); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="wcflow-basket-subtotal" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                    <span style="color:#666;">Subtotal:</span>
                    <span style="font-weight:600;color:#333;"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                </div>
                
                <?php if (WC()->cart->get_shipping_total() > 0) : ?>
                    <div class="wcflow-basket-shipping" style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #e0e0e0;">
                        <span style="color:#666;">Shipping:</span>
                        <span style="font-weight:600;color:#333;"><?php echo WC()->cart->get_cart_shipping_total(); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="wcflow-basket-total" style="display:flex;justify-content:space-between;padding:16px;background:#f8f9fa;font-size:18px;">
                    <strong style="color:#333;">Total:</strong>
                    <strong style="color:#007cba;"><?php echo WC()->cart->get_total(); ?></strong>
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
