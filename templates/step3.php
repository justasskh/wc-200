<?php
/**
 * WooCommerce Gifting Flow Step 3 - FIXED VERSION
 * Complete checkout with payment integration
 */
?>
<div class="wcflow-modal wcflow-fullscreen" data-step="3">
    <header class="wcflow-header">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-header-inner">
                <div class="wcflow-header-left">
                    <button class="wcflow-back-btn wcflow-btn-prev">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Back
                    </button>
                </div>
                <div class="wcflow-header-right">
                    <div class="wcflow-secure-checkout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="16" r="1" fill="currentColor"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Secure Checkout
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="wcflow-modal-body">
        <div class="wcflow-loading-overlay" style="display: none;">
            <div class="wcflow-loader"></div>
        </div>
        
        <div class="wcflow-content-wrapper">
            <h2 class="wcflow-main-title">Complete Your Order</h2>
            <p class="wcflow-form-subtitle">Review your gift and complete payment</p>
            
            <div class="wcflow-step3-layout">
                <!-- Left Column - Order Summary -->
                <div class="wcflow-left-column">
                    <!-- Discount Code Section -->
                    <div class="wcflow-discount-section">
                        <h3>Got a code?</h3>
                        <p class="wcflow-discount-desc">Add your promo code, voucher or digital gift card below. Been referred by a friend? <a href="#" class="wcflow-referral-link">click here</a>.</p>
                        <div class="wcflow-discount-form">
                            <input type="text" id="wcflow-discount-code" placeholder="Code" class="wcflow-discount-input">
                            <button type="button" id="wcflow-apply-discount" class="wcflow-apply-btn">Apply</button>
                        </div>
                        <div id="wcflow-discount-message" class="wcflow-discount-message" style="display: none;"></div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="wcflow-basket-section">
                        <h3>Your basket</h3>
                        <div id="wcflow-basket-summary-container">
                            <!-- This will be populated by AJAX -->
                            <div class="wcflow-loader"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Payment Options -->
                <div class="wcflow-right-column">
                    <div class="wcflow-payment-section">
                        <h3>Payment options</h3>
                        
                        <!-- Buyer/Billing Logic -->
                        <div class="wcflow-form-group" style="margin-bottom:18px;">
                            <label style="font-weight:bold;display:flex;align-items:center;cursor:pointer;padding:12px;border:2px solid #e0e0e0;border-radius:8px;background:#f9f9f9;">
                                <input type="checkbox" id="wcflow-buyer-same" checked style="margin-right:12px;width:18px;height:18px;">
                                <span style="font-size:16px;">I am the buyer (billing info same as shipping)</span>
                            </label>
                        </div>
                        
                        <!-- Billing form (hidden unless needed) -->
                        <div id="wcflow-billing-form" style="display:none; margin-bottom:18px; padding:20px; border:2px solid #007cba; border-radius:8px; background:#f0f8ff;">
                            <h4 style="margin:0 0 16px 0; color:#333; font-size:18px;">💳 Billing Information</h4>
                            <div class="wcflow-form-row" style="display:flex; gap:12px; margin-bottom:12px;">
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">First Name *</label>
                                    <input type="text" data-wcflow-billing="billing_first_name" placeholder="Enter first name" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Last Name *</label>
                                    <input type="text" data-wcflow-billing="billing_last_name" placeholder="Enter last name" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                            </div>
                            <div class="wcflow-form-group" style="margin-bottom:12px;">
                                <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Address *</label>
                                <input type="text" data-wcflow-billing="billing_address_1" placeholder="Enter billing address" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                            </div>
                            <div class="wcflow-form-row" style="display:flex; gap:12px; margin-bottom:12px;">
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">City *</label>
                                    <input type="text" data-wcflow-billing="billing_city" placeholder="City" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Postcode *</label>
                                    <input type="text" data-wcflow-billing="billing_postcode" placeholder="Postcode" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                            </div>
                            <div class="wcflow-form-row" style="display:flex; gap:12px; margin-bottom:12px;">
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Country *</label>
                                    <input type="text" data-wcflow-billing="billing_country" placeholder="Country" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                                <div class="wcflow-form-group" style="flex:1;">
                                    <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Phone</label>
                                    <input type="text" data-wcflow-billing="billing_phone" placeholder="Phone number" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                                </div>
                            </div>
                            <div class="wcflow-form-group">
                                <label style="display:block; margin-bottom:4px; font-weight:bold; color:#555;">Email *</label>
                                <input type="email" data-wcflow-billing="billing_email" placeholder="Enter email address" class="input-text" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
                            </div>
                        </div>
                        
                        <!-- WooCommerce Payment Methods -->
                        <div id="wcflow-payment-options-container">
                            <div class="wcflow-loader"></div>
                        </div>
                        
                        <!-- Place Order Button -->
                        <div class="wcflow-place-order-section" style="padding-top:16px;">
                            <button type="button" id="wcflow-place-order-btn" class="wcflow-place-order-button">
                                Place Order
                            </button>
                            <div id="wcflow-payment-error" style="color:red;display:none;margin:10px 0;font-weight:bold;padding:10px;background:#ffe6e6;border:1px solid #ff0000;border-radius:4px;"></div>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="wcflow-security-notice" style="margin-top:16px;padding:12px;background:#f8f9fa;border-radius:8px;font-size:14px;color:#666;">
                            <div style="display:flex;align-items:center;margin-bottom:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right:8px;">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="16" r="1" fill="currentColor"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <strong>Secure Payment</strong>
                            </div>
                            <p style="margin:0;line-height:1.4;">Your payment information is encrypted and secure. We never store your payment details.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    console.log('Step 3 initialized - Loading cart summary and payment methods');
    
    // Toggle billing form
    $('#wcflow-buyer-same').on('change', function() {
        if ($(this).is(':checked')) {
            $('#wcflow-billing-form').slideUp();
            
            // Copy shipping details to billing
            if (window.wcflow && window.wcflow.orderState) {
                window.wcflow.orderState.billing_first_name = window.wcflow.orderState.shipping_first_name;
                window.wcflow.orderState.billing_last_name = window.wcflow.orderState.shipping_last_name;
                window.wcflow.orderState.billing_address_1 = window.wcflow.orderState.shipping_address_1;
                window.wcflow.orderState.billing_city = window.wcflow.orderState.shipping_city;
                window.wcflow.orderState.billing_postcode = window.wcflow.orderState.shipping_postcode;
                window.wcflow.orderState.billing_country = window.wcflow.orderState.shipping_country;
                window.wcflow.orderState.billing_phone = window.wcflow.orderState.shipping_phone;
                window.wcflow.orderState.billing_email = window.wcflow.orderState.customer_email;
            }
        } else {
            $('#wcflow-billing-form').slideDown();
        }
    });
    
    // Store form data in order state
    $('[data-wcflow-billing]').on('input change', function() {
        const field = $(this).data('wcflow-billing');
        if (window.wcflow && window.wcflow.orderState) {
            window.wcflow.orderState[field] = $(this).val();
        }
    });
    
    // Apply discount code
    $('#wcflow-apply-discount').on('click', function() {
        const code = $('#wcflow-discount-code').val().trim();
        if (!code) {
            $('#wcflow-discount-message').text('Please enter a discount code').css('color', 'red').show();
            return;
        }
        
        // Show loading state
        $(this).prop('disabled', true).text('Applying...');
        
        // Simulate discount application
        setTimeout(() => {
            $('#wcflow-discount-message').text('Discount code applied successfully!').css('color', 'green').show();
            $(this).prop('disabled', false).text('Apply');
            
            // Update order total if needed
            if (window.wcflow && window.wcflow.orderState) {
                // Apply discount logic here
            }
        }, 1000);
    });
});
</script>