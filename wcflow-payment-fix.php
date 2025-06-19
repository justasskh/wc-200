<?php
/**
 * WooCommerce Gifting Flow - Payment Method Fix (full file)
 * Makes payment methods work properly inside the popup, ensures customer data, native look, and gateway JS.
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Payment_Fix {
    
    private $debug = true;
    
    public function __construct() {
        add_filter('woocommerce_get_checkout_payment_url', array($this, 'modify_payment_url'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_payment_scripts'), 100);
        add_filter('wcflow_get_step_template', array($this, 'add_payment_form_fields'), 10, 2);
        add_action('woocommerce_checkout_create_order', array($this, 'add_customer_data_to_order'), 10, 2);
        add_filter('woocommerce_checkout_fields', array($this, 'modify_checkout_fields'), 999);
        if ($this->debug) {
            add_action('wp_footer', array($this, 'add_debug_script'));
        }
    }
    
    public function enqueue_payment_scripts() {
        if (!is_checkout() && (is_product() || is_front_page() || is_home())) {
            wp_enqueue_script('woocommerce');
            wp_enqueue_script('wc-checkout');
            wp_enqueue_script('wc-credit-card-form');
            // Force all gateway scripts to load
            $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
            foreach ($available_gateways as $gateway) {
                if (method_exists($gateway, 'payment_scripts')) {
                    $gateway->payment_scripts();
                }
            }
            // Auto-initialize payment forms after AJAX load (step 3)
            wp_add_inline_script('wc-checkout', "
                jQuery(function($) {
                    $(document).on('wcflow_step_loaded', function(e, step) {
                        if (step === 3) {
                            setTimeout(function() {
                                $(document.body).trigger('init_checkout');
                                $(document.body).trigger('update_checkout');
                                $(document.body).trigger('wc-credit-card-form-init');
                                if ($('input[name=\"payment_method\"]:checked').length === 0) {
                                    $('input[name=\"payment_method\"]:first').prop('checked', true).trigger('change');
                                }
                            }, 300);
                        }
                    });
                    $(document).on('change', 'input[name=\"payment_method\"]', function() {
                        var method = $(this).val();
                        $('.payment_box').hide();
                        $('#payment_method_' + method + '_box').show();
                        setTimeout(function() {
                            $(document.body).trigger('wc-credit-card-form-init');
                        }, 1000);
                    });
                    // Customer data injection handled in wcflow-customer-data.js
                });
            ");
        }
    }

    public function add_payment_form_fields($template, $step) {
        if ($step !== 3) return $template;
        ob_start();
        $gateways = WC()->payment_gateways()->get_available_payment_gateways();
        wc_get_template('checkout/payment-methods.php', array(
            'available_gateways' => $gateways,
            'order_button_text'  => __('Place order', 'woocommerce'),
        ));
        $payment_methods = ob_get_clean();

        $form_html = '
        <form name="checkout" id="wcflow-checkout-form" method="post" class="checkout woocommerce-checkout">
            <div id="payment" class="woocommerce-checkout-payment">
                <div class="payment_methods methods">
                    ' . $payment_methods . '
                </div>
                <input type="hidden" name="_wpnonce" value="' . wp_create_nonce('woocommerce-process_checkout') . '">
                <input type="hidden" name="woocommerce-process-checkout-nonce" value="' . wp_create_nonce('woocommerce-process_checkout') . '">
                <input type="hidden" name="wcflow_checkout" value="1">
            </div>
        </form>';
        $new_template = str_replace('<!--PAYMENT_SECTION-->', $form_html, $template);
        $styles = '<style>
        .wc_payment_method {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .wc_payment_method:hover {
            background: #f9f9f9;
        }
        .wc_payment_method.active {
            border-color: #2271b1;
            background: #f0f7fb;
        }
        .wc_payment_method label {
            cursor: pointer;
            display: inline-block;
            width: calc(100% - 25px);
            vertical-align: middle;
        }
        .wc_payment_method input[type="radio"] {
            cursor: pointer;
            vertical-align: middle;
        }
        .payment_box {
            margin-top: 10px;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 4px;
        }
        .wc-credit-card-form {
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
        }
        .wc-credit-card-form-card-number,
        .wc-credit-card-form-card-expiry,
        .wc-credit-card-form-card-cvc {
            font-size: 1.5em !important;
            padding: 8px !important;
            background-color: #fff !important;
        }
        .wc-stripe-elements-field {
            padding: 10px !important;
            background: white !important;
        }
        </style>';
        return $new_template . $styles;
    }
    
    public function modify_payment_url($url, $order) {
        if (isset($_REQUEST['wcflow_checkout']) && $_REQUEST['wcflow_checkout'] === '1') {
            $order->update_status('pending', 'Order awaiting payment via WooCommerce Gifting Flow.');
            return add_query_arg(array(
                'action' => 'wcflow_process_checkout',
                'order_id' => $order->get_id(),
                'nonce' => wp_create_nonce('wcflow_checkout')
            ), admin_url('admin-ajax.php'));
        }
        return $url;
    }
    
    public function add_customer_data_to_order($order, $data) {
        if (!isset($_REQUEST['wcflow_checkout']) || $_REQUEST['wcflow_checkout'] !== '1') return;
        $customer_data = array();
        if (!empty($_POST['customer_data']) && is_array($_POST['customer_data'])) {
            $customer_data = $_POST['customer_data'];
        } elseif (WC()->session->get('wcflow_customer_data')) {
            $customer_data = WC()->session->get('wcflow_customer_data');
        }
        if (!empty($customer_data)) {
            if (!empty($customer_data['customer_email'])) {
                $order->set_billing_email($customer_data['customer_email']);
            }
            if (!empty($customer_data['shipping_first_name'])) {
                $order->set_billing_first_name($customer_data['shipping_first_name']);
                $order->set_shipping_first_name($customer_data['shipping_first_name']);
            }
            if (!empty($customer_data['shipping_last_name'])) {
                $order->set_billing_last_name($customer_data['shipping_last_name']);
                $order->set_shipping_last_name($customer_data['shipping_last_name']);
            }
            if (!empty($customer_data['shipping_address_1'])) {
                $order->set_billing_address_1($customer_data['shipping_address_1']);
                $order->set_shipping_address_1($customer_data['shipping_address_1']);
            }
            if (!empty($customer_data['shipping_city'])) {
                $order->set_billing_city($customer_data['shipping_city']);
                $order->set_shipping_city($customer_data['shipping_city']);
            }
            if (!empty($customer_data['shipping_postcode'])) {
                $order->set_billing_postcode($customer_data['shipping_postcode']);
                $order->set_shipping_postcode($customer_data['shipping_postcode']);
            }
            if (!empty($customer_data['shipping_country'])) {
                $order->set_billing_country($customer_data['shipping_country']);
                $order->set_shipping_country($customer_data['shipping_country']);
            }
            if (!empty($customer_data['shipping_phone'])) {
                $order->set_billing_phone($customer_data['shipping_phone']);
            }
        }
    }

    public function modify_checkout_fields($fields) {
        if (!isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email'] = array(
                'label'       => __('Email address', 'woocommerce'),
                'required'    => true,
                'class'       => array('form-row-wide'),
                'clear'       => true,
                'priority'    => 10,
                'type'        => 'email',
            );
        }
        return $fields;
    }

    public function add_debug_script() {
        ?>
        <script>
        window.WCFLOW_DEBUG = true;
        </script>
        <?php
    }
}
new WCFlow_Payment_Fix();