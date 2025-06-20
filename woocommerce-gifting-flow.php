<?php
/*
Plugin Name: WooCommerce Gifting Flow
Description: A complete alternative checkout system for WooCommerce with gifting features, greeting cards, and streamlined user experience.
Version: 5.1
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

define('WCFLOW_VERSION', '5.1');
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

/**
 * Main plugin class
 */
class WooCommerce_Gifting_Flow {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('wcflow', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->include_files();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Check for updates
        $this->check_version();
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        $required_files = [
            'includes/settings.php',
            'includes/cpt.php', 
            'includes/ajax.php',
            'includes/database-connection.php',
            'includes/admin-tools.php',
            'includes/checkout-handler.php',
            'includes/order-handler.php',
            'includes/product-integration.php',
            'includes/security-validation.php',
            'includes/compatibility.php'
        ];

        foreach ($required_files as $file) {
            $file_path = WCFLOW_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                wcflow_log('Missing required file: ' . $file);
            }
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // WooCommerce integration hooks
        add_action('woocommerce_init', array($this, 'woocommerce_integration'));
        add_filter('woocommerce_checkout_fields', array($this, 'add_checkout_fields'));
        add_action('woocommerce_email_order_meta', array($this, 'add_email_order_meta'), 10, 3);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        if (!is_product() && !is_shop() && !is_product_category() && !is_cart() && !is_checkout()) {
            return;
        }
        
        wp_enqueue_style('wcflow-style', WCFLOW_URL . 'assets/wcflow.css', [], WCFLOW_VERSION);
        wp_enqueue_script('wcflow-script', WCFLOW_URL . 'assets/wcflow.js', ['jquery'], WCFLOW_VERSION, true);
        
        // Enhanced localization with comprehensive data
        $product_price = 0;
        if (is_product()) {
            global $product;
            if ($product && $product->get_price()) {
                $product_price = floatval($product->get_price());
            }
        }
        
        wp_localize_script('wcflow-script', 'wcflow_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wcflow_nonce'),
            'locale'   => get_locale(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'currency_code' => get_woocommerce_currency(),
            'user_logged_in' => is_user_logged_in(),
            'checkout_url' => wc_get_checkout_url(),
            'cart_url' => wc_get_cart_url(),
            'version' => WCFLOW_VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'base_product_price' => $product_price,
            'processing_time' => get_option('wcflow_processing_time', 2),
            'allowed_delivery_days' => get_option('wcflow_allowed_delivery_days', [1,2,3,4,5]),
            'strings' => [
                'loading' => __('Loading...', 'wcflow'),
                'error' => __('An error occurred. Please try again.', 'wcflow'),
                'success' => __('Success!', 'wcflow'),
                'required_field' => __('This field is required.', 'wcflow'),
                'invalid_email' => __('Please enter a valid email address.', 'wcflow'),
                'order_total' => __('Order Total', 'wcflow'),
                'including_delivery' => __('Including delivery', 'wcflow'),
                'free_delivery' => __('Free delivery included', 'wcflow')
            ]
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wcflow') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('wcflow-admin-style', WCFLOW_URL . 'assets/admin.css', [], WCFLOW_VERSION);
            wp_enqueue_script('wcflow-admin-script', WCFLOW_URL . 'assets/admin.js', ['jquery'], WCFLOW_VERSION, true);
            
            // Admin localization
            wp_localize_script('wcflow-admin-script', 'wcflow_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcflow_admin_nonce'),
                'strings' => [
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'wcflow'),
                    'order_updated' => __('Order updated successfully.', 'wcflow'),
                    'error_occurred' => __('An error occurred. Please try again.', 'wcflow')
                ]
            ]);
        }
    }
    
    /**
     * WooCommerce integration
     */
    public function woocommerce_integration() {
        // Ensure WooCommerce objects are available
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
        
        // Initialize customer and cart
        if (!WC()->customer) {
            WC()->customer = new WC_Customer();
        }
        
        if (!WC()->cart) {
            WC()->cart = new WC_Cart();
        }
        
        // Hook into WooCommerce processes
        add_action('woocommerce_before_calculate_totals', array($this, 'before_calculate_totals'));
        add_filter('woocommerce_cart_needs_shipping', array($this, 'cart_needs_shipping'));
    }
    
    /**
     * Add custom checkout fields
     */
    public function add_checkout_fields($fields) {
        // Add delivery date field to checkout
        $fields['order']['wcflow_delivery_date'] = array(
            'type'        => 'date',
            'label'       => __('Preferred Delivery Date', 'wcflow'),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true,
            'priority'    => 25,
            'custom_attributes' => array(
                'min' => date('Y-m-d', strtotime('+' . get_option('wcflow_processing_time', 2) . ' days'))
            )
        );
        
        // Add greeting message field
        $fields['order']['wcflow_greeting_message'] = array(
            'type'        => 'textarea',
            'label'       => __('Greeting Card Message', 'wcflow'),
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true,
            'priority'    => 26,
            'custom_attributes' => array(
                'maxlength' => '450',
                'rows' => '4'
            )
        );
        
        return $fields;
    }
    
    /**
     * Add order meta to emails
     */
    public function add_email_order_meta($order, $sent_to_admin, $plain_text) {
        $delivery_date = $order->get_meta('_delivery_date');
        $greeting_message = $order->get_meta('_greeting_card_message');
        
        if ($delivery_date || $greeting_message) {
            if ($plain_text) {
                echo "\n" . __('Gift Details:', 'wcflow') . "\n";
                if ($delivery_date) {
                    echo __('Delivery Date:', 'wcflow') . ' ' . date('F j, Y', strtotime($delivery_date)) . "\n";
                }
                if ($greeting_message) {
                    echo __('Message:', 'wcflow') . ' ' . $greeting_message . "\n";
                }
            } else {
                echo '<h2 style="color: #007cba;">' . __('🎁 Gift Details', 'wcflow') . '</h2>';
                if ($delivery_date) {
                    echo '<p><strong>' . __('Delivery Date:', 'wcflow') . '</strong> ' . date('F j, Y', strtotime($delivery_date)) . '</p>';
                }
                if ($greeting_message) {
                    echo '<p><strong>' . __('Message:', 'wcflow') . '</strong><br>' . nl2br(esc_html($greeting_message)) . '</p>';
                }
            }
        }
    }
    
    /**
     * Before calculate totals hook
     */
    public function before_calculate_totals($cart) {
        // Add any custom calculations here
        do_action('wcflow_before_calculate_totals', $cart);
    }
    
    /**
     * Cart needs shipping filter
     */
    public function cart_needs_shipping($needs_shipping) {
        // Ensure shipping is calculated for gifting orders
        return $needs_shipping;
    }
    
    /**
     * Add action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=wcflow_settings') . '">' . __('Settings', 'wcflow') . '</a>';
        $cards_link = '<a href="' . admin_url('edit.php?post_type=wcflow_card') . '">' . __('Cards', 'wcflow') . '</a>';
        $tools_link = '<a href="' . admin_url('edit.php?post_type=wcflow_card&page=wcflow-database-tools') . '" style="color: #00a32a;">' . __('Database Tools', 'wcflow') . '</a>';
        
        array_unshift($links, $settings_link, $cards_link, $tools_link);
        return $links;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (!current_user_can('manage_woocommerce')) return;
        
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'wcflow') !== false) return;
        
        // Check if settings are configured
        $processing_time = get_option('wcflow_processing_time');
        $allowed_days = get_option('wcflow_allowed_delivery_days');
        
        if (empty($processing_time) && empty($allowed_days)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('WooCommerce Gifting Flow:', 'wcflow'); ?></strong> 
                    <?php _e('Please configure your delivery settings to get started.', 'wcflow'); ?>
                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=wcflow_settings'); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Configure Now', 'wcflow'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
        
        // Check for WooCommerce version compatibility
        if (version_compare(WC_VERSION, '5.0', '<')) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('WooCommerce Gifting Flow:', 'wcflow'); ?></strong> 
                    <?php _e('This plugin requires WooCommerce 5.0 or higher. Please update WooCommerce.', 'wcflow'); ?>
                </p>
            </div>
            <?php
        }
        
        // Database connection status
        global $wpdb;
        $cards_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'");
        $categories_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'");
        
        if ($cards_count == 0 || $categories_count == 0) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>🎯 WooCommerce Gifting Flow:</strong> 
                    Ready to set up your greeting cards! 
                    <a href="<?php echo admin_url('edit.php?post_type=wcflow_card&page=wcflow-database-tools'); ?>" class="button button-primary">
                        Open Database Tools
                    </a>
                </p>
            </div>
            <?php
        } else {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✅ WooCommerce Gifting Flow:</strong> 
                    Database connected! <?php echo $categories_count; ?> categories, <?php echo $cards_count; ?> cards ready.
                    <a href="<?php echo admin_url('edit.php?post_type=wcflow_card&page=wcflow-database-tools'); ?>" class="button button-secondary">
                        Manage Database
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Check version and run updates if needed
     */
    private function check_version() {
        $current_version = get_option('wcflow_version', '0.0');
        
        if (version_compare($current_version, WCFLOW_VERSION, '<')) {
            $this->update_plugin($current_version);
            update_option('wcflow_version', WCFLOW_VERSION);
        }
    }
    
    /**
     * Update plugin data
     */
    private function update_plugin($from_version) {
        wcflow_log('Updating plugin from version ' . $from_version . ' to ' . WCFLOW_VERSION);
        
        // Run version-specific updates
        if (version_compare($from_version, '5.1', '<')) {
            // Update to 5.1 - ensure database connection is working
            if (!get_option('wcflow_processing_time')) {
                update_option('wcflow_processing_time', 2);
            }
            if (!get_option('wcflow_allowed_delivery_days')) {
                update_option('wcflow_allowed_delivery_days', [1,2,3,4,5]);
            }
            
            // Clear cache to force fresh data
            delete_transient('wcflow_cards_cache');
        }
        
        // Clear any caches
        wp_cache_flush();
        
        do_action('wcflow_updated', $from_version, WCFLOW_VERSION);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default settings
        if (!get_option('wcflow_processing_time')) {
            update_option('wcflow_processing_time', 2);
        }
        if (!get_option('wcflow_allowed_delivery_days')) {
            update_option('wcflow_allowed_delivery_days', [1,2,3,4,5]);
        }
        if (!get_option('wcflow_enable_debug')) {
            update_option('wcflow_enable_debug', 'no');
        }
        
        // Set version
        update_option('wcflow_version', WCFLOW_VERSION);
        
        // Force recreation of post types and default data
        delete_option('wcflow_default_data_created');
        delete_option('wcflow_flush_rewrite_rules');
        
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        wcflow_log('WooCommerce Gifting Flow activated - Version ' . WCFLOW_VERSION);
        
        do_action('wcflow_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('wcflow_daily_cleanup');
        
        // Clear cache
        delete_transient('wcflow_cards_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        wcflow_log('WooCommerce Gifting Flow deactivated');
        
        do_action('wcflow_deactivated');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create analytics table for tracking gifting flow usage
        $table_name = $wpdb->prefix . 'wcflow_analytics';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            step_completed varchar(20) NOT NULL,
            completion_time datetime DEFAULT CURRENT_TIMESTAMP,
            user_agent text,
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY step_completed (step_completed)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
new WooCommerce_Gifting_Flow();

/**
 * Helper function to check if gifting flow is enabled for a product
 */
function wcflow_is_enabled_for_product($product_id) {
    $enabled = get_post_meta($product_id, '_enable_wcflow_gifting', true);
    return $enabled === 'yes' || $enabled === ''; // Default to enabled
}

/**
 * Helper function to get gifting flow settings
 */
function wcflow_get_setting($key, $default = '') {
    return get_option('wcflow_' . $key, $default);
}

/**
 * Helper function to log messages - FIXED to avoid redeclaration
 */
if (!function_exists('wcflow_log')) {
    function wcflow_log($message) {
        if (get_option('wcflow_enable_debug') === 'yes' || (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log('[WooCommerce Gifting Flow] ' . $message);
        }
    }
}

/**
 * Template function to display gifting flow button
 */
function wcflow_display_button($product_id = null) {
    if (!$product_id) {
        global $product;
        $product_id = $product ? $product->get_id() : 0;
    }
    
    if ($product_id && wcflow_is_enabled_for_product($product_id)) {
        echo '<button type="button" class="wcflow-start-btn" data-product-id="' . esc_attr($product_id) . '">Send as Gift</button>';
    }
}

/**
 * Shortcode for displaying gifting flow button
 */
add_shortcode('wcflow_button', function($atts) {
    $atts = shortcode_atts([
        'product_id' => 0,
        'text' => 'Send as Gift',
        'class' => 'wcflow-start-btn'
    ], $atts);
    
    if (!$atts['product_id']) {
        global $product;
        $atts['product_id'] = $product ? $product->get_id() : 0;
    }
    
    if ($atts['product_id'] && wcflow_is_enabled_for_product($atts['product_id'])) {
        return '<button type="button" class="' . esc_attr($atts['class']) . '" data-product-id="' . esc_attr($atts['product_id']) . '">' . esc_html($atts['text']) . '</button>';
    }
    
    return '';
});