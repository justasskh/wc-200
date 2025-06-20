<?php
/**
 * Plugin Refresh Script
 * This script forces a refresh of the WooCommerce Gifting Flow plugin's assets
 * by clearing caches and updating version numbers.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Clear WordPress cache
wp_cache_flush();

// Clear transients
delete_transient('wcflow_cards_cache');
delete_transient('wcflow_addons_cache');

// Update plugin version to force asset refresh
$current_version = get_option('wcflow_version', '5.1');
update_option('wcflow_version', $current_version . '.' . time());

// Clear browser cache by updating asset version numbers
function wcflow_force_asset_refresh() {
    wp_enqueue_style('wcflow-style', WCFLOW_URL . 'assets/wcflow-styles.css', [], WCFLOW_VERSION . '.' . time());
    wp_enqueue_script('wcflow-script', WCFLOW_URL . 'assets/wcflow.js', ['jquery'], WCFLOW_VERSION . '.' . time(), true);
    wp_enqueue_style('greeting-cards-slider', WCFLOW_URL . 'assets/greeting-cards-slider.css', [], WCFLOW_VERSION . '.' . time());
    wp_enqueue_script('greeting-cards-slider', WCFLOW_URL . 'assets/greeting-cards-slider.js', ['jquery'], WCFLOW_VERSION . '.' . time(), true);
}
add_action('wp_enqueue_scripts', 'wcflow_force_asset_refresh', 999);

// Add admin notice
function wcflow_refresh_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>WooCommerce Gifting Flow:</strong> Plugin assets have been refreshed. Please reload your pages to see the changes.</p>
    </div>
    <?php
}
add_action('admin_notices', 'wcflow_refresh_notice');

// Log the refresh
if (function_exists('wcflow_log')) {
    wcflow_log('Plugin assets refreshed at ' . date('Y-m-d H:i:s'));
}

echo '<h1>WooCommerce Gifting Flow Plugin Refreshed</h1>';
echo '<p>All caches have been cleared and asset versions have been updated.</p>';
echo '<p>Please reload your pages to see the changes.</p>';
echo '<p><a href="' . admin_url() . '">Return to Dashboard</a></p>';
