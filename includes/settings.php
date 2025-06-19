<?php
/**
 * WooCommerce Gifting Flow Settings
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.2
 * @since 2025-06-18
 * @updated 2025-06-18 08:53:05 UTC
 */

if (!defined('ABSPATH')) exit;

add_action('init', 'wcflow_init_settings', 20);

function wcflow_init_settings() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    add_filter('woocommerce_get_settings_tabs_array', 'wcflow_add_settings_tab', 50);
    add_action('woocommerce_settings_tabs_wcflow_settings', 'wcflow_add_settings');
    add_action('woocommerce_update_options_wcflow_settings', 'wcflow_save_settings');
    add_action('woocommerce_settings_save_wcflow_settings', 'wcflow_log_settings_update');
}

function wcflow_add_settings_tab($settings_tabs) {
    $settings_tabs['wcflow_settings'] = __('Gifting Flow', 'wcflow');
    return $settings_tabs;
}

function wcflow_add_settings() {
    woocommerce_admin_fields(wcflow_get_settings());
}

function wcflow_save_settings() {
    woocommerce_update_options(wcflow_get_settings());
}

function wcflow_log_settings_update() {
    error_log('WooCommerce Gifting Flow settings updated on 2025-06-18 08:53:05 UTC by user: justasskh');
}

function wcflow_get_settings() {
    $settings = [
        'section_title' => [
            'name' => __('Delivery Date Configuration', 'wcflow'),
            'type' => 'title',
            'desc' => __('Configure how customers can select delivery dates in the gifting flow. Last updated: 2025-06-18 08:53:05 UTC by justasskh', 'wcflow'),
            'id'   => 'wcflow_delivery_date_settings'
        ],
        'processing_time' => [
            'name'        => __('Default Processing Time (Days)', 'wcflow'),
            'type'        => 'number',
            'desc'        => __('Minimum days before an order can be delivered. Example: Setting 2 means customers can only select dates 2+ days from today.', 'wcflow'),
            'id'          => 'wcflow_processing_time',
            'default'     => '2',
            'desc_tip'    => true,
            'custom_attributes' => [
                'min' => '0',
                'max' => '30',
                'step' => '1'
            ]
        ],
        'allowed_days' => [
            'name'        => __('Allowed Delivery Days', 'wcflow'),
            'type'        => 'multiselect',
            'class'       => 'wc-enhanced-select',
            'desc'        => __('Select which days of the week delivery is available. Hold Ctrl/Cmd to select multiple days.', 'wcflow'),
            'id'          => 'wcflow_allowed_delivery_days',
            'options'     => [
                '1' => __('Monday', 'wcflow'),
                '2' => __('Tuesday', 'wcflow'),
                '3' => __('Wednesday', 'wcflow'),
                '4' => __('Thursday', 'wcflow'),
                '5' => __('Friday', 'wcflow'),
                '6' => __('Saturday', 'wcflow'),
                '0' => __('Sunday', 'wcflow'),
            ],
            'default'     => ['1', '2', '3', '4', '5'],
            'desc_tip'    => true,
        ],
        'shipping_method_processing' => [
            'name'        => __('Custom Processing Time per Shipping Method', 'wcflow'),
            'type'        => 'textarea',
            'desc'        => __('<strong>Format:</strong> method_id:extra_days (one per line)<br><br><strong>Common Examples:</strong><br>• <code>flat_rate:1</code> - Flat rate shipping needs 1 extra day<br>• <code>free_shipping:3</code> - Free shipping needs 3 extra days<br>• <code>local_pickup:0</code> - Local pickup available same day<br><br><strong>How to find method IDs:</strong> Go to WooCommerce → Settings → Shipping and check your shipping zones.', 'wcflow'),
            'id'          => 'wcflow_shipping_method_processing',
            'css'         => 'width:100%; height: 150px; font-family: monospace;',
            'placeholder' => "flat_rate:1\nfree_shipping:3\nlocal_pickup:0",
            'desc_tip'    => false,
        ],
        'advanced_section' => [
            'name' => __('Advanced Settings', 'wcflow'),
            'type' => 'title',
            'desc' => __('Advanced configuration options for developers and power users.', 'wcflow'),
            'id'   => 'wcflow_advanced_settings'
        ],
        'enable_debug' => [
            'name'    => __('Enable Debug Mode', 'wcflow'),
            'type'    => 'checkbox',
            'desc'    => __('Enable debug logging for troubleshooting. Logs will be written to wp-content/debug.log', 'wcflow'),
            'id'      => 'wcflow_enable_debug',
            'default' => 'no',
            'desc_tip' => true,
        ],
        'section_end' => [
            'type' => 'sectionend',
            'id'   => 'wcflow_delivery_date_settings_end'
        ]
    ];
    return apply_filters('wc_wcflow_settings', $settings);
}

// Add settings validation
add_action('woocommerce_update_options_wcflow_settings', function() {
    $processing_time = intval($_POST['wcflow_processing_time'] ?? 2);
    if ($processing_time < 0 || $processing_time > 30) {
        WC_Admin_Settings::add_error(__('Processing time must be between 0 and 30 days.', 'wcflow'));
        update_option('wcflow_processing_time', 2);
    }
    
    $shipping_methods = sanitize_textarea_field($_POST['wcflow_shipping_method_processing'] ?? '');
    if ($shipping_methods) {
        $lines = explode("\n", $shipping_methods);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && !preg_match('/^[a-zA-Z_]+[a-zA-Z0-9_]*:[0-9]+$/', $line)) {
                WC_Admin_Settings::add_error(sprintf(__('Invalid shipping method format: %s. Use format: method_id:days', 'wcflow'), $line));
            }
        }
    }
}, 5);

// Add help tab
add_action('current_screen', function() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'woocommerce_page_wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'wcflow_settings') {
        $screen->add_help_tab([
            'id'      => 'wcflow_help',
            'title'   => __('Gifting Flow Help', 'wcflow'),
            'content' => '
                <h3>WooCommerce Gifting Flow Setup Guide</h3>
                <p><strong>Version:</strong> 4.2 | <strong>Developer:</strong> justasskh | <strong>Updated:</strong> 2025-06-18 08:53:05 UTC</p>
                
                <h4>Quick Setup Checklist:</h4>
                <ol>
                    <li>Set your processing time (recommended: 2 days)</li>
                    <li>Choose delivery days (Monday-Friday is typical)</li>
                    <li>Configure shipping method processing times</li>
                    <li>Create some Add-ons and Greeting Cards</li>
                    <li>Test the flow on a product page</li>
                </ol>
                
                <h4>Shipping Method Examples:</h4>
                <pre>
flat_rate:1        # Standard delivery needs 1 extra day
free_shipping:3    # Free shipping needs 3 extra days  
local_pickup:0     # Pickup available same day
express:0          # Express delivery same day
                </pre>
                
                <h4>Need Help?</h4>
                <p>Check the plugin folder for documentation or contact the developer.</p>
            '
        ]);
    }
});

// Export settings for backup
add_action('wp_ajax_wcflow_export_settings', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die('Unauthorized');
    }
    
    $settings = [
        'processing_time' => get_option('wcflow_processing_time'),
        'allowed_delivery_days' => get_option('wcflow_allowed_delivery_days'),
        'shipping_method_processing' => get_option('wcflow_shipping_method_processing'),
        'enable_debug' => get_option('wcflow_enable_debug'),
        'exported_at' => '2025-06-18 08:53:05 UTC',
        'exported_by' => 'justasskh',
    ];
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="wcflow-settings-' . date('Y-m-d') . '.json"');
    echo json_encode($settings, JSON_PRETTY_PRINT);
    exit;
});