/**
 * WooCommerce Gifting Flow - Checkout Fix
 * Handles proper WooCommerce checkout within popup
 * 
 * @package WooCommerce_Gifting_Flow
 * @author justasskh
 * @version 4.3
 * @since 2025-06-19
 * @updated 2025-06-19 10:05:31 UTC
 */

(function($) {
    'use strict';
    
    var wcflowCheckoutHandler = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Load checkout form when step 3 is shown
            $(document).on('wcflow_step_loaded', this.handleStepLoaded);
            
            // Handle place order button
            $(document).on('click', '#wcflow-place-order-btn', this.handlePlaceOrder);
            
            // Handle payment method changes
            $(document).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange);
        },
        
        handleStepLoaded: function(e, step) {
            if (step === 3) {
                wcflowCheckoutHandler.loadCheckoutForm();
            }
        },
        
        loadCheckoutForm: function() {
            console.log('[WCFLOW] Loading checkout form...');
            
            // Show loading
            $('#wcflow-payment-options-container').html('<div class="wcflow-loader"></div>');
            
            $.ajax({
                url: wcflow_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcflow_get_checkout_form',
                    nonce: wcflow_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Insert checkout form
                        $('#wcflow-payment-options-container').html(response.data.html);
                        
                        // Load required scripts
                        wcflowCheckoutHandler.loadCheckoutScripts(response.data.scripts);
                        
                        // Update customer data from previous steps
                        wcflowCheckoutHandler.updateCheckoutData();
                        
                        // Initialize payment forms
                        setTimeout(function() {
                            wcflowCheckoutHandler.initializePaymentForms();
                        }, 500);
                        
                        console.log('[WCFLOW] Checkout form loaded successfully');
                    } else {
                        $('#wcflow-payment-options-container').html(
                            '<div class="woocommerce-error">' + response.data.message + '</div>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[WCFLOW] Failed to load checkout form:', error);
                    $('#wcflow-payment-options-container').html(
                        '<div class="woocommerce-error">Failed to load payment options. Please try again.</div>'
                    );
                }
            });
        },
        
        loadCheckoutScripts: function(scripts) {
            // Load WooCommerce scripts if not already loaded
            if (typeof wc_checkout_params === 'undefined') {
                $('<script>').attr('src', scripts['wc-checkout']).appendTo('head');
            }
            
            if (typeof wc_credit_card_form_params === 'undefined') {
                $('<script>').attr('src', scripts['wc-credit-card-form']).appendTo('head');
            }
        },
        
        updateCheckoutData: function() {
            // Get customer data from previous steps
            var customerData = window.wcflow && window.wcflow.orderState ? 
                window.wcflow.orderState : {};
            
            console.log('[WCFLOW] Updating checkout with customer data:', customerData);
            
            // Update checkout form if available
            if (window.wcflowCheckout && typeof window.wcflowCheckout.updateCustomerData === 'function') {
                window.wcflowCheckout.updateCustomerData(customerData);
            }
        },
        
        initializePaymentForms: function() {
            console.log('[WCFLOW] Initializing payment forms...');
            
            // Trigger WooCommerce events
            $(document.body).trigger('init_checkout');
            $(document.body).trigger('update_checkout');
            $(document.body).trigger('wc-credit-card-form-init');
            $(document.body).trigger('payment_method_selected');
            
            // Select first payment method if none selected
            if ($('input[name="payment_method"]:checked').length === 0) {
                $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
            }
            
            // Initialize Stripe and other payment gateways
            setTimeout(function() {
                // Stripe initialization
                if (typeof window.Stripe !== 'undefined') {
                    console.log('[WCFLOW] Stripe detected, triggering initialization');
                    $(document.body).trigger('updated_checkout');
                }
                
                // Mollie initialization  
                if (typeof window.Mollie !== 'undefined') {
                    console.log('[WCFLOW] Mollie detected, triggering initialization');
                    $(document.body).trigger('mollie_checkout_init');
                }
                
                // General payment gateway initialization
                $(document.body).trigger('checkout_loaded');
            }, 1000);
        },
        
        handlePaymentMethodChange: function(e) {
            var selectedMethod = $(this).val();
            console.log('[WCFLOW] Payment method changed to:', selectedMethod);
            
            // Hide all payment boxes
            $('.payment_box').hide();
            
            // Show selected payment box
            $('.payment_method_' + selectedMethod + ' .payment_box').show();
            
            // Trigger payment method events
            $(document.body).trigger('payment_method_selected', [selectedMethod]);
            
            // Re-initialize payment forms
            setTimeout(function() {
                $(document.body).trigger('wc-credit-card-form-init');
            }, 100);
        },
        
        handlePlaceOrder: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $form = $('.woocommerce-checkout');
            
            if (!$form.length) {
                alert('Checkout form not found. Please refresh and try again.');
                return;
            }
            
            console.log('[WCFLOW] Processing checkout...');
            
            // Disable button and show loading
            $button.prop('disabled', true).text('Processing...');
            $('.wcflow-spinner').remove();
            $button.after('<div class="wcflow-spinner"></div>');
            $('#wcflow-payment-error').hide();
            
            // Validate terms if required
            if ($('#terms').length && !$('#terms').is(':checked')) {
                $('#wcflow-payment-error').html('You must accept the terms and conditions.').show();
                $button.prop('disabled', false).text('Place an order');
                $('.wcflow-spinner').remove();
                return;
            }
            
            // Get form data
            var formData = $form.serialize();
            formData += '&action=wcflow_process_checkout';
            formData += '&nonce=' + wcflow_params.nonce;
            
            // Add customer data from orderState
            var customerData = window.wcflow && window.wcflow.orderState ? 
                window.wcflow.orderState : {};
            
            // Ensure required fields are present
            if (!customerData.customer_email) {
                $('#wcflow-payment-error').html('Customer email is required.').show();
                $button.prop('disabled', false).text('Place an order');
                $('.wcflow-spinner').remove();
                return;
            }
            
            // Process checkout
            $.ajax({
                url: wcflow_params.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('[WCFLOW] Checkout response:', response);
                    
                    if (response.success) {
                        var result = response.data;
                        
                        if (result.result === 'success') {
                            // Show success message
                            $('.wcflow-modal-content').html(`
                                <div style="text-align:center;padding:40px;">
                                    <h2>Order Created Successfully!</h2>
                                    <p>Your order #${result.order_id} has been created.</p>
                                    <p>Redirecting to payment...</p>
                                    <div class="wcflow-loader" style="margin:20px auto;"></div>
                                </div>
                            `);
                            
                            // Redirect after delay
                            setTimeout(function() {
                                window.location.href = result.redirect;
                            }, 1500);
                        } else {
                            // Handle payment redirect or other result
                            if (result.redirect) {
                                window.location.href = result.redirect;
                            } else {
                                $('#wcflow-payment-error').html('Payment processing completed.').show();
                                $button.prop('disabled', false).text('Place an order');
                                $('.wcflow-spinner').remove();
                            }
                        }
                    } else {
                        // Show error
                        $('#wcflow-payment-error').html(response.data.message || 'An error occurred during checkout.').show();
                        $button.prop('disabled', false).text('Place an order');
                        $('.wcflow-spinner').remove();
                        
                        console.error('[WCFLOW] Checkout failed:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#wcflow-payment-error').html('Network error. Please try again.').show();
                    $button.prop('disabled', false).text('Place an order');
                    $('.wcflow-spinner').remove();
                    
                    console.error('[WCFLOW] AJAX error:', {status, error, response: xhr.responseText});
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        wcflowCheckoutHandler.init();
    });
    
})(jQuery);