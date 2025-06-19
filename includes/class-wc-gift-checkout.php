<?php
/**
 * WooCommerce Gift Checkout Handler
 *
 * Handles the checkout process for gifting flow
 */
class WC_Gift_Checkout {
    
    /**
     * Process checkout form data
     */
    public function process_checkout() {
        // Get form data
        $posted_data = $this->get_posted_data();
        
        // FIXED: Ensure shipping fields are properly transferred from session
        $gift_session = WC()->session->get('gift_flow_data');
        
        if (!empty($gift_session) && !empty($gift_session['shipping'])) {
            // Map session shipping data to WooCommerce expected format
            $shipping_fields = array(
                'shipping_first_name' => isset($gift_session['shipping']['first_name']) ? $gift_session['shipping']['first_name'] : '',
                'shipping_last_name'  => isset($gift_session['shipping']['last_name']) ? $gift_session['shipping']['last_name'] : '',
                'shipping_company'    => isset($gift_session['shipping']['company']) ? $gift_session['shipping']['company'] : '',
                'shipping_address_1'  => isset($gift_session['shipping']['address_1']) ? $gift_session['shipping']['address_1'] : '',
                'shipping_address_2'  => isset($gift_session['shipping']['address_2']) ? $gift_session['shipping']['address_2'] : '',
                'shipping_city'       => isset($gift_session['shipping']['city']) ? $gift_session['shipping']['city'] : '',
                'shipping_state'      => isset($gift_session['shipping']['state']) ? $gift_session['shipping']['state'] : '',
                'shipping_postcode'   => isset($gift_session['shipping']['postcode']) ? $gift_session['shipping']['postcode'] : '',
                'shipping_country'    => isset($gift_session['shipping']['country']) ? $gift_session['shipping']['country'] : '',
            );
            
            // Merge shipping fields with posted data
            $posted_data = array_merge($posted_data, $shipping_fields);
        }
        
        // Add recipient email to posted data if available
        if (!empty($gift_session['recipient_email'])) {
            $posted_data['billing_email'] = $gift_session['recipient_email'];
        }
        
        // Create the order
        $order_id = $this->create_order($posted_data);
        
        return $order_id;
    }
    
    /**
     * Create order from form data
     */
    private function create_order($data) {
        $order = wc_create_order();
        
        // FIXED: Ensure all required shipping fields are set
        if (!empty($data['shipping_first_name'])) {
            $order->set_shipping_first_name($data['shipping_first_name']);
        }
        
        if (!empty($data['shipping_last_name'])) {
            $order->set_shipping_last_name($data['shipping_last_name']);
        }
        
        if (!empty($data['shipping_address_1'])) {
            $order->set_shipping_address_1($data['shipping_address_1']);
        }
        
        if (!empty($data['shipping_city'])) {
            $order->set_shipping_city($data['shipping_city']);
        }
        
        if (!empty($data['shipping_postcode'])) {
            $order->set_shipping_postcode($data['shipping_postcode']);
        }
        
        if (!empty($data['shipping_country'])) {
            $order->set_shipping_country($data['shipping_country']);
        }
        
        if (!empty($data['billing_email'])) {
            $order->set_billing_email($data['billing_email']);
        }
        
        // Set other order details...
        
        $order->save();
        
        return $order->get_id();
    }
    
    /**
     * Get posted checkout form data
     */
    private function get_posted_data() {
        $data = array();
        
        // Get posted data from the form
        foreach ($_POST as $key => $value) {
            $data[$key] = $value;
        }
        
        return $data;
    }
}