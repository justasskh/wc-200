<?php
/**
 * WooCommerce Gifting Flow Checkout Handler
 * Recreated: 2025-01-27
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Checkout_Handler {
    
    public function __construct() {
        add_action('woocommerce_checkout_create_order', array($this, 'add_customer_data_to_order'), 10, 2);
        add_filter('woocommerce_checkout_fields', array($this, 'modify_checkout_fields'), 999);
    }
    
    public function add_customer_data_to_order($order, $data) {
        // Get customer data from POST or session
        $customer_data = isset($_POST['customer_data']) ? $_POST['customer_data'] : WC()->session->get('wcflow_customer_data');
        
        if (!empty($customer_data) && is_array($customer_data)) {
            // Set email
            $email = !empty($customer_data['customer_email']) ? $customer_data['customer_email'] : (!empty($customer_data['billing_email']) ? $customer_data['billing_email'] : '');
            if (!empty($email) && is_email($email)) {
                $order->set_billing_email($email);
            }
            
            // Set shipping information
            if (!empty($customer_data['shipping_first_name'])) {
                $order->set_shipping_first_name($customer_data['shipping_first_name']);
            }
            if (!empty($customer_data['shipping_last_name'])) {
                $order->set_shipping_last_name($customer_data['shipping_last_name']);
            }
            if (!empty($customer_data['shipping_address_1'])) {
                $order->set_shipping_address_1($customer_data['shipping_address_1']);
            }
            if (!empty($customer_data['shipping_city'])) {
                $order->set_shipping_city($customer_data['shipping_city']);
            }
            if (!empty($customer_data['shipping_postcode'])) {
                $order->set_shipping_postcode($customer_data['shipping_postcode']);
            }
            if (!empty($customer_data['shipping_country'])) {
                $order->set_shipping_country($customer_data['shipping_country']);
            }
            if (!empty($customer_data['shipping_phone'])) {
                $order->set_shipping_phone($customer_data['shipping_phone']);
            }
            
            // Set billing information
            if (!empty($customer_data['billing_first_name'])) {
                $order->set_billing_first_name($customer_data['billing_first_name']);
            } elseif (!empty($customer_data['shipping_first_name'])) {
                $order->set_billing_first_name($customer_data['shipping_first_name']);
            }
            
            if (!empty($customer_data['billing_last_name'])) {
                $order->set_billing_last_name($customer_data['billing_last_name']);
            } elseif (!empty($customer_data['shipping_last_name'])) {
                $order->set_billing_last_name($customer_data['shipping_last_name']);
            }
            
            if (!empty($customer_data['billing_address_1'])) {
                $order->set_billing_address_1($customer_data['billing_address_1']);
            } elseif (!empty($customer_data['shipping_address_1'])) {
                $order->set_billing_address_1($customer_data['shipping_address_1']);
            }
            
            if (!empty($customer_data['billing_city'])) {
                $order->set_billing_city($customer_data['billing_city']);
            } elseif (!empty($customer_data['shipping_city'])) {
                $order->set_billing_city($customer_data['shipping_city']);
            }
            
            if (!empty($customer_data['billing_postcode'])) {
                $order->set_billing_postcode($customer_data['billing_postcode']);
            } elseif (!empty($customer_data['shipping_postcode'])) {
                $order->set_billing_postcode($customer_data['shipping_postcode']);
            }
            
            if (!empty($customer_data['billing_country'])) {
                $order->set_billing_country($customer_data['billing_country']);
            } elseif (!empty($customer_data['shipping_country'])) {
                $order->set_billing_country($customer_data['shipping_country']);
            }
            
            if (!empty($customer_data['billing_phone'])) {
                $order->set_billing_phone($customer_data['billing_phone']);
            } elseif (!empty($customer_data['shipping_phone'])) {
                $order->set_billing_phone($customer_data['shipping_phone']);
            }
            
            // Store all customer data as order meta
            $order->add_meta_data('_wcflow_original_customer_data', $customer_data);
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
}

new WCFlow_Checkout_Handler();