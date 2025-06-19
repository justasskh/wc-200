<?php
/**
 * WooCommerce Gift Session Handler
 *
 * Manages session data for the gifting flow
 */
class WC_Gift_Session {
    
    /**
     * Save step 2 shipping data to session
     */
    public function save_step2_data() {
        $gift_data = WC()->session->get('gift_flow_data', array());
        
        // FIXED: Ensure we're properly collecting all shipping fields
        $shipping_fields = array(
            'first_name',
            'last_name',
            'company',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode',
            'country'
        );
        
        // Initialize shipping data array if not exists
        if (!isset($gift_data['shipping'])) {
            $gift_data['shipping'] = array();
        }
        
        // Save each field from the form submission
        foreach ($shipping_fields as $field) {
            if (isset($_POST['shipping_' . $field])) {
                $gift_data['shipping'][$field] = sanitize_text_field($_POST['shipping_' . $field]);
            }
        }
        
        // Save recipient email separately
        if (isset($_POST['recipient_email'])) {
            $gift_data['recipient_email'] = sanitize_email($_POST['recipient_email']);
        }
        
        // Update session
        WC()->session->set('gift_flow_data', $gift_data);
        
        return true;
    }
    
    /**
     * Get session data
     */
    public function get_gift_data() {
        return WC()->session->get('gift_flow_data', array());
    }
    
    /**
     * Clear session data
     */
    public function clear_gift_data() {
        WC()->session->__unset('gift_flow_data');
    }
}