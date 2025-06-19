<?php
/**
 * WooCommerce Gifting Flow Product Integration
 * Handles product variations, add-ons, and custom fields
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Product_Integration {
    
    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'add_gifting_flow_button'), 35);
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_gifting_flow_data'), 10, 3);
        add_action('woocommerce_add_to_cart', array($this, 'store_gifting_flow_data'), 10, 6);
        add_filter('woocommerce_get_item_data', array($this, 'display_gifting_flow_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_gifting_flow_data_to_order'), 10, 4);
    }
    
    /**
     * Add gifting flow button to product pages
     */
    public function add_gifting_flow_button() {
        global $product;
        
        if (!$product || !$product->get_id()) return;
        
        // Check if product supports gifting flow
        $enable_gifting = get_post_meta($product->get_id(), '_enable_wcflow_gifting', true);
        
        if ($enable_gifting !== 'yes') {
            // Enable by default for all products
            $enable_gifting = 'yes';
        }
        
        if ($enable_gifting === 'yes') {
            ?>
            <div class="wcflow-product-integration" style="margin-top: 20px;">
                <script>window.wcflow_product_id = <?php echo intval($product->get_id()); ?>;</script>
                
                <?php if ($product->is_type('variable')) : ?>
                    <p class="wcflow-variation-notice" style="font-size: 14px; color: #666; margin-bottom: 10px;">
                        Please select product options above before using the gifting flow.
                    </p>
                <?php endif; ?>
                
                <button type="button" 
                        class="button alt wcflow-start-btn" 
                        style="width: 100%; background: #007cba; border-color: #007cba; color: white; padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 6px; transition: all 0.3s ease;" 
                        data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                        <?php echo $product->is_type('variable') ? 'disabled' : ''; ?>>
                    <span style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/>
                            <path d="M2 7h20l-2 5H4l-2-5z"/>
                            <path d="M12 22V7"/>
                            <path d="M8 7V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v4"/>
                        </svg>
                        Send as Gift
                    </span>
                </button>
                
                <p style="font-size: 12px; color: #666; text-align: center; margin-top: 8px;">
                    Complete checkout with greeting cards, personal messages, and delivery options
                </p>
            </div>
            
            <?php if ($product->is_type('variable')) : ?>
                <script>
                jQuery(document).ready(function($) {
                    // Enable/disable gifting button based on variation selection
                    $('form.variations_form').on('found_variation', function(event, variation) {
                        $('.wcflow-start-btn').prop('disabled', false).data('variation-id', variation.variation_id);
                        $('.wcflow-variation-notice').hide();
                    }).on('reset_data', function() {
                        $('.wcflow-start-btn').prop('disabled', true).removeData('variation-id');
                        $('.wcflow-variation-notice').show();
                    });
                });
                </script>
            <?php endif; ?>
            
            <style>
            .wcflow-start-btn:hover {
                background: #005a87 !important;
                border-color: #005a87 !important;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(0, 124, 186, 0.3);
            }
            
            .wcflow-start-btn:disabled {
                background: #ccc !important;
                border-color: #ccc !important;
                cursor: not-allowed;
                transform: none;
                box-shadow: none;
            }
            </style>
            <?php
        }
    }
    
    /**
     * Validate gifting flow data when adding to cart
     */
    public function validate_gifting_flow_data($passed, $product_id, $quantity) {
        // Add any custom validation logic here
        return $passed;
    }
    
    /**
     * Store gifting flow data when adding to cart
     */
    public function store_gifting_flow_data($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Store any additional data needed for the gifting flow
        if (isset($_POST['wcflow_data'])) {
            WC()->session->set('wcflow_cart_data_' . $cart_item_key, $_POST['wcflow_data']);
        }
    }
    
    /**
     * Display gifting flow data in cart
     */
    public function display_gifting_flow_data($item_data, $cart_item) {
        $wcflow_data = WC()->session->get('wcflow_cart_data_' . $cart_item['key']);
        
        if ($wcflow_data) {
            if (isset($wcflow_data['greeting_card'])) {
                $item_data[] = array(
                    'key'   => 'Greeting Card',
                    'value' => $wcflow_data['greeting_card']
                );
            }
            
            if (isset($wcflow_data['message'])) {
                $item_data[] = array(
                    'key'   => 'Personal Message',
                    'value' => wp_trim_words($wcflow_data['message'], 10)
                );
            }
            
            if (isset($wcflow_data['delivery_date'])) {
                $item_data[] = array(
                    'key'   => 'Delivery Date',
                    'value' => date('F j, Y', strtotime($wcflow_data['delivery_date']))
                );
            }
        }
        
        return $item_data;
    }
    
    /**
     * Save gifting flow data to order line items
     */
    public function save_gifting_flow_data_to_order($item, $cart_item_key, $values, $order) {
        $wcflow_data = WC()->session->get('wcflow_cart_data_' . $cart_item_key);
        
        if ($wcflow_data) {
            foreach ($wcflow_data as $key => $value) {
                $item->add_meta_data('_wcflow_' . $key, $value);
            }
        }
    }
}

new WCFlow_Product_Integration();

/**
 * Add product meta box for gifting flow settings
 */
add_action('add_meta_boxes', function() {
    add_meta_box(
        'wcflow_product_settings',
        'Gifting Flow Settings',
        'wcflow_product_settings_callback',
        'product',
        'side',
        'default'
    );
});

function wcflow_product_settings_callback($post) {
    wp_nonce_field('wcflow_product_settings', 'wcflow_product_settings_nonce');
    
    $enable_gifting = get_post_meta($post->ID, '_enable_wcflow_gifting', true);
    $enable_gifting = $enable_gifting ?: 'yes'; // Default to enabled
    
    ?>
    <div style="margin: 15px 0;">
        <label style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" 
                   name="enable_wcflow_gifting" 
                   value="yes" 
                   <?php checked($enable_gifting, 'yes'); ?>>
            <span>Enable Gifting Flow for this product</span>
        </label>
        <p style="margin-top: 8px; font-size: 12px; color: #666;">
            When enabled, customers can use the streamlined gifting checkout process.
        </p>
    </div>
    <?php
}

/**
 * Save product gifting flow settings
 */
add_action('save_post', function($post_id) {
    if (!isset($_POST['wcflow_product_settings_nonce']) || 
        !wp_verify_nonce($_POST['wcflow_product_settings_nonce'], 'wcflow_product_settings')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $enable_gifting = isset($_POST['enable_wcflow_gifting']) ? 'yes' : 'no';
    update_post_meta($post_id, '_enable_wcflow_gifting', $enable_gifting);
});