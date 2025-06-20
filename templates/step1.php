<?php
/**
 * WooCommerce Gifting Flow Step 1 - ENHANCED with Consistent Bottom Bar
 * Updated: 2025-01-27 - Persistent pricing and enhanced user experience
 */
?>
<div class="wcflow-modal wcflow-fullscreen" data-step="1">
    <header class="wcflow-header">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-header-inner">
                <div class="wcflow-header-left">
                    <button class="wcflow-back-btn" onclick="window.history.back()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Continue Shopping
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
    
    <div class="wcflow-modal-body wcflow-step1-body">
        <div class="wcflow-loading-overlay" style="display: none;">
            <div class="wcflow-loader"></div>
        </div>
        
        <div class="wcflow-content-wrapper">
            <h2 class="wcflow-main-title">They're going to love it</h2>
            <p class="wcflow-subtitle">Add some extras to make your gift even more special</p>
            
            <!-- Enhanced Add-ons Gallery -->
            <div class="wcflow-addons-gallery">
                <div id="wcflow-addons-grid" class="wcflow-addons-grid">
                    <div class="wcflow-loader"></div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Greeting Cards Section -->
        <div class="wcflow-cards-container">
            <div class="wcflow-content-wrapper">
                <div style="margin-bottom: 40px;">
                    <h2 class="wcflow-section-title">Choose your card</h2>
                    <p class="wcflow-message-subheading">Our cards are 100% tree-free, made from zero-waste recycled sugarcane. With a lovely eggshell texture, they can be recycled, composted, or treasured for years.</p>
                </div>
            </div>
            
            <!-- Dynamic Category Sliders Container -->
            <div id="wcflow-dynamic-cards-container">
                <div class="wcflow-loader" style="text-align: center; padding: 40px;">
                    <p>Loading greeting cards from database...</p>
                </div>
            </div>
        </div>

        <div class="wcflow-content-wrapper">
            <section class="wcflow-message-section">
                <h2 class="wcflow-section-title">Write your message</h2>
                <p class="wcflow-message-subheading">A few heartfelt words can mean the world. Remember to include your name so they know it's from you.</p>
                <div class="wcflow-message-wrapper">
                    <textarea id="wcflow-card-message" class="wcflow-message-textarea" disabled maxlength="450" placeholder="Dear [Name], Happy Birthday! I hope this brings a smile to your face. Love, [Your Name]"></textarea>
                </div>
                <div class="wcflow-message-count"><span id="wcflow-message-count">0</span> of 450 characters</div>
                <p class="wcflow-message-note">Tip: Select a card above to enable the message field</p>
            </section>
        </div>
    </div>
    
    <!-- ENHANCED: Consistent Bottom Bar with Persistent Pricing -->
    <footer class="wcflow-bottom-bar">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-bottom-bar-inner">
                <div class="wcflow-order-summary">
                    <div class="wcflow-order-total-line">
                        <span class="wcflow-order-label">Order Total</span>
                        <span class="wcflow-order-amount" id="wcflow-dynamic-total">0.00 €</span>
                    </div>
                    <div class="wcflow-order-details">
                        <span class="wcflow-order-breakdown" id="wcflow-shipping-details">Įskaičiuotas 0.00 € pristatymo mokestis</span>
                    </div>
                </div>
                <div class="wcflow-bottom-bar-action">
                    <button type="button" class="wcflow-btn-next wcflow-bottom-bar-btn">
                        Continue to Delivery Information
                    </button>
                </div>
            </div>
        </div>
    </footer>
</div>