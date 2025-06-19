<?php
/**
 * WooCommerce Gift AJAX Handler
 *
 * Handles AJAX requests for the gifting flow
 */
class WC_Gift_AJAX {
    
    /**
     * Process step 2 form submission
     */
    public function process_step2() {
        check_ajax_referer('gift_flow_nonce', 'security');
        
        $errors = array();
        
        // Validate required fields
        $required_fields = array(
            'shipping_first_name' => __('Shipping first name', 'wc-gifting-flow'),
            'shipping_last_name'  => __('Shipping last name', 'wc-gifting-flow'),
            'shipping_address_1'  => __('Shipping address', 'wc-gifting-flow'),
            'shipping_city'       => __('Shipping city', 'wc-gifting-flow'),
            'shipping_postcode'   => __('Shipping postcode', 'wc-gifting-flow'),
            'shipping_country'    => __('Shipping country', 'wc-gifting-flow'),
            'recipient_email'     => __('Valid email address', 'wc-gifting-flow')
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $errors[] = sprintf(__('%s is required', 'wc-gifting-flow'), $label);
            }
        }
        
        // Validate email format
        if (!empty($_POST['recipient_email']) && !is_email($_POST['recipient_email'])) {
            $errors[] = __('Please provide a valid email address', 'wc-gifting-flow');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'errors' => $errors
            ));
            return;
        }
        
        // FIXED: Save form data to session
        $session_handler = new WC_Gift_Session();
        $session_handler->save_step2_data();
        
        wp_send_json_success(array(
            'redirect' => wc_get_page_permalink('checkout') . '?step=3'
        ));
    }
    
    /**
     * Create order from gifting flow
     */
    public function create_gift_order() {
        check_ajax_referer('gift_flow_nonce', 'security');
        
        // FIXED: Use session data for order creation
        $session_handler = new WC_Gift_Session();
        $gift_data = $session_handler->get_gift_data();
        
        if (empty($gift_data) || empty($gift_data['shipping'])) {
            wp_send_json_error(array(
                'message' => __('Required fields missing: Shipping information is required', 'wc-gifting-flow')
            ));
            return;
        }
        
        // Create order
        $checkout_handler = new WC_Gift_Checkout();
        $order_id = $checkout_handler->process_checkout();
        
        if (!$order_id) {
            wp_send_json_error(array(
                'message' => __('Failed to create order', 'wc-gifting-flow')
            ));
            return;
        }
        
        // Clear session after successful order creation
        $session_handler->clear_gift_data();
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'redirect' => wc_get_endpoint_url('order-received', $order_id, wc_get_page_permalink('checkout'))
        ));
    }
}