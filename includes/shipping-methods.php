<?php
/**
 * WooCommerce Gifting Flow - Shipping Methods Handler
 * 
 * Handles retrieval of real WooCommerce shipping methods
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get real shipping methods from WooCommerce
 * This is the main function that should be called to get shipping methods
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_real_shipping_methods($country = 'US') {
    wcflow_log('Getting real shipping methods for country: ' . $country);
    
    // Try different approaches to get shipping methods
    $methods = array();
    
    // First try: Direct WooCommerce shipping zone access
    $methods = wcflow_get_shipping_methods_from_zones($country);
    if (!empty($methods)) {
        wcflow_log('Successfully retrieved ' . count($methods) . ' methods from shipping zones');
        return $methods;
    }
    
    // Second try: WooCommerce cart and packages
    $methods = wcflow_get_shipping_methods_from_cart($country);
    if (!empty($methods)) {
        wcflow_log('Successfully retrieved ' . count($methods) . ' methods from cart packages');
        return $methods;
    }
    
    // Third try: WooCommerce settings
    $methods = wcflow_get_shipping_methods_from_settings($country);
    if (!empty($methods)) {
        wcflow_log('Successfully retrieved ' . count($methods) . ' methods from settings');
        return $methods;
    }
    
    // Last resort: Get methods from database
    $methods = wcflow_get_shipping_methods_from_database($country);
    if (!empty($methods)) {
        wcflow_log('Successfully retrieved ' . count($methods) . ' methods from database');
        return $methods;
    }
    
    // If all else fails, return fallback methods
    return wcflow_get_fallback_shipping_methods($country);
}

/**
 * Get shipping methods directly from WooCommerce shipping zones
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_shipping_methods_from_zones($country) {
    wcflow_log('Getting shipping methods from zones for country: ' . $country);
    
    // Check if WooCommerce is loaded
    if (!function_exists('WC') || !class_exists('WC_Shipping_Zones')) {
        wcflow_log('WooCommerce not loaded, cannot get shipping methods from zones');
        return array();
    }
    
    $shipping_methods = array();
    
    // Get shipping zones
    $shipping_zones = WC_Shipping_Zones::get_zones();
    wcflow_log('Found ' . count($shipping_zones) . ' shipping zones');
    
    // Find zone that includes the selected country
    $country_zone = null;
    foreach ($shipping_zones as $zone_data) {
        $zone = new WC_Shipping_Zone($zone_data['zone_id']);
        $locations = $zone->get_zone_locations();
        
        foreach ($locations as $location) {
            if ($location->type === 'country' && $location->code === $country) {
                $country_zone = $zone;
                wcflow_log('Found ' . $country . ' in zone: ' . $zone->get_zone_name());
                break 2;
            }
        }
    }
    
    // If country zone found, get its shipping methods
    if ($country_zone) {
        $zone_shipping_methods = $country_zone->get_shipping_methods(true);
        wcflow_log('Found ' . count($zone_shipping_methods) . ' shipping methods in zone');
        
        foreach ($zone_shipping_methods as $method) {
            if ($method->is_enabled()) {
                $cost = 0;
                
                // Get cost based on method type
                if ($method->id === 'flat_rate') {
                    $cost = isset($method->instance_settings['cost']) ? floatval($method->instance_settings['cost']) : 0;
                } elseif ($method->id === 'free_shipping') {
                    $cost = 0;
                }
                
                $shipping_methods[] = array(
                    'id' => $method->id . ':' . $method->instance_id,
                    'label' => $method->get_title(),
                    'name' => $method->get_title(),
                    'cost' => number_format($cost, 2),
                    'cost_with_tax' => number_format($cost, 2),
                    'price' => $cost,
                    'price_formatted' => wc_price($cost),
                    'method_id' => $method->id,
                    'instance_id' => $method->instance_id,
                    'description' => 'Shipping via ' . $method->get_title()
                );
                
                wcflow_log('Added zone shipping method: ' . $method->get_title() . ' - ' . $cost);
            }
        }
    } else {
        wcflow_log('No zone found for country ' . $country . ', checking rest of world zone');
        
        // Check rest of world zone
        $rest_of_world = new WC_Shipping_Zone(0);
        $methods = $rest_of_world->get_shipping_methods(true);
        wcflow_log('Found ' . count($methods) . ' shipping methods in rest of world zone');
        
        foreach ($methods as $method) {
            if ($method->is_enabled()) {
                $cost = 0;
                
                // Get cost based on method type
                if ($method->id === 'flat_rate') {
                    $cost = isset($method->instance_settings['cost']) ? floatval($method->instance_settings['cost']) : 0;
                } elseif ($method->id === 'free_shipping') {
                    $cost = 0;
                }
                
                $shipping_methods[] = array(
                    'id' => $method->id . ':' . $method->instance_id,
                    'label' => $method->get_title(),
                    'name' => $method->get_title(),
                    'cost' => number_format($cost, 2),
                    'cost_with_tax' => number_format($cost, 2),
                    'price' => $cost,
                    'price_formatted' => wc_price($cost),
                    'method_id' => $method->id,
                    'instance_id' => $method->instance_id,
                    'description' => 'Shipping via ' . $method->get_title()
                );
                
                wcflow_log('Added rest of world shipping method: ' . $method->get_title() . ' - ' . $cost);
            }
        }
    }
    
    return $shipping_methods;
}

/**
 * Get shipping methods from WooCommerce cart and packages
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_shipping_methods_from_cart($country) {
    wcflow_log('Getting shipping methods from cart for country: ' . $country);
    
    // Check if WooCommerce is loaded
    if (!function_exists('WC') || !WC()->cart || !WC()->customer || !WC()->shipping) {
        wcflow_log('WooCommerce not fully loaded, cannot get shipping methods from cart');
        return array();
    }
    
    // Check if cart is empty
    if (WC()->cart->is_empty()) {
        wcflow_log('Cart is empty, adding a dummy product for shipping calculation');
        
        // Get a valid product ID
        $dummy_product_id = 0;
        $products = wc_get_products(array('limit' => 1, 'status' => 'publish'));
        
        if (!empty($products)) {
            $dummy_product_id = $products[0]->get_id();
            wcflow_log('Found dummy product ID: ' . $dummy_product_id);
            
            // Add product to cart
            $cart_item_key = WC()->cart->add_to_cart($dummy_product_id, 1);
            
            if (!$cart_item_key) {
                wcflow_log('Failed to add dummy product to cart');
                return array();
            }
            
            wcflow_log('Added dummy product to cart for shipping calculation');
        } else {
            wcflow_log('No products found for shipping calculation');
            return array();
        }
    }
    
    // Clear any cached shipping rates
    WC()->session->set('shipping_for_package_0', null);
    
    // Set shipping destination based on country
    WC()->customer->set_shipping_country($country);
    WC()->customer->set_shipping_postcode('10001');
    WC()->customer->set_shipping_city('New York');
    WC()->customer->set_shipping_state('');
    
    // Force recalculation
    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();
    
    $packages = WC()->cart->get_shipping_packages();
    $shipping_methods = array();
    
    wcflow_log('Cart packages count: ' . count($packages));
    
    if (!empty($packages)) {
        $shipping_for_package = WC()->shipping->calculate_shipping_for_package($packages[0]);
        wcflow_log('Shipping rates found: ' . count($shipping_for_package['rates']));
        
        if (!empty($shipping_for_package['rates'])) {
            foreach ($shipping_for_package['rates'] as $rate) {
                $cost_with_tax = $rate->get_cost() + $rate->get_shipping_tax();
                $shipping_methods[] = array(
                    'id' => $rate->get_id(),
                    'label' => $rate->get_label(),
                    'name' => $rate->get_label(),
                    'cost' => number_format($rate->get_cost(), 2),
                    'cost_with_tax' => number_format($cost_with_tax, 2),
                    'price' => $cost_with_tax,
                    'price_formatted' => wc_price($cost_with_tax),
                    'method_id' => $rate->get_method_id(),
                    'instance_id' => $rate->get_instance_id(),
                    'description' => $rate->get_label() . ' - Delivery in 2-5 business days'
                );
                
                wcflow_log('Added shipping method from cart: ' . $rate->get_label() . ' - ' . $cost_with_tax);
            }
        }
    }
    
    return $shipping_methods;
}

/**
 * Get shipping methods from WooCommerce settings
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_shipping_methods_from_settings($country) {
    wcflow_log('Getting shipping methods from settings for country: ' . $country);
    
    // Check if WooCommerce is loaded
    if (!function_exists('WC')) {
        wcflow_log('WooCommerce not loaded, cannot get shipping methods from settings');
        return array();
    }
    
    $shipping_methods = array();
    
    // Get all shipping methods from WooCommerce
    $all_shipping_methods = WC()->shipping->get_shipping_methods();
    wcflow_log('Found ' . count($all_shipping_methods) . ' global shipping methods');
    
    // Check if flat rate is enabled
    if (isset($all_shipping_methods['flat_rate']) && $all_shipping_methods['flat_rate']->is_enabled()) {
        $flat_rate_settings = get_option('woocommerce_flat_rate_settings', array());
        $cost = isset($flat_rate_settings['cost']) ? floatval($flat_rate_settings['cost']) : 4.99;
        
        $shipping_methods[] = array(
            'id' => 'flat_rate:1',
            'label' => $all_shipping_methods['flat_rate']->get_method_title(),
            'name' => $all_shipping_methods['flat_rate']->get_method_title(),
            'cost' => number_format($cost, 2),
            'cost_with_tax' => number_format($cost, 2),
            'price' => $cost,
            'price_formatted' => wc_price($cost),
            'method_id' => 'flat_rate',
            'instance_id' => '1',
            'description' => 'Flat rate shipping - Delivery in 2-5 business days'
        );
        
        wcflow_log('Added flat rate from settings: ' . $cost);
    }
    
    // Check if free shipping is enabled
    if (isset($all_shipping_methods['free_shipping']) && $all_shipping_methods['free_shipping']->is_enabled()) {
        $shipping_methods[] = array(
            'id' => 'free_shipping:1',
            'label' => $all_shipping_methods['free_shipping']->get_method_title(),
            'name' => $all_shipping_methods['free_shipping']->get_method_title(),
            'cost' => '0.00',
            'cost_with_tax' => '0.00',
            'price' => 0,
            'price_formatted' => wc_price(0),
            'method_id' => 'free_shipping',
            'instance_id' => '1',
            'description' => 'Free shipping - Delivery in 5-7 business days'
        );
        
        wcflow_log('Added free shipping from settings');
    }
    
    return $shipping_methods;
}

/**
 * Get shipping methods from database
 * This is a last resort method that directly queries the database
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_shipping_methods_from_database($country) {
    wcflow_log('Getting shipping methods from database for country: ' . $country);
    
    global $wpdb;
    $shipping_methods = array();
    
    // Get shipping methods from database
    $shipping_methods_table = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
    $shipping_zones_table = $wpdb->prefix . 'woocommerce_shipping_zones';
    $shipping_zone_locations_table = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
    
    // Check if tables exist
    $shipping_methods_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$shipping_methods_table}'") == $shipping_methods_table;
    $shipping_zones_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$shipping_zones_table}'") == $shipping_zones_table;
    $shipping_zone_locations_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$shipping_zone_locations_table}'") == $shipping_zone_locations_table;
    
    if (!$shipping_methods_table_exists || !$shipping_zones_table_exists || !$shipping_zone_locations_table_exists) {
        wcflow_log('WooCommerce shipping tables do not exist');
        return array();
    }
    
    // Find zones that include the country
    $zones_query = $wpdb->prepare("
        SELECT DISTINCT z.zone_id, z.zone_name
        FROM {$shipping_zones_table} z
        JOIN {$shipping_zone_locations_table} l ON z.zone_id = l.zone_id
        WHERE l.location_code = %s AND l.location_type = 'country'
    ", $country);
    
    $zones = $wpdb->get_results($zones_query);
    wcflow_log('Found ' . count($zones) . ' zones for country ' . $country);
    
    if (!empty($zones)) {
        foreach ($zones as $zone) {
            // Get shipping methods for this zone
            $methods_query = $wpdb->prepare("
                SELECT method_id, method_order, instance_id, is_enabled
                FROM {$shipping_methods_table}
                WHERE zone_id = %d AND is_enabled = 1
            ", $zone->zone_id);
            
            $methods = $wpdb->get_results($methods_query);
            wcflow_log('Found ' . count($methods) . ' methods for zone ' . $zone->zone_name);
            
            foreach ($methods as $method) {
                // Get method settings
                $method_settings = get_option('woocommerce_' . $method->method_id . '_' . $method->instance_id . '_settings', array());
                
                // Get method title
                $method_title = isset($method_settings['title']) ? $method_settings['title'] : ucfirst($method->method_id);
                
                // Get method cost
                $cost = 0;
                if ($method->method_id === 'flat_rate') {
                    $cost = isset($method_settings['cost']) ? floatval($method_settings['cost']) : 0;
                }
                
                $shipping_methods[] = array(
                    'id' => $method->method_id . ':' . $method->instance_id,
                    'label' => $method_title,
                    'name' => $method_title,
                    'cost' => number_format($cost, 2),
                    'cost_with_tax' => number_format($cost, 2),
                    'price' => $cost,
                    'price_formatted' => wc_price($cost),
                    'method_id' => $method->method_id,
                    'instance_id' => $method->instance_id,
                    'description' => 'Shipping method from zone: ' . $zone->zone_name
                );
                
                wcflow_log('Added shipping method from database: ' . $method_title . ' - ' . $cost);
            }
        }
    } else {
        // Check rest of world zone (zone_id = 0)
        $methods_query = "
            SELECT method_id, method_order, instance_id, is_enabled
            FROM {$shipping_methods_table}
            WHERE zone_id = 0 AND is_enabled = 1
        ";
        
        $methods = $wpdb->get_results($methods_query);
        wcflow_log('Found ' . count($methods) . ' methods for rest of world zone');
        
        foreach ($methods as $method) {
            // Get method settings
            $method_settings = get_option('woocommerce_' . $method->method_id . '_' . $method->instance_id . '_settings', array());
            
            // Get method title
            $method_title = isset($method_settings['title']) ? $method_settings['title'] : ucfirst($method->method_id);
            
            // Get method cost
            $cost = 0;
            if ($method->method_id === 'flat_rate') {
                $cost = isset($method_settings['cost']) ? floatval($method_settings['cost']) : 0;
            }
            
            $shipping_methods[] = array(
                'id' => $method->method_id . ':' . $method->instance_id,
                'label' => $method_title,
                'name' => $method_title,
                'cost' => number_format($cost, 2),
                'cost_with_tax' => number_format($cost, 2),
                'price' => $cost,
                'price_formatted' => wc_price($cost),
                'method_id' => $method->method_id,
                'instance_id' => $method->instance_id,
                'description' => 'Shipping method from rest of world zone'
            );
            
            wcflow_log('Added shipping method from database: ' . $method_title . ' - ' . $cost);
        }
    }
    
    return $shipping_methods;
}

/**
 * Get fallback shipping methods when all other methods fail
 * 
 * @param string $country Country code
 * @return array Array of shipping methods
 */
function wcflow_get_fallback_shipping_methods($country) {
    wcflow_log('Using fallback shipping methods for country: ' . $country);
    
    $currency_symbol = get_woocommerce_currency_symbol();
    
    return array(
        array(
            'id' => 'flat_rate:1',
            'label' => 'Standard Shipping',
            'name' => 'Standard Shipping',
            'cost' => '4.99',
            'cost_with_tax' => '4.99',
            'price' => 4.99,
            'price_formatted' => $currency_symbol . '4.99',
            'method_id' => 'flat_rate',
            'instance_id' => '1',
            'description' => 'Standard shipping - Delivery in 3-5 business days'
        ),
        array(
            'id' => 'flat_rate:2',
            'label' => 'Express Shipping',
            'name' => 'Express Shipping',
            'cost' => '9.99',
            'cost_with_tax' => '9.99',
            'price' => 9.99,
            'price_formatted' => $currency_symbol . '9.99',
            'method_id' => 'flat_rate',
            'instance_id' => '2',
            'description' => 'Express shipping - Delivery in 1-2 business days'
        ),
        array(
            'id' => 'free_shipping:1',
            'label' => 'Free Shipping',
            'name' => 'Free Shipping',
            'cost' => '0.00',
            'cost_with_tax' => '0.00',
            'price' => 0,
            'price_formatted' => 'FREE',
            'method_id' => 'free_shipping',
            'instance_id' => '1',
            'description' => 'Free shipping - Delivery in 5-7 business days'
        )
    );
}