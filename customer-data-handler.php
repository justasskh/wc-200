<?php
/**
 * WooCommerce Gifting Flow - Customer Data Handler
 * Enhanced data validation and transfer between steps
 * Updated: 2025-06-19 12:09:19 UTC
 */

if (!defined('ABSPATH')) exit;

// Save customer data to session
add_action('wp_ajax_wcflow_save_customer_data', 'wcflow_save_customer_data');
add_action('wp_ajax_nopriv_wcflow_save_customer_data', 'wcflow_save_customer_data');

function wcflow_save_customer_data() {
    check_ajax_referer('wcflow_nonce', 'nonce');
    
    $customer_data = $_POST['customer_data'] ?? [];
    
    if (!empty($customer_data) && is_array($customer_data)) {
        $sanitized_data = [];
        foreach ($customer_data as $key => $value) {
            if (in_array($key, array(
                'customer_email',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_address_1',
                'shipping_city',
                'shipping_postcode',
                'shipping_country',
                'shipping_phone',
                'billing_first_name',
                'billing_last_name',
                'billing_address_1',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'billing_phone',
                'billing_email'
            ))) {
                $sanitized_data[$key] = sanitize_text_field($value);
            }
        }
        WC()->session->set('wcflow_customer_data', $sanitized_data);
        wp_send_json_success(array(
            'message' => 'Customer data saved to session',
            'data' => $sanitized_data
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'No customer data provided'
        ));
    }
}

// Enhanced order data handler with comprehensive validation
add_action('woocommerce_checkout_create_order', 'wcflow_add_customer_data_to_new_order', 10, 2);

function wcflow_add_customer_data_to_new_order($order, $data) {
    // Get customer data from POST (preferred) or session (fallback)
    $customer_data = isset($_POST['customer_data']) ? $_POST['customer_data'] : WC()->session->get('wcflow_customer_data');
    
    if (!empty($customer_data) && is_array($customer_data)) {
        // IMPROVED: Handle email - prefer customer_email, fallback to billing_email
        $email = !empty($customer_data['customer_email']) ? $customer_data['customer_email'] : (!empty($customer_data['billing_email']) ? $customer_data['billing_email'] : '');
        if (!empty($email) && is_email($email)) {
            $order->set_billing_email($email);
        }
        
        // CRITICAL: Set shipping information first (required fields)
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
        
        // Handle billing fields - use billing data if available, otherwise use shipping data
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
        
        // Store all customer data as order meta for debugging and future reference
        $order->add_meta_data('_wcflow_original_customer_data', $customer_data);
        $order->add_meta_data('_wcflow_data_handler_processed', current_time('mysql'));
    }
}

// Enhanced customer data retrieval from session
function wcflow_get_customer_data_from_session() {
    return WC()->session->get('wcflow_customer_data', array());
}

// Clear customer data from session after order completion
add_action('woocommerce_thankyou', 'wcflow_clear_customer_data_session');

function wcflow_clear_customer_data_session($order_id) {
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_meta('_wcflow_order') === 'yes') {
            WC()->session->__unset('wcflow_customer_data');
            WC()->session->__unset('wcflow_selected_addons');
            WC()->session->__unset('wcflow_selected_card');
            WC()->session->__unset('wcflow_card_message');
        }
    }
}

// Validation helper function
function wcflow_validate_customer_data($data) {
    $errors = array();
    
    // Required shipping fields
    $required_fields = array(
        'shipping_first_name' => 'Shipping first name',
        'shipping_last_name' => 'Shipping last name',
        'shipping_address_1' => 'Shipping address',
        'shipping_city' => 'Shipping city',
        'shipping_postcode' => 'Shipping postcode',
        'shipping_country' => 'Shipping country'
    );
    
    foreach ($required_fields as $field => $label) {
        if (empty($data[$field]) || trim($data[$field]) === '') {
            $errors[] = $label . ' is required';
        }
    }
    
    // Email validation
    $has_valid_email = false;
    if (!empty($data['customer_email']) && is_email($data['customer_email'])) {
        $has_valid_email = true;
    }
    if (!empty($data['billing_email']) && is_email($data['billing_email'])) {
        $has_valid_email = true;
    }
    
    if (!$has_valid_email) {
        $errors[] = 'Valid email address is required';
    }
    
    return $errors;
}

// Debug function to log customer data
function wcflow_debug_customer_data($data, $context = 'unknown') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[WCFlow Customer Data - ' . $context . '] ' . print_r($data, true));
    }
}