<?php
/**
 * WooCommerce Gifting Flow Step 1 - Updated design to match screenshot
 * Updated: 2025-01-27 - Modern greeting cards slider design
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
            
            <div class="wcflow-addons-gallery">
                <div id="wcflow-addons-grid" class="wcflow-addons-grid">
                    <div class="wcflow-loader"></div>
                </div>
            </div>
        </div>
        
        <!-- Updated Greeting Cards Slider to match design -->
        <section class="greeting-cards-section" role="region" aria-label="Greeting Cards">
            <div class="greeting-cards-container">
                <div class="greeting-cards-header">
                    <h2 class="greeting-cards-title">Birthday</h2>
                    <a href="#" class="greeting-cards-see-all">See all</a>
                </div>
                
                <p class="greeting-cards-description">
                    Because it wouldn't be a birthday without a card. Pick your fave design, and add your own celebratory note.
                </p>
                
                <div class="greeting-cards-slider-wrapper">
                    <button class="slider-nav slider-nav-prev" aria-label="Previous cards" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    
                    <button class="slider-nav slider-nav-next" aria-label="Next cards" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                    
                    <div id="wcflow-cards-slider" class="greeting-cards-slider" role="list">
                        <div class="wcflow-loader"></div>
                    </div>
                </div>
            </div>
        </section>

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
    
    <footer class="wcflow-bottom-bar">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-bottom-bar-inner">
                <div class="wcflow-order-summary">
                    <div class="wcflow-order-total-line">
                        <span class="wcflow-order-label">Order Total</span>
                        <span class="wcflow-order-amount" id="wcflow-dynamic-total">£0.00</span>
                    </div>
                    <div class="wcflow-order-details">
                        <span class="wcflow-order-breakdown" id="wcflow-shipping-details">Including £0.00 delivery</span>
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