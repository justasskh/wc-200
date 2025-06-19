<?php
/**
 * WooCommerce Gifting Flow Custom Post Types with Category Management
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-01-27
 */

if (!defined('ABSPATH')) exit;

// Register custom post types and taxonomies
add_action('init', function() {
    // Register Card Categories taxonomy
    register_taxonomy('wcflow_card_category', 'wcflow_card', [
        'label' => 'Card Categories',
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => false,
        'capabilities' => [
            'manage_terms' => 'manage_woocommerce',
            'edit_terms' => 'manage_woocommerce',
            'delete_terms' => 'manage_woocommerce',
            'assign_terms' => 'edit_posts',
        ],
        'labels' => [
            'name' => 'Card Categories',
            'singular_name' => 'Card Category',
            'add_new_item' => 'Add New Category',
            'edit_item' => 'Edit Category',
            'update_item' => 'Update Category',
            'view_item' => 'View Category',
            'separate_items_with_commas' => 'Separate categories with commas',
            'add_or_remove_items' => 'Add or remove categories',
            'choose_from_most_used' => 'Choose from the most used',
            'popular_items' => 'Popular Categories',
            'search_items' => 'Search Categories',
            'not_found' => 'Not Found',
            'no_terms' => 'No categories',
            'items_list' => 'Categories list',
            'items_list_navigation' => 'Categories list navigation',
        ],
        'show_in_rest' => true,
    ]);
    
    // Register Add-ons post type
    register_post_type('wcflow_addon', [
        'label' => 'Gifting Add-ons',
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-admin-gifts',
        'supports' => ['title', 'thumbnail', 'editor', 'excerpt', 'page-attributes'],
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
        'supports' => ['title', 'thumbnail', 'excerpt', 'page-attributes'],
        'taxonomies' => ['wcflow_card_category'],
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
    
    // Category settings meta box
    add_meta_box(
        'wcflow_category_settings_meta_box',
        'Category Display Settings',
        'wcflow_category_settings_meta_box_callback',
        'wcflow_card_category',
        'normal',
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

// Category settings meta box callback
function wcflow_category_settings_meta_box_callback($term) {
    $category_order = get_term_meta($term->term_id, '_wcflow_category_order', true);
    $category_description = get_term_meta($term->term_id, '_wcflow_category_description', true);
    
    ?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="wcflow_category_order">Display Order</label>
            </th>
            <td>
                <input type="number" id="wcflow_category_order" name="wcflow_category_order" value="<?php echo esc_attr($category_order); ?>" min="0" step="1" />
                <p class="description">Lower numbers appear first. Leave empty for default ordering.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="wcflow_category_description">Category Description</label>
            </th>
            <td>
                <textarea id="wcflow_category_description" name="wcflow_category_description" rows="3" cols="50"><?php echo esc_textarea($category_description); ?></textarea>
                <p class="description">This description will appear below the category title in the slider.</p>
            </td>
        </tr>
    </table>
    <?php
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

// Save category meta data
add_action('edited_wcflow_card_category', 'wcflow_save_category_meta', 10, 2);
add_action('created_wcflow_card_category', 'wcflow_save_category_meta', 10, 2);

function wcflow_save_category_meta($term_id, $tt_id) {
    if (isset($_POST['wcflow_category_order'])) {
        update_term_meta($term_id, '_wcflow_category_order', intval($_POST['wcflow_category_order']));
    }
    
    if (isset($_POST['wcflow_category_description'])) {
        update_term_meta($term_id, '_wcflow_category_description', sanitize_textarea_field($_POST['wcflow_category_description']));
    }
}

// Add custom columns to admin lists
add_filter('manage_wcflow_addon_posts_columns', 'wcflow_addon_columns');
add_filter('manage_wcflow_card_posts_columns', 'wcflow_card_columns');
add_filter('manage_edit-wcflow_card_category_columns', 'wcflow_category_columns');

function wcflow_addon_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['wcflow_image'] = 'Image';
    $new_columns['wcflow_price'] = 'Price';
    $new_columns['menu_order'] = 'Order';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

function wcflow_card_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['wcflow_image'] = 'Image';
    $new_columns['wcflow_price'] = 'Price';
    $new_columns['taxonomy-wcflow_card_category'] = 'Category';
    $new_columns['menu_order'] = 'Order';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

function wcflow_category_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['name'] = $columns['name'];
    $new_columns['wcflow_category_order'] = 'Display Order';
    $new_columns['wcflow_category_description'] = 'Description';
    $new_columns['posts'] = $columns['posts'];
    return $new_columns;
}

// Populate custom columns
add_action('manage_wcflow_addon_posts_custom_column', 'wcflow_addon_column_content', 10, 2);
add_action('manage_wcflow_card_posts_custom_column', 'wcflow_card_column_content', 10, 2);
add_action('manage_wcflow_card_category_custom_column', 'wcflow_category_column_content', 10, 3);

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
            
        case 'menu_order':
            $order = get_post_field('menu_order', $post_id);
            echo '<input type="number" value="' . esc_attr($order) . '" min="0" style="width: 60px;" onchange="updateMenuOrder(' . $post_id . ', this.value)">';
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
            
        case 'menu_order':
            $order = get_post_field('menu_order', $post_id);
            echo '<input type="number" value="' . esc_attr($order) . '" min="0" style="width: 60px;" onchange="updateMenuOrder(' . $post_id . ', this.value)">';
            break;
    }
}

function wcflow_category_column_content($content, $column, $term_id) {
    switch ($column) {
        case 'wcflow_category_order':
            $order = get_term_meta($term_id, '_wcflow_category_order', true);
            return '<input type="number" value="' . esc_attr($order) . '" min="0" style="width: 60px;" onchange="updateCategoryOrder(' . $term_id . ', this.value)">';
            
        case 'wcflow_category_description':
            $description = get_term_meta($term_id, '_wcflow_category_description', true);
            return wp_trim_words($description, 10);
    }
    return $content;
}

// Add admin scripts for drag and drop functionality
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'wcflow') !== false || $hook === 'edit.php') {
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('wcflow-admin-sortable', WCFLOW_URL . 'assets/admin-sortable.js', ['jquery', 'jquery-ui-sortable'], WCFLOW_VERSION, true);
        wp_localize_script('wcflow-admin-sortable', 'wcflow_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcflow_admin_nonce')
        ]);
    }
});

// AJAX handlers for updating order
add_action('wp_ajax_wcflow_update_menu_order', function() {
    check_ajax_referer('wcflow_admin_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $menu_order = intval($_POST['menu_order']);
    
    wp_update_post([
        'ID' => $post_id,
        'menu_order' => $menu_order
    ]);
    
    wp_send_json_success();
});

add_action('wp_ajax_wcflow_update_category_order', function() {
    check_ajax_referer('wcflow_admin_nonce', 'nonce');
    
    $term_id = intval($_POST['term_id']);
    $order = intval($_POST['order']);
    
    update_term_meta($term_id, '_wcflow_category_order', $order);
    
    wp_send_json_success();
});

// Make posts sortable by menu_order
add_filter('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if (in_array($query->get('post_type'), ['wcflow_addon', 'wcflow_card'])) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
});