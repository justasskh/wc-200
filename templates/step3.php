<?php
/**
 * WooCommerce Gifting Flow Step 3 - ENHANCED with Consistent Pricing Display
 * Updated: 2025-01-27 - Improved layout and persistent pricing
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
                        Atgal
                    </button>
                </div>
                <div class="wcflow-header-right">
                    <div class="wcflow-secure-checkout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="16" r="1" fill="currentColor"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Saugus atsiskaitymas
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
            <p class="wcflow-subtitle">Review your order and complete payment</p>
            
            <div class="wcflow-step3-layout">
                <!-- Left Column - Cart Summary -->
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
                    
                    <!-- Your Basket Section -->
                    <div class="wcflow-basket-section">
                        <h3>Your basket</h3>
                        <div id="wcflow-basket-summary-container">
                            <div class="wcflow-loader"></div>
                        </div>
                        
                        <!-- ENHANCED: Order Total Display -->
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 18px; font-weight: 600; color: #333;">Order Total</span>
                                <span style="font-size: 24px; font-weight: 700; color: #333;" id="wcflow-dynamic-total-step3">0.00 €</span>
                            </div>
                            <div style="text-align: right;">
                                <span style="font-size: 14px; color: #666;" id="wcflow-shipping-details-step3">Įskaičiuotas 0.00 € pristatymo mokestis</span>
                            </div>
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
                        
                        <div id="wcflow-payment-options-container">
                            <div class="wcflow-loader"></div>
                        </div>
                        
                        <div class="wcflow-place-order-section" style="padding-top:16px;">
                            <button type="button" id="wcflow-place-order-btn" class="wcflow-place-order-button">
                                Place an order
                            </button>
                            <div id="wcflow-payment-error" style="color:red;display:none;margin:10px 0;font-weight:bold;padding:10px;background:#ffe6e6;border:1px solid #ff0000;border-radius:4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ENHANCED: Sync pricing display in Step 3
jQuery(document).ready(function($) {
    // Update Step 3 pricing displays when pricing changes
    function updateStep3Pricing() {
        const totalAmount = $('#wcflow-dynamic-total').text();
        const shippingDetails = $('#wcflow-shipping-details').text();
        
        $('#wcflow-dynamic-total-step3').text(totalAmount);
        $('#wcflow-shipping-details-step3').text(shippingDetails);
    }
    
    // Monitor for pricing updates
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.id === 'wcflow-dynamic-total' || mutation.target.id === 'wcflow-shipping-details') {
                updateStep3Pricing();
            }
        });
    });
    
    // Start observing
    const totalElement = document.getElementById('wcflow-dynamic-total');
    const shippingElement = document.getElementById('wcflow-shipping-details');
    
    if (totalElement) {
        observer.observe(totalElement, { childList: true, subtree: true });
    }
    if (shippingElement) {
        observer.observe(shippingElement, { childList: true, subtree: true });
    }
    
    // Initial sync
    setTimeout(updateStep3Pricing, 500);
});
</script>