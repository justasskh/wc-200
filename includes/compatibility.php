<?php
/**
 * WooCommerce Gifting Flow Compatibility Layer
 * Ensures compatibility with WooCommerce hooks, filters, and third-party plugins
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Compatibility {
    
    public function __construct() {
        add_action('init', array($this, 'init_compatibility'), 20);
        add_action('woocommerce_init', array($this, 'woocommerce_compatibility'));
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'display_meta_key'), 10, 3);
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'display_meta_value'), 10, 3);
    }
    
    /**
     * Initialize compatibility features
     */
    public function init_compatibility() {
        // Ensure WooCommerce sessions are available
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
        }
        
        // Initialize customer if not exists
        if (!WC()->customer) {
            WC()->customer = new WC_Customer();
        }
        
        // Initialize cart if not exists
        if (!WC()->cart) {
            WC()->cart = new WC_Cart();
        }
        
        // Hook into WooCommerce order status changes
        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
        
        // Hook into WooCommerce email system
        add_action('woocommerce_email_order_details', array($this, 'add_gifting_details_to_email'), 15, 4);
        
        // Hook into order admin display
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'display_gifting_details_admin'));
    }
    
    /**
     * WooCommerce specific compatibility
     */
    public function woocommerce_compatibility() {
        // Ensure checkout is available
        if (!WC()->checkout()) {
            WC()->checkout = new WC_Checkout();
        }
        
        // Ensure shipping is available
        if (!WC()->shipping) {
            WC()->shipping = new WC_Shipping();
        }
        
        // Ensure payment gateways are available
        if (!WC()->payment_gateways) {
            WC()->payment_gateways = new WC_Payment_Gateways();
        }
        
        // Hook into checkout process
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_checkout_fields'));
        
        // Hook into cart calculations
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_gifting_fees'));
        
        // Hook into order creation
        add_action('woocommerce_checkout_create_order', array($this, 'add_order_meta'), 10, 2);
    }
    
    /**
     * Handle order status changes
     */
    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        $delivery_date = $order->get_meta('_delivery_date');
        $greeting_message = $order->get_meta('_greeting_card_message');
        
        // Log status change for gifting orders
        if ($delivery_date || $greeting_message) {
            wcflow_log("Gifting order #{$order_id} status changed from {$old_status} to {$new_status}");
            
            // Send notification for specific status changes
            if ($new_status === 'processing') {
                do_action('wcflow_order_processing', $order_id, $order);
            } elseif ($new_status === 'completed') {
                do_action('wcflow_order_completed', $order_id, $order);
            }
        }
    }
    
    /**
     * Add gifting details to order emails
     */
    public function add_gifting_details_to_email($order, $sent_to_admin, $plain_text, $email) {
        $delivery_date = $order->get_meta('_delivery_date');
        $greeting_message = $order->get_meta('_greeting_card_message');
        $card_id = $order->get_meta('_greeting_card_id');
        
        if ($delivery_date || $greeting_message || $card_id) {
            if ($plain_text) {
                echo "\n" . str_repeat('=', 50) . "\n";
                echo "GIFT DETAILS\n";
                echo str_repeat('=', 50) . "\n";
                
                if ($delivery_date) {
                    echo "Requested Delivery Date: " . date('l, F j, Y', strtotime($delivery_date)) . "\n";
                }
                
                if ($card_id) {
                    $card = get_post($card_id);
                    if ($card) {
                        echo "Greeting Card: " . $card->post_title . "\n";
                    }
                }
                
                if ($greeting_message) {
                    echo "Personal Message:\n" . $greeting_message . "\n";
                }
                
                echo str_repeat('=', 50) . "\n";
            } else {
                ?>
                <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-left: 4px solid #007cba;">
                    <h2 style="color: #007cba; margin-top: 0;">🎁 Gift Details</h2>
                    
                    <?php if ($delivery_date) : ?>
                        <p><strong>Requested Delivery Date:</strong> <?php echo date('l, F j, Y', strtotime($delivery_date)); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($card_id) : ?>
                        <?php $card = get_post($card_id); ?>
                        <?php if ($card) : ?>
                            <p><strong>Greeting Card:</strong> <?php echo esc_html($card->post_title); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($greeting_message) : ?>
                        <p><strong>Personal Message:</strong></p>
                        <div style="background: white; padding: 15px; border-radius: 4px; font-style: italic; border-left: 3px solid #007cba;">
                            <?php echo nl2br(esc_html($greeting_message)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Display gifting details in admin order view
     */
    public function display_gifting_details_admin($order) {
        $delivery_date = $order->get_meta('_delivery_date');
        $greeting_message = $order->get_meta('_greeting_card_message');
        $card_id = $order->get_meta('_greeting_card_id');
        $selected_addons = $order->get_meta('_selected_addons');
        
        if ($delivery_date || $greeting_message || $card_id || $selected_addons) {
            ?>
            <div class="wcflow-admin-details" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #007cba;">🎁 Gifting Flow Details</h3>
                
                <?php if ($delivery_date) : ?>
                    <p><strong>Requested Delivery Date:</strong> <?php echo date('l, F j, Y', strtotime($delivery_date)); ?></p>
                <?php endif; ?>
                
                <?php if ($card_id) : ?>
                    <?php $card = get_post($card_id); ?>
                    <?php if ($card) : ?>
                        <p><strong>Greeting Card:</strong> <?php echo esc_html($card->post_title); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($selected_addons && is_array($selected_addons)) : ?>
                    <p><strong>Selected Add-ons:</strong></p>
                    <ul style="margin-left: 20px;">
                        <?php foreach ($selected_addons as $addon_id) : ?>
                            <?php $addon = get_post($addon_id); ?>
                            <?php if ($addon) : ?>
                                <li><?php echo esc_html($addon->post_title); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if ($greeting_message) : ?>
                    <p><strong>Personal Message:</strong></p>
                    <div style="background: white; padding: 10px; border-radius: 4px; font-style: italic; max-width: 400px;">
                        <?php echo nl2br(esc_html($greeting_message)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    
    /**
     * Validate checkout fields
     */
    public function validate_checkout_fields() {
        // Add any additional validation for gifting flow fields
        if (isset($_POST['wcflow_delivery_date'])) {
            $delivery_date = sanitize_text_field($_POST['wcflow_delivery_date']);
            $date = DateTime::createFromFormat('Y-m-d', $delivery_date);
            
            if (!$date || $date <= new DateTime()) {
                wc_add_notice('Please select a valid future delivery date.', 'error');
            }
        }
    }
    
    /**
     * Save checkout fields
     */
    public function save_checkout_fields($order_id) {
        if (isset($_POST['wcflow_delivery_date'])) {
            update_post_meta($order_id, '_delivery_date', sanitize_text_field($_POST['wcflow_delivery_date']));
        }
        
        if (isset($_POST['wcflow_greeting_message'])) {
            update_post_meta($order_id, '_greeting_card_message', sanitize_textarea_field($_POST['wcflow_greeting_message']));
        }
    }
    
    /**
     * Add gifting fees to cart
     */
    public function add_gifting_fees() {
        // This is handled in the order creation process
        // but we can add hooks here for third-party compatibility
        do_action('wcflow_calculate_fees');
    }
    
    /**
     * Add order meta during checkout
     */
    public function add_order_meta($order, $data) {
        // Add any additional meta data during standard checkout
        // This ensures compatibility with both flows
        do_action('wcflow_add_order_meta', $order, $data);
    }
    
    /**
     * Display custom meta keys in admin
     */
    public function display_meta_key($display_key, $meta, $item) {
        if (strpos($meta->key, '_wcflow_') === 0) {
            $key = str_replace('_wcflow_', '', $meta->key);
            $key = str_replace('_', ' ', $key);
            return ucwords($key);
        }
        return $display_key;
    }
    
    /**
     * Display custom meta values in admin
     */
    public function display_meta_value($display_value, $meta, $item) {
        if ($meta->key === '_wcflow_delivery_date') {
            return date('F j, Y', strtotime($meta->value));
        }
        
        if ($meta->key === '_wcflow_greeting_card_message') {
            return wp_trim_words($meta->value, 20);
        }
        
        return $display_value;
    }
}

new WCFlow_Compatibility();

/**
 * Third-party plugin compatibility
 */

// WooCommerce Subscriptions compatibility
if (class_exists('WC_Subscriptions')) {
    add_filter('woocommerce_subscription_payment_meta', function($payment_meta, $subscription) {
        // Add gifting flow meta to subscription payments
        $delivery_date = $subscription->get_meta('_delivery_date');
        if ($delivery_date) {
            $payment_meta['wcflow'] = array(
                'post_meta' => array(
                    '_delivery_date' => array(
                        'value' => $delivery_date,
                        'label' => 'Delivery Date'
                    )
                )
            );
        }
        return $payment_meta;
    }, 10, 2);
}

// WooCommerce PDF Invoices compatibility
if (class_exists('WC_PDF_Invoice')) {
    add_action('wpo_wcpdf_after_order_details', function($type, $order) {
        if ($type === 'invoice') {
            $delivery_date = $order->get_meta('_delivery_date');
            $greeting_message = $order->get_meta('_greeting_card_message');
            
            if ($delivery_date || $greeting_message) {
                echo '<div class="gifting-details">';
                echo '<h3>Gift Details</h3>';
                
                if ($delivery_date) {
                    echo '<p><strong>Delivery Date:</strong> ' . date('F j, Y', strtotime($delivery_date)) . '</p>';
                }
                
                if ($greeting_message) {
                    echo '<p><strong>Message:</strong> ' . esc_html(wp_trim_words($greeting_message, 15)) . '</p>';
                }
                
                echo '</div>';
            }
        }
    }, 10, 2);
}

// WPML compatibility
if (defined('ICL_SITEPRESS_VERSION')) {
    add_filter('wcflow_translate_string', function($string, $context = 'wcflow') {
        if (function_exists('icl_translate')) {
            return icl_translate('wcflow', $context, $string);
        }
        return $string;
    }, 10, 2);
}