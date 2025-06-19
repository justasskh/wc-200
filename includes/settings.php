<?php
/**
 * WooCommerce Gifting Flow Settings
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-01-27
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

function wcflow_get_settings() {
    $settings = [
        'section_title' => [
            'name' => __('Delivery Date Configuration', 'wcflow'),
            'type' => 'title',
            'desc' => __('Configure how customers can select delivery dates in the gifting flow.', 'wcflow'),
            'id'   => 'wcflow_delivery_date_settings'
        ],
        'processing_time' => [
            'name'        => __('Default Processing Time (Days)', 'wcflow'),
            'type'        => 'number',
            'desc'        => __('Minimum days before an order can be delivered.', 'wcflow'),
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
            'desc'        => __('Select which days of the week delivery is available.', 'wcflow'),
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
        'enable_debug' => [
            'name'    => __('Enable Debug Mode', 'wcflow'),
            'type'    => 'checkbox',
            'desc'    => __('Enable debug logging for troubleshooting.', 'wcflow'),
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