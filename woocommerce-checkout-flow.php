<?php
/*
Plugin Name: WooCommerce Gifting Flow
Description: A multi-step modal checkout flow for gifting, with modern full-screen UI.
Version: 4.2
Author: justasskh
Author URI: https://github.com/justasskh
Text Domain: wcflow
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 8.9
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Last Updated: 2025-06-18 08:53:05 UTC
Developer: justasskh
*/

if (!defined('ABSPATH')) exit;

define('WCFLOW_VERSION', '4.2');
define('WCFLOW_PATH', plugin_dir_path(__FILE__));
define('WCFLOW_URL', plugin_dir_url(__FILE__));
define('WCFLOW_PLUGIN_FILE', __FILE__);

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>WooCommerce Gifting Flow:</strong> This plugin requires WooCommerce to be installed and active.</p></div>';
    });
    return;
}

require_once WCFLOW_PATH . 'includes/settings.php';
require_once WCFLOW_PATH . 'includes/cpt.php';
require_once WCFLOW_PATH . 'includes/ajax.php';

// Add customer data handler
require_once WCFLOW_PATH . 'customer-data-handler.php';

// Add payment fix
require_once WCFLOW_PATH . 'wcflow-payment-fix.php';

// Set default settings on activation
register_activation_hook(__FILE__, 'wcflow_set_default_settings');
function wcflow_set_default_settings() {
    if (get_option('wcflow_processing_time') === false) {
        update_option('wcflow_processing_time', 2);
    }
    if (get_option('wcflow_allowed_delivery_days') === false) {
        update_option('wcflow_allowed_delivery_days', [1, 2, 3, 4, 5]);
    }
    
    // Log activation
    error_log('WooCommerce Gifting Flow activated on 2025-06-18 08:53:05 UTC by user: justasskh');
}

// Enqueue assets only on product pages
add_action('wp_enqueue_scripts', function() {
    if (!is_product() && !is_shop() && !is_product_category()) return;
    
    wp_enqueue_style('wcflow-style', WCFLOW_URL . 'assets/wcflow.css', [], WCFLOW_VERSION);
    wp_enqueue_script('wcflow-script', WCFLOW_URL . 'assets/wcflow.js', ['jquery'], WCFLOW_VERSION, true);

    // Add WooCommerce checkout params if needed
    if (is_product() || is_front_page() || is_home()) {
        wp_localize_script('wcflow-js', 'wc_checkout_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'update_order_review_nonce' => wp_create_nonce('update-order-review')
        ));
    }
    
    // Enhanced localization with user context
    wp_localize_script('wcflow-script', 'wcflow_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcflow_nonce'),
        'locale'   => get_locale(),
        'currency_symbol' => get_woocommerce_currency_symbol(),
        'user_logged_in' => is_user_logged_in(),
        'checkout_url' => wc_get_checkout_url(),
        'version' => WCFLOW_VERSION,
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'timestamp' => '2025-06-18 08:53:05 UTC',
    ]);
});

// Add gifting flow button after add to cart
add_action('woocommerce_after_add_to_cart_button', function() {
    global $product;
    if (!$product || !$product->get_id()) return;
    
    echo '<script>window.wcflow_product_id = ' . intval($product->get_id()) . ';</script>';
    echo '<button type="button" class="button alt wcflow-trigger-btn" style="margin-top: 15px; width: 100%; background: #007cba; border-color: #007cba;" data-product-id="' . esc_attr($product->get_id()) . '">Order with Gifting Flow</button>';
});

// Add admin notices for configuration
add_action('admin_notices', function() {
    if (!current_user_can('manage_woocommerce')) return;
    
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'wcflow') !== false) return;
    
    $processing_time = get_option('wcflow_processing_time');
    $allowed_days = get_option('wcflow_allowed_delivery_days');
    
    if (empty($processing_time) && empty($allowed_days)) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>WooCommerce Gifting Flow:</strong> Please configure your delivery settings to get started. <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=wcflow_settings'); ?>" class="button button-primary" style="margin-left: 10px;">Configure Now</a></p>
        </div>
        <?php
    }
});

// Add custom order meta display in admin
add_action('woocommerce_admin_order_data_after_shipping_address', function($order) {
    $delivery_date = $order->get_meta('_delivery_date');
    if ($delivery_date) {
        echo '<p><strong>Delivery Date:</strong> ' . date('l, F j, Y', strtotime($delivery_date)) . '</p>';
    }
});

// Add delivery date to order emails
add_action('woocommerce_email_order_meta', function($order, $sent_to_admin, $plain_text, $email) {
    $delivery_date = $order->get_meta('_delivery_date');
    if ($delivery_date) {
        if ($plain_text) {
            echo "\nDelivery Date: " . date('l, F j, Y', strtotime($delivery_date)) . "\n";
        } else {
            echo '<h2 style="color: #007cba; margin-top: 20px;">Delivery Information</h2>';
            echo '<p><strong>Requested Delivery Date:</strong> ' . date('l, F j, Y', strtotime($delivery_date)) . '</p>';
        }
    }
}, 20, 4);

// Add plugin action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=wcflow_settings') . '">Settings</a>';
    $docs_link = '<a href="#" target="_blank">Documentation</a>';
    array_unshift($links, $settings_link, $docs_link);
    return $links;
});

// Log plugin errors for debugging
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        if (is_product()) {
            echo "<!-- WooCommerce Gifting Flow v" . WCFLOW_VERSION . " loaded at 2025-06-18 08:53:05 UTC -->";
        }
    });
}

add_action('wp_enqueue_scripts', function() {
    if (is_product() || is_front_page() || is_home()) {
        wp_enqueue_script('woocommerce');
        wp_enqueue_script('wc-checkout');
        wp_enqueue_script('wc-credit-card-form');
        // Force all available gateway scripts to load
        foreach (WC()->payment_gateways()->get_available_payment_gateways() as $gateway) {
            if (method_exists($gateway, 'payment_scripts')) {
                $gateway->payment_scripts();
            }
        }
    }
}, 100);

