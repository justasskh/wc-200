<?php
/**
 * WooCommerce Gifting Flow Step 3 - Complete Checkout with 3-Step Basket Design
 * Updated: 2025-06-20 - Complete implementation with WooCommerce payment integration
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
            <p class="wcflow-form-subtitle">Review your gift and complete payment</p>
            
            <div class="wcflow-step3-layout">
                <!-- Left Column - 3-Step Basket Design -->
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
                    
                    <!-- 3-Step Basket Summary -->
                    <div class="wcflow-basket-section">
                        <h3>Your basket</h3>
                        <div id="wcflow-basket-summary-container">
                            <!-- This will be populated by AJAX with the 3-step design -->
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
                                <span class="wcflow-place-order-text">Place Order</span>
                                <span class="wcflow-place-order-total" id="wcflow-place-order-total">€0.00</span>
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

<style>
/* Step 3 Layout Styles */
.wcflow-step3-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 40px;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .wcflow-step3-layout {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

/* Left Column Styles */
.wcflow-left-column {
    min-height: 400px;
}

.wcflow-discount-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 30px;
}

.wcflow-discount-section h3 {
    margin: 0 0 8px 0;
    font-size: 20px;
    color: #333;
}

.wcflow-discount-desc {
    margin: 0 0 16px 0;
    color: #666;
    line-height: 1.5;
}

.wcflow-discount-form {
    display: flex;
    gap: 12px;
}

.wcflow-discount-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.wcflow-discount-input:focus {
    outline: none;
    border-color: #007cba;
}

.wcflow-apply-btn {
    padding: 12px 24px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.wcflow-apply-btn:hover {
    background: #005a8b;
}

.wcflow-basket-section h3 {
    margin: 0 0 20px 0;
    font-size: 24px;
    color: #333;
}

/* Right Column Styles */
.wcflow-right-column {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 24px;
    height: fit-content;
    position: sticky;
    top: 20px;
}

.wcflow-payment-section h3 {
    margin: 0 0 20px 0;
    font-size: 24px;
    color: #333;
}

/* Place Order Button Styles */
.wcflow-place-order-button {
    width: 100%;
    background: linear-gradient(135deg, #007cba 0%, #005a8b 100%);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0, 124, 186, 0.3);
}

.wcflow-place-order-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 124, 186, 0.4);
}

.wcflow-place-order-button:active {
    transform: translateY(0);
}

.wcflow-place-order-button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Basket Summary Styles */
.wcflow-basket-summary {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.wcflow-basket-item {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.wcflow-basket-item:last-child {
    border-bottom: none;
}

.wcflow-basket-item-img {
    width: 80px;
    height: 80px;
    margin-right: 16px;
    background: #f5f5f5;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.wcflow-basket-item-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wcflow-basket-item-details {
    flex: 1;
}

.wcflow-basket-item-details h4 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.wcflow-basket-item-details p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.wcflow-basket-item-price {
    font-size: 18px;
    font-weight: 700;
    color: #007cba;
}

/* Add-on and Card Styles */
.wcflow-basket-addon,
.wcflow-basket-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #f0f0f0;
}

.wcflow-basket-addon span:first-child,
.wcflow-basket-card span:first-child {
    color: #666;
    font-size: 14px;
}

.wcflow-basket-addon span:last-child,
.wcflow-basket-card span:last-child {
    font-weight: 600;
    color: #333;
}

/* Message Styles */
.wcflow-basket-message {
    padding: 16px 20px;
    background: #f0f8ff;
    border-bottom: 1px solid #f0f0f0;
}

.wcflow-basket-message p {
    margin: 0;
    line-height: 1.4;
}

.wcflow-basket-message p:first-child {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.wcflow-basket-message p:last-child {
    color: #333;
    font-style: italic;
    margin-top: 4px;
}

/* Recipient and Delivery Styles */
.wcflow-basket-recipient,
.wcflow-basket-delivery {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.wcflow-basket-recipient h4,
.wcflow-basket-delivery h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    color: #333;
}

.wcflow-basket-recipient p,
.wcflow-basket-delivery p {
    margin: 0;
    color: #666;
    line-height: 1.4;
}

/* Totals Styles */
.wcflow-basket-subtotal,
.wcflow-basket-shipping {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.wcflow-basket-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    font-size: 18px;
    font-weight: 700;
}

.wcflow-basket-total strong:first-child {
    color: #333;
}

.wcflow-basket-total strong:last-child {
    color: #007cba;
}

/* Loading Styles */
.wcflow-loader {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    console.log('🛒 Step 3 initialized - Loading cart summary and payment methods');
    
    // Update place order button total
    function updatePlaceOrderTotal() {
        const total = window.wcflow && window.wcflow.orderState && window.wcflow.orderState.total 
            ? parseFloat(window.wcflow.orderState.total) 
            : 0;
        $('#wcflow-place-order-total').text('€' + total.toFixed(2));
    }
    
    // Initial update
    updatePlaceOrderTotal();
    
    // Update when order state changes
    $(document).on('wcflow_pricing_updated', function() {
        updatePlaceOrderTotal();
    });
    
    // Store form data in order state
    $('[data-wcflow-billing]').on('input change', function() {
        const field = $(this).data('wcflow-billing');
        if (window.wcflow && window.wcflow.orderState) {
            window.wcflow.orderState[field] = $(this).val();
        }
    });
    
    // Store customer email in order state
    $('#wcflow-customer-email').on('input change', function() {
        if (window.wcflow && window.wcflow.orderState) {
            window.wcflow.orderState.customer_email = $(this).val();
        }
    });
});
</script>
