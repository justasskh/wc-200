<?php
/**
 * WooCommerce Gifting Flow Custom Post Types
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-01-27
 */

if (!defined('ABSPATH')) exit;

// Register custom post types
add_action('init', function() {
    // Register Add-ons post type
    register_post_type('wcflow_addon', [
        'label' => 'Gifting Add-ons',
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-admin-gifts',
        'supports' => ['title', 'thumbnail', 'editor', 'excerpt'],
        'capability_type' => 'post',
        'has_archive' => false,
        'rewrite' => false,
        'labels' => [
            'name' => 'Gifting Add-ons',
            'singular_name' => 'Add-on',
            'add_new' => 'Add New Add-on',
            'add_new_item' => 'Add New Add-on',
            'edit_item' => 'Edit Add-on',
            'new_item' => 'New Add-on',
            'view_item' => 'View Add-on',
            'search_items' => 'Search Add-ons',
            'not_found' => 'No add-ons found',
            'not_found_in_trash' => 'No add-ons found in trash',
            'all_items' => 'All Add-ons',
            'menu_name' => 'Gifting Add-ons',
        ],
        'show_in_rest' => true,
    ]);
    
    // Register Cards post type
    register_post_type('wcflow_card', [
        'label' => 'Greeting Cards',
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 21,
        'menu_icon' => 'dashicons-format-gallery',
        'supports' => ['title', 'thumbnail', 'excerpt'],
        'taxonomies' => ['category'],
        'capability_type' => 'post',
        'has_archive' => false,
        'rewrite' => false,
        'labels' => [
            'name' => 'Greeting Cards',
            'singular_name' => 'Card',
            'add_new' => 'Add New Card',
            'add_new_item' => 'Add New Card',
            'edit_item' => 'Edit Card',
            'new_item' => 'New Card',
            'view_item' => 'View Card',
            'search_items' => 'Search Cards',
            'not_found' => 'No cards found',
            'not_found_in_trash' => 'No cards found in trash',
            'all_items' => 'All Cards',
            'menu_name' => 'Greeting Cards',
        ],
        'show_in_rest' => true,
    ]);
});

// Add custom meta boxes
add_action('add_meta_boxes', function() {
    // Price meta box for both post types
    add_meta_box(
        'wcflow_price_meta_box',
        'Price Configuration',
        'wcflow_price_meta_box_callback',
        ['wcflow_addon', 'wcflow_card'],
        'side',
        'high'
    );
});

// Price meta box callback
function wcflow_price_meta_box_callback($post) {
    wp_nonce_field('wcflow_price_nonce', 'wcflow_price_nonce');
    $price = get_post_meta($post->ID, '_wcflow_price', true);
    $currency = get_woocommerce_currency_symbol();
    
    echo '<div style="margin: 15px 0;">';
    echo '<label for="wcflow_price" style="display: block; margin-bottom: 5px; font-weight: bold;">Price (' . $currency . '):</label>';
    echo '<input type="number" id="wcflow_price" name="wcflow_price" value="' . esc_attr($price) . '" step="0.01" min="0" style="width: 100%;" placeholder="0.00" />';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #666;">Set to 0 for free items.</p>';
    echo '</div>';
}

// Save meta box data
add_action('save_post', function($post_id) {
    // Security checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $post_type = get_post_type($post_id);
    if (!in_array($post_type, ['wcflow_addon', 'wcflow_card'])) return;
    
    // Save price
    if (isset($_POST['wcflow_price_nonce']) && wp_verify_nonce($_POST['wcflow_price_nonce'], 'wcflow_price_nonce')) {
        if (isset($_POST['wcflow_price'])) {
            $price = floatval($_POST['wcflow_price']);
            update_post_meta($post_id, '_wcflow_price', $price);
        }
    }
});

// Add custom columns to admin lists
add_filter('manage_wcflow_addon_posts_columns', 'wcflow_addon_columns');
add_filter('manage_wcflow_card_posts_columns', 'wcflow_card_columns');

function wcflow_addon_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['wcflow_image'] = 'Image';
    $new_columns['wcflow_price'] = 'Price';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

function wcflow_card_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['wcflow_image'] = 'Image';
    $new_columns['wcflow_price'] = 'Price';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

// Populate custom columns
add_action('manage_wcflow_addon_posts_custom_column', 'wcflow_addon_column_content', 10, 2);
add_action('manage_wcflow_card_posts_custom_column', 'wcflow_card_column_content', 10, 2);

function wcflow_addon_column_content($column, $post_id) {
    switch ($column) {
        case 'wcflow_image':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 50]);
            } else {
                echo '<span style="color: #ccc;">No image</span>';
            }
            break;
            
        case 'wcflow_price':
            $price = get_post_meta($post_id, '_wcflow_price', true);
            if ($price > 0) {
                echo get_woocommerce_currency_symbol() . number_format($price, 2);
            } else {
                echo '<span style="color: green;">FREE</span>';
            }
            break;
    }
}

function wcflow_card_column_content($column, $post_id) {
    switch ($column) {
        case 'wcflow_image':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, [50, 50]);
            } else {
                echo '<span style="color: #ccc;">No image</span>';
            }
            break;
            
        case 'wcflow_price':
            $price = get_post_meta($post_id, '_wcflow_price', true);
            if ($price > 0) {
                echo get_woocommerce_currency_symbol() . number_format($price, 2);
            } else {
                echo '<span style="color: green;">FREE</span>';
            }
            break;
    }
}