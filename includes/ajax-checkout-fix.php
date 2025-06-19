<?php
/**
 * WooCommerce Gifting Flow - AJAX Checkout Fix
 * Handles proper WooCommerce checkout within popup
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-06-19
 * @updated 2025-06-19 10:05:31 UTC
 */

if (!defined('ABSPATH')) exit;

// Get WooCommerce checkout form with all required fields and scripts
function wcflow_get_checkout_form_ajax() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }

    // Ensure we have a checkout object
    WC()->checkout();
    
    // Load checkout scripts and styles
    wp_enqueue_script('woocommerce');
    wp_enqueue_script('wc-checkout');
    wp_enqueue_script('wc-address-i18n');
    wp_enqueue_script('wc-credit-card-form');
    wp_enqueue_style('woocommerce-general');
    wp_enqueue_style('woocommerce-layout');
    wp_enqueue_style('woocommerce-smallscreen');

    // Get payment gateways and their forms
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
    
    ob_start();
    ?>
    <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
        
        <!-- Hidden fields required for WooCommerce checkout -->
        <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
        <?php wp_nonce_field('wc_checkout_nonce', '_wpnonce'); ?>
        <input type="hidden" name="woocommerce_checkout_update_totals" value="1" />
        
        <!-- Customer email (this was missing and causing your error) -->
        <input type="hidden" name="billing_email" id="billing_email" value="" />
        <input type="hidden" name="billing_first_name" id="billing_first_name" value="" />
        <input type="hidden" name="billing_last_name" id="billing_last_name" value="" />
        <input type="hidden" name="billing_phone" id="billing_phone" value="" />
        
        <!-- Shipping fields -->
        <input type="hidden" name="shipping_first_name" id="shipping_first_name" value="" />
        <input type="hidden" name="shipping_last_name" id="shipping_last_name" value="" />
        <input type="hidden" name="shipping_address_1" id="shipping_address_1" value="" />
        <input type="hidden" name="shipping_city" id="shipping_city" value="" />
        <input type="hidden" name="shipping_postcode" id="shipping_postcode" value="" />
        <input type="hidden" name="shipping_country" id="shipping_country" value="" />
        
        <!-- Shipping method -->
        <input type="hidden" name="shipping_method[0]" id="shipping_method_0" value="" />
        
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
        
        <!-- Error container -->
        <div id="wcflow-payment-error" class="woocommerce-error" style="display: none;"></div>
        
    </form>
    
    <!-- Load payment gateway scripts inline -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize WooCommerce checkout
            var checkout = {
                init: function() {
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
                },
                
                updateCustomerData: function(customerData) {
                    $('#billing_email').val(customerData.customer_email || '');
                    $('#billing_first_name').val(customerData.shipping_first_name || '');
                    $('#billing_last_name').val(customerData.shipping_last_name || '');
                    $('#billing_phone').val(customerData.shipping_phone || '');
                    
                    $('#shipping_first_name').val(customerData.shipping_first_name || '');
                    $('#shipping_last_name').val(customerData.shipping_last_name || '');
                    $('#shipping_address_1').val(customerData.shipping_address_1 || '');
                    $('#shipping_city').val(customerData.shipping_city || '');
                    $('#shipping_postcode').val(customerData.shipping_postcode || '');
                    $('#shipping_country').val(customerData.shipping_country || '');
                    $('#shipping_method_0').val(customerData.shipping_method || '');
                }
            };
            
            // Make checkout object globally available
            window.wcflowCheckout = checkout;
            checkout.init();
        });
    </script>
    <?php
    
    $html = ob_get_clean();
    
    // Get required scripts URLs for loading
    $scripts = [
        'woocommerce' => includes_url('js/dist/vendor/wp-polyfill.min.js'),
        'wc-checkout' => WC()->plugin_url() . '/assets/js/frontend/checkout.min.js',
        'wc-credit-card-form' => WC()->plugin_url() . '/assets/js/frontend/credit-card-form.min.js'
    ];
    
    wcflow_log('Checkout form generated with ' . count($available_gateways) . ' payment gateways');
    wp_send_json_success([
        'html' => $html,
        'scripts' => $scripts,
        'payment_methods' => array_keys($available_gateways)
    ]);
}
add_action('wp_ajax_wcflow_get_checkout_form', 'wcflow_get_checkout_form_ajax');
add_action('wp_ajax_nopriv_wcflow_get_checkout_form', 'wcflow_get_checkout_form_ajax');

// Process checkout using WooCommerce native AJAX
function wcflow_process_checkout_ajax() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['woocommerce-process-checkout-nonce'], 'woocommerce-process_checkout')) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }
    
    if (WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty.']);
    }

    // Process using WooCommerce checkout
    wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
    wc_maybe_define_constant('WC_DOING_AJAX', true);
    
    // Get checkout instance
    $checkout = WC()->checkout();
    
    try {
        // Process the checkout
        $order_id = $checkout->process_checkout();
        
        if (is_wp_error($order_id)) {
            throw new Exception($order_id->get_error_message());
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Order not found.');
        }
        
        // Add gifting flow metadata
        $order->add_meta_data('_wcflow_order', 'yes');
        $order->add_meta_data('_wcflow_version', WCFLOW_VERSION);
        $order->add_meta_data('_wcflow_created_at', '2025-06-19 10:05:31');
        $order->save();
        
        // Clear cart
        WC()->cart->empty_cart();
        
        // Get payment result
        $result = apply_filters('woocommerce_payment_successful_result', [
            'result'   => 'success',
            'redirect' => $checkout->get_checkout_order_received_url($order),
        ], $order_id);
        
        wcflow_log('Checkout processed successfully: Order #' . $order_id);
        
        // Return success with payment result
        wp_send_json_success([
            'result' => $result['result'],
            'redirect' => $result['redirect'],
            'order_id' => $order_id,
            'order_key' => $order->get_order_key(),
            'payment_method' => $order->get_payment_method()
        ]);
        
    } catch (Exception $e) {
        wcflow_log('Checkout processing failed: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_wcflow_process_checkout', 'wcflow_process_checkout_ajax');
add_action('wp_ajax_nopriv_wcflow_process_checkout', 'wcflow_process_checkout_ajax');