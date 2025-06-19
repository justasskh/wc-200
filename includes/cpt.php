<?php
/**
 * WooCommerce Gifting Flow Custom Post Types
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.2
 * @since 2025-06-18
 * @updated 2025-06-18 08:53:05 UTC
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
    
    // Product details meta box for add-ons
    add_meta_box(
        'wcflow_addon_details_meta_box',
        'Add-on Details',
        'wcflow_addon_details_meta_box_callback',
        'wcflow_addon',
        'normal',
        'high'
    );
    
    // Usage statistics meta box
    add_meta_box(
        'wcflow_stats_meta_box',
        'Usage Statistics',
        'wcflow_stats_meta_box_callback',
        ['wcflow_addon', 'wcflow_card'],
        'side',
        'low'
    );
});

// Price meta box callback
function wcflow_price_meta_box_callback($post) {
    wp_nonce_field('wcflow_price_nonce', 'wcflow_price_nonce');
    $price = get_post_meta($post->ID, 'price', true);
    $currency = get_woocommerce_currency_symbol();
    
    echo '<div style="margin: 15px 0;">';
    echo '<label for="wcflow_price" style="display: block; margin-bottom: 5px; font-weight: bold;">Price (' . $currency . '):</label>';
    echo '<input type="number" id="wcflow_price" name="wcflow_price" value="' . esc_attr($price) . '" step="0.01" min="0" style="width: 100%;" placeholder="0.00" />';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #666;">Set to 0 for free items. Updated: 2025-06-18 08:53:05 UTC by justasskh</p>';
    echo '</div>';
}

// Add-on details meta box callback
function wcflow_addon_details_meta_box_callback($post) {
    wp_nonce_field('wcflow_addon_details_nonce', 'wcflow_addon_details_nonce');
    
    $is_featured = get_post_meta($post->ID, '_wcflow_featured', true);
    $stock_status = get_post_meta($post->ID, '_wcflow_stock_status', true);
    $category = get_post_meta($post->ID, '_wcflow_category', true);
    
    echo '<table style="width: 100%;">';
    
    // Featured checkbox
    echo '<tr>';
    echo '<td style="width: 25%; padding: 10px 0;"><label for="wcflow_featured"><strong>Featured Item:</strong></label></td>';
    echo '<td><input type="checkbox" id="wcflow_featured" name="wcflow_featured" value="1" ' . checked($is_featured, '1', false) . ' /> Mark as featured (appears first)</td>';
    echo '</tr>';
    
    // Stock status
    echo '<tr>';
    echo '<td style="padding: 10px 0;"><label for="wcflow_stock_status"><strong>Stock Status:</strong></label></td>';
    echo '<td><select id="wcflow_stock_status" name="wcflow_stock_status" style="width: 200px;">';
    echo '<option value="instock"' . selected($stock_status, 'instock', false) . '>In Stock</option>';
    echo '<option value="outofstock"' . selected($stock_status, 'outofstock', false) . '>Out of Stock</option>';
    echo '<option value="limited"' . selected($stock_status, 'limited', false) . '>Limited Stock</option>';
    echo '</select></td>';
    echo '</tr>';
    
    // Category
    echo '<tr>';
    echo '<td style="padding: 10px 0;"><label for="wcflow_category"><strong>Category:</strong></label></td>';
    echo '<td><select id="wcflow_category" name="wcflow_category" style="width: 200px;">';
    echo '<option value="">Select Category</option>';
    echo '<option value="flowers"' . selected($category, 'flowers', false) . '>Flowers</option>';
    echo '<option value="chocolates"' . selected($category, 'chocolates', false) . '>Chocolates</option>';
    echo '<option value="wines"' . selected($category, 'wines', false) . '>Wines</option>';
    echo '<option value="accessories"' . selected($category, 'accessories', false) . '>Accessories</option>';
    echo '<option value="other"' . selected($category, 'other', false) . '>Other</option>';
    echo '</select></td>';
    echo '</tr>';
    
    echo '</table>';
}

// Usage statistics meta box callback
function wcflow_stats_meta_box_callback($post) {
    $orders_count = get_post_meta($post->ID, '_wcflow_orders_count', true) ?: 0;
    $last_ordered = get_post_meta($post->ID, '_wcflow_last_ordered', true);
    $revenue = get_post_meta($post->ID, '_wcflow_total_revenue', true) ?: 0;
    
    echo '<div style="margin: 15px 0;">';
    echo '<p><strong>Total Orders:</strong> ' . $orders_count . '</p>';
    echo '<p><strong>Total Revenue:</strong> ' . get_woocommerce_currency_symbol() . number_format($revenue, 2) . '</p>';
    
    if ($last_ordered) {
        echo '<p><strong>Last Ordered:</strong> ' . date('M j, Y', strtotime($last_ordered)) . '</p>';
    } else {
        echo '<p><strong>Last Ordered:</strong> Never</p>';
    }
    
    echo '<p style="font-size: 12px; color: #666; margin-top: 10px;">Statistics updated automatically when orders are placed.</p>';
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
            update_post_meta($post_id, 'price', $price);
        }
    }
    
    // Save add-on details
    if ($post_type === 'wcflow_addon' && isset($_POST['wcflow_addon_details_nonce']) && wp_verify_nonce($_POST['wcflow_addon_details_nonce'], 'wcflow_addon_details_nonce')) {
        
        // Featured status
        $featured = isset($_POST['wcflow_featured']) ? '1' : '0';
        update_post_meta($post_id, '_wcflow_featured', $featured);
        
        // Stock status
        if (isset($_POST['wcflow_stock_status'])) {
            $stock_status = sanitize_text_field($_POST['wcflow_stock_status']);
            update_post_meta($post_id, '_wcflow_stock_status', $stock_status);
        }
        
        // Category
        if (isset($_POST['wcflow_category'])) {
            $category = sanitize_text_field($_POST['wcflow_category']);
            update_post_meta($post_id, '_wcflow_category', $category);
        }
    }
    
    // Log the update
    error_log('WooCommerce Gifting Flow: ' . $post_type . ' #' . $post_id . ' updated by justasskh at 2025-06-18 08:53:05 UTC');
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
    $new_columns['wcflow_category'] = 'Category';
    $new_columns['wcflow_stock'] = 'Stock';
    $new_columns['wcflow_orders'] = 'Orders';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

function wcflow_card_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['wcflow_image'] = 'Image';
    $new_columns['wcflow_price'] = 'Price';
    $new_columns['wcflow_categories'] = 'Categories';
    $new_columns['wcflow_orders'] = 'Orders';
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
            $price = get_post_meta($post_id, 'price', true);
            if ($price > 0) {
                echo get_woocommerce_currency_symbol() . number_format($price, 2);
            } else {
                echo '<span style="color: green;">FREE</span>';
            }
            break;
            
        case 'wcflow_category':
            $category = get_post_meta($post_id, '_wcflow_category', true);
            if ($category) {
                $categories = [
                    'flowers' => 'Flowers',
                    'chocolates' => 'Chocolates',
                    'wines' => 'Wines',
                    'accessories' => 'Accessories',
                    'other' => 'Other',
                ];
                echo $categories[$category] ?? $category;
            } else {
                echo '<span style="color: #ccc;">Uncategorized</span>';
            }
            break;
            
        case 'wcflow_stock':
            $stock = get_post_meta($post_id, '_wcflow_stock_status', true) ?: 'instock';
            $statuses = [
                'instock' => '<span style="color: green;">In Stock</span>',
                'outofstock' => '<span style="color: red;">Out of Stock</span>',
                'limited' => '<span style="color: orange;">Limited</span>',
            ];
            echo $statuses[$stock] ?? $statuses['instock'];
            break;
            
        case 'wcflow_orders':
            $orders = get_post_meta($post_id, '_wcflow_orders_count', true) ?: 0;
            echo $orders;
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
            $price = get_post_meta($post_id, 'price', true);
            if ($price > 0) {
                echo get_woocommerce_currency_symbol() . number_format($price, 2);
            } else {
                echo '<span style="color: green;">FREE</span>';
            }
            break;
            
        case 'wcflow_categories':
            $terms = get_the_terms($post_id, 'category');
            if ($terms && !is_wp_error($terms)) {
                $term_names = array_map(function($term) { return $term->name; }, $terms);
                echo implode(', ', $term_names);
            } else {
                echo '<span style="color: #ccc;">Uncategorized</span>';
            }
            break;
            
        case 'wcflow_orders':
            $orders = get_post_meta($post_id, '_wcflow_orders_count', true) ?: 0;
            echo $orders;
            break;
    }
}

// Make columns sortable
add_filter('manage_edit-wcflow_addon_sortable_columns', function($columns) {
    $columns['wcflow_price'] = 'price';
    $columns['wcflow_orders'] = '_wcflow_orders_count';
    return $columns;
});

add_filter('manage_edit-wcflow_card_sortable_columns', function($columns) {
    $columns['wcflow_price'] = 'price';
    $columns['wcflow_orders'] = '_wcflow_orders_count';
    return $columns;
});

// Handle sorting
add_action('pre_get_posts', function($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    
    $orderby = $query->get('orderby');
    
    if ($orderby === 'price') {
        $query->set('meta_key', 'price');
        $query->set('orderby', 'meta_value_num');
    } elseif ($orderby === '_wcflow_orders_count') {
        $query->set('meta_key', '_wcflow_orders_count');
        $query->set('orderby', 'meta_value_num');
    }
});

// Add admin notices for setup
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['wcflow_addon', 'wcflow_card'])) return;
    
    if ($screen->post_type === 'wcflow_addon') {
        $addon_count = wp_count_posts('wcflow_addon')->publish;
        if ($addon_count == 0) {
            ?>
            <div class="notice notice-info">
                <p><strong>Welcome to Gifting Add-ons!</strong> Create your first add-on to offer customers extra items like flowers, chocolates, or accessories. <a href="<?php echo admin_url('post-new.php?post_type=wcflow_addon'); ?>" class="button button-primary">Create Add-on</a></p>
            </div>
            <?php
        }
    }
    
    if ($screen->post_type === 'wcflow_card') {
        $card_count = wp_count_posts('wcflow_card')->publish;
        if ($card_count == 0) {
            ?>
            <div class="notice notice-info">
                <p><strong>Welcome to Greeting Cards!</strong> Create your first greeting card design. Don't forget to assign categories to organize them. <a href="<?php echo admin_url('post-new.php?post_type=wcflow_card'); ?>" class="button button-primary">Create Card</a></p>
            </div>
            <?php
        }
    }
});

// Update statistics when orders are created (hook into WooCommerce)
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if ($new_status !== 'completed') return;
    
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    // Check if this is a gifting flow order
    if ($order->get_meta('_wcflow_order') !== 'yes') return;
    
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $post_type = get_post_type($product_id);
        
        if (in_array($post_type, ['wcflow_addon', 'wcflow_card'])) {
            // Update order count
            $current_count = get_post_meta($product_id, '_wcflow_orders_count', true) ?: 0;
            update_post_meta($product_id, '_wcflow_orders_count', $current_count + 1);
            
            // Update last ordered date
            update_post_meta($product_id, '_wcflow_last_ordered', '2025-06-18 08:53:05');
            
            // Update revenue
            $current_revenue = get_post_meta($product_id, '_wcflow_total_revenue', true) ?: 0;
            $item_total = $item->get_total();
            update_post_meta($product_id, '_wcflow_total_revenue', $current_revenue + $item_total);
        }
    }
}, 10, 3);