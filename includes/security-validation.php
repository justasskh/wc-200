<?php
/**
 * WooCommerce Gifting Flow Security and Validation
 * Comprehensive security measures and input validation
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Security {
    
    public function __construct() {
        add_action('init', array($this, 'init_security_measures'));
        add_filter('wcflow_validate_input', array($this, 'validate_input'), 10, 2);
        add_filter('wcflow_sanitize_data', array($this, 'sanitize_data'), 10, 2);
    }
    
    /**
     * Initialize security measures
     */
    public function init_security_measures() {
        // Rate limiting for AJAX requests
        add_action('wp_ajax_wcflow_start_flow', array($this, 'check_rate_limit'), 1);
        add_action('wp_ajax_nopriv_wcflow_start_flow', array($this, 'check_rate_limit'), 1);
        add_action('wp_ajax_wcflow_create_order', array($this, 'check_rate_limit'), 1);
        add_action('wp_ajax_nopriv_wcflow_create_order', array($this, 'check_rate_limit'), 1);
        
        // Input validation hooks
        add_action('wp_ajax_wcflow_create_order', array($this, 'validate_order_data'), 5);
        add_action('wp_ajax_nopriv_wcflow_create_order', array($this, 'validate_order_data'), 5);
    }
    
    /**
     * Rate limiting for AJAX requests
     */
    public function check_rate_limit() {
        $user_ip = $this->get_user_ip();
        $action = $_POST['action'] ?? '';
        
        $transient_key = 'wcflow_rate_limit_' . md5($user_ip . $action);
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            $requests = 1;
            set_transient($transient_key, $requests, MINUTE_IN_SECONDS);
        } else {
            $requests++;
            set_transient($transient_key, $requests, MINUTE_IN_SECONDS);
        }
        
        // Allow max 20 requests per minute per IP per action
        if ($requests > 20) {
            wcflow_log('Rate limit exceeded for IP: ' . $user_ip . ' Action: ' . $action);
            wp_send_json_error(['message' => 'Too many requests. Please try again later.']);
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Validate order data before processing
     */
    public function validate_order_data() {
        $state = $_POST['state'] ?? array();
        
        if (empty($state)) {
            wp_send_json_error(['message' => 'Invalid order data.']);
        }
        
        // Validate required fields
        $required_fields = [
            'shipping_first_name' => 'string',
            'shipping_last_name' => 'string',
            'shipping_address_1' => 'string',
            'shipping_city' => 'string',
            'shipping_postcode' => 'string',
            'shipping_country' => 'string'
        ];
        
        foreach ($required_fields as $field => $type) {
            if (empty($state[$field])) {
                wp_send_json_error(['message' => "Missing required field: {$field}"]);
            }
            
            if (!$this->validate_field_type($state[$field], $type)) {
                wp_send_json_error(['message' => "Invalid data type for field: {$field}"]);
            }
        }
        
        // Validate email
        $email = $state['customer_email'] ?? $state['billing_email'] ?? '';
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => 'Valid email address is required.']);
        }
        
        // Validate country code
        $countries = WC()->countries->get_countries();
        if (!isset($countries[$state['shipping_country']])) {
            wp_send_json_error(['message' => 'Invalid country code.']);
        }
        
        // Validate postcode format
        if (!WC_Validation::is_postcode($state['shipping_postcode'], $state['shipping_country'])) {
            wp_send_json_error(['message' => 'Invalid postcode format.']);
        }
        
        // Validate phone number (basic validation)
        if (!empty($state['shipping_phone']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,20}$/', $state['shipping_phone'])) {
            wp_send_json_error(['message' => 'Invalid phone number format.']);
        }
        
        // Validate addon IDs
        if (!empty($state['addons']) && is_array($state['addons'])) {
            foreach ($state['addons'] as $addon_id) {
                if (!is_numeric($addon_id) || !get_post($addon_id)) {
                    wp_send_json_error(['message' => 'Invalid addon ID.']);
                }
            }
        }
        
        // Validate card ID
        if (!empty($state['card_id'])) {
            if (!is_numeric($state['card_id']) || !get_post($state['card_id'])) {
                wp_send_json_error(['message' => 'Invalid card ID.']);
            }
        }
        
        // Validate message length
        if (!empty($state['card_message']) && strlen($state['card_message']) > 450) {
            wp_send_json_error(['message' => 'Message too long. Maximum 450 characters allowed.']);
        }
        
        // Validate delivery date
        if (!empty($state['delivery_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $state['delivery_date']);
            if (!$date || $date->format('Y-m-d') !== $state['delivery_date']) {
                wp_send_json_error(['message' => 'Invalid delivery date format.']);
            }
            
            // Check if date is in the future
            $today = new DateTime();
            if ($date <= $today) {
                wp_send_json_error(['message' => 'Delivery date must be in the future.']);
            }
        }
    }
    
    /**
     * Validate field type
     */
    private function validate_field_type($value, $type) {
        switch ($type) {
            case 'string':
                return is_string($value) && !empty(trim($value));
            case 'email':
                return is_email($value);
            case 'numeric':
                return is_numeric($value);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }
    
    /**
     * Sanitize input data
     */
    public function sanitize_data($data, $type = 'string') {
        switch ($type) {
            case 'string':
                return sanitize_text_field($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'int':
                return intval($data);
            case 'float':
                return floatval($data);
            case 'array':
                return is_array($data) ? array_map('sanitize_text_field', $data) : array();
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Validate input data
     */
    public function validate_input($data, $rules) {
        $errors = array();
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required field check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Field {$field} is required.";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }
            
            // Type validation
            if (isset($rule['type'])) {
                if (!$this->validate_field_type($value, $rule['type'])) {
                    $errors[$field] = "Field {$field} has invalid type.";
                    continue;
                }
            }
            
            // Length validation
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Field {$field} exceeds maximum length.";
            }
            
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Field {$field} is below minimum length.";
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "Field {$field} has invalid format.";
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}

new WCFlow_Security();

/**
 * Security helper functions
 */
function wcflow_validate_nonce($action = 'wcflow_nonce') {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', $action)) {
        wp_send_json_error(['message' => 'Security check failed.']);
    }
}

function wcflow_check_permissions($capability = 'read') {
    if (!current_user_can($capability)) {
        wp_send_json_error(['message' => 'Insufficient permissions.']);
    }
}

function wcflow_sanitize_order_data($data) {
    $sanitized = array();
    
    $string_fields = [
        'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 
        'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_phone',
        'billing_first_name', 'billing_last_name', 'billing_address_1',
        'billing_city', 'billing_postcode', 'billing_country', 'billing_phone',
        'customer_email', 'billing_email', 'shipping_method', 'payment_method'
    ];
    
    foreach ($string_fields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = sanitize_text_field($data[$field]);
        }
    }
    
    // Sanitize message
    if (isset($data['card_message'])) {
        $sanitized['card_message'] = sanitize_textarea_field($data['card_message']);
    }
    
    // Sanitize numeric fields
    $numeric_fields = ['card_id', 'base_price', 'shipping_cost', 'total'];
    foreach ($numeric_fields as $field) {
        if (isset($data[$field])) {
            $sanitized[$field] = is_numeric($data[$field]) ? $data[$field] : 0;
        }
    }
    
    // Sanitize arrays
    if (isset($data['addons']) && is_array($data['addons'])) {
        $sanitized['addons'] = array_map('intval', $data['addons']);
    }
    
    // Sanitize date
    if (isset($data['delivery_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['delivery_date']);
        $sanitized['delivery_date'] = $date ? $date->format('Y-m-d') : '';
    }
    
    return $sanitized;
}