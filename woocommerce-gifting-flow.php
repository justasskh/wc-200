<?php
/*
Plugin Name: WooCommerce Gifting Flow
Description: A multi-step modal checkout flow for gifting, with modern full-screen UI.
Version: 4.3
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

Last Updated: 2025-01-27
Developer: justasskh
*/

if (!defined('ABSPATH')) exit;

define('WCFLOW_VERSION', '4.3');
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

// Include required files - only include files that exist
$required_files = [
    'includes/settings.php',
    'includes/cpt.php', 
    'includes/ajax.php'
];

foreach ($required_files as $file) {
    $file_path = WCFLOW_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Set default settings on activation
register_activation_hook(__FILE__, 'wcflow_set_default_settings');
function wcflow_set_default_settings() {
    if (get_option('wcflow_processing_time') === false) {
        update_option('wcflow_processing_time', 2);
    }
    if (get_option('wcflow_allowed_delivery_days') === false) {
        update_option('wcflow_allowed_delivery_days', [1, 2, 3, 4, 5]);
    }
    
    error_log('WooCommerce Gifting Flow activated on ' . date('Y-m-d H:i:s') . ' UTC');
}

// Enqueue assets
add_action('wp_enqueue_scripts', function() {
    if (!is_product() && !is_shop() && !is_product_category()) return;
    
    wp_enqueue_style('wcflow-style', WCFLOW_URL . 'assets/wcflow.css', [], WCFLOW_VERSION);
    wp_enqueue_script('wcflow-script', WCFLOW_URL . 'assets/wcflow.js', ['jquery'], WCFLOW_VERSION, true);
    
    // Localize script
    wp_localize_script('wcflow-script', 'wcflow_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wcflow_nonce'),
        'locale'   => get_locale(),
        'currency_symbol' => get_woocommerce_currency_symbol(),
        'user_logged_in' => is_user_logged_in(),
        'checkout_url' => wc_get_checkout_url(),
        'version' => WCFLOW_VERSION,
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
    ]);
});

// Add gifting flow button after add to cart
add_action('woocommerce_after_add_to_cart_button', function() {
    global $product;
    if (!$product || !$product->get_id()) return;
    
    echo '<script>window.wcflow_product_id = ' . intval($product->get_id()) . ';</script>';
    echo '<button type="button" class="button alt wcflow-start-btn" style="margin-top: 15px; width: 100%; background: #007cba; border-color: #007cba;" data-product-id="' . esc_attr($product->get_id()) . '">Order with Gifting Flow</button>';
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
    array_unshift($links, $settings_link);
    return $links;
});