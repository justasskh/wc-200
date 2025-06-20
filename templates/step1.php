<?php
/**
 * WooCommerce Gifting Flow Step 1 - ENHANCED with proper spacing and headings
 * Updated: 2025-01-27 - Added spacing, headings, and sustainability message
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
            
            <!-- ENHANCED: Add-ons Gallery with improved spacing -->
            <div class="wcflow-addons-gallery">
                <div id="wcflow-addons-grid" class="wcflow-addons-grid">
                    <div class="wcflow-loader"></div>
                </div>
            </div>
        </div>
        
        <!-- ENHANCED: Greeting Cards Section with proper heading and sustainability message -->
        <div class="wcflow-cards-container">
            <div class="wcflow-content-wrapper">
                <div style="margin-bottom: 40px;">
                    <h2 class="wcflow-section-title">Choose your card</h2>
                    <p class="wcflow-message-subheading">Our cards are 100% tree-free, made from zero-waste recycled sugarcane. With a lovely eggshell texture, they can be recycled, composted, or treasured for years.</p>
                </div>
            </div>
            
            <!-- Multiple Category Sliders Container -->
            <div id="wcflow-cards-container">
                <!-- Birthday Cards Slider -->
                <section class="greeting-cards-section" role="region" aria-label="Birthday Cards" data-category="Birthday Cards">
                    <div class="greeting-cards-container">
                        <div class="greeting-cards-header">
                            <h3 class="greeting-cards-title">Birthday Cards</h3>
                            <a href="#" class="greeting-cards-see-all">See all</a>
                        </div>
                        
                        <p class="greeting-cards-description">
                            Perfect cards for birthday celebrations and special moments
                        </p>
                        
                        <div class="greeting-cards-slider-wrapper">
                            <div class="greeting-cards-slider" role="list">
                                <div class="greeting-card" data-card-id="birthday-1" data-price-value="0" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Happy Birthday Balloons" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Happy Birthday Balloons</h4>
                                        <p class="greeting-card-price free">FREE</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="birthday-2" data-price-value="1.50" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Birthday Cake Celebration" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Birthday Cake Celebration</h4>
                                        <p class="greeting-card-price">€1.50</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="birthday-3" data-price-value="2.50" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Birthday Wishes" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Birthday Wishes</h4>
                                        <p class="greeting-card-price">€2.50</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="birthday-4" data-price-value="1.75" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Party Time" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Party Time</h4>
                                        <p class="greeting-card-price">€1.75</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="birthday-5" data-price-value="2.00" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Another Year Older" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Another Year Older</h4>
                                        <p class="greeting-card-price">€2.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="slider-controls">
                            <div class="slider-progress-container">
                                <div class="slider-progress-bar" role="progressbar" aria-label="Slider progress">
                                    <div class="slider-progress-fill"></div>
                                </div>
                            </div>
                            <div class="slider-nav-controls">
                                <button class="slider-nav slider-nav-prev" aria-label="Previous" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6"/>
                                    </svg>
                                </button>
                                <button class="slider-nav slider-nav-next" aria-label="Next" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Holiday Cards Slider -->
                <section class="greeting-cards-section" role="region" aria-label="Holiday Cards" data-category="Holiday Cards">
                    <div class="greeting-cards-container">
                        <div class="greeting-cards-header">
                            <h3 class="greeting-cards-title">Holiday Cards</h3>
                            <a href="#" class="greeting-cards-see-all">See all</a>
                        </div>
                        
                        <p class="greeting-cards-description">
                            Festive cards for special occasions and celebrations
                        </p>
                        
                        <div class="greeting-cards-slider-wrapper">
                            <div class="greeting-cards-slider" role="list">
                                <div class="greeting-card" data-card-id="holiday-1" data-price-value="0" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Season Greetings" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Season Greetings</h4>
                                        <p class="greeting-card-price free">FREE</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="holiday-2" data-price-value="1.25" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Winter Wonderland" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Winter Wonderland</h4>
                                        <p class="greeting-card-price">€1.25</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="holiday-3" data-price-value="1.50" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Holiday Cheer" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Holiday Cheer</h4>
                                        <p class="greeting-card-price">€1.50</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="holiday-4" data-price-value="1.80" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Festive Joy" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Festive Joy</h4>
                                        <p class="greeting-card-price">€1.80</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="slider-controls">
                            <div class="slider-progress-container">
                                <div class="slider-progress-bar" role="progressbar" aria-label="Slider progress">
                                    <div class="slider-progress-fill"></div>
                                </div>
                            </div>
                            <div class="slider-nav-controls">
                                <button class="slider-nav slider-nav-prev" aria-label="Previous" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6"/>
                                    </svg>
                                </button>
                                <button class="slider-nav slider-nav-next" aria-label="Next" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Thank You Cards Slider -->
                <section class="greeting-cards-section" role="region" aria-label="Thank You Cards" data-category="Thank You Cards">
                    <div class="greeting-cards-container">
                        <div class="greeting-cards-header">
                            <h3 class="greeting-cards-title">Thank You Cards</h3>
                            <a href="#" class="greeting-cards-see-all">See all</a>
                        </div>
                        
                        <p class="greeting-cards-description">
                            Express your gratitude with these beautiful cards
                        </p>
                        
                        <div class="greeting-cards-slider-wrapper">
                            <div class="greeting-cards-slider" role="list">
                                <div class="greeting-card" data-card-id="thanks-1" data-price-value="0" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Thank You So Much" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Thank You So Much</h4>
                                        <p class="greeting-card-price free">FREE</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="thanks-2" data-price-value="1.00" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Grateful Heart" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Grateful Heart</h4>
                                        <p class="greeting-card-price">€1.00</p>
                                    </div>
                                </div>
                                
                                <div class="greeting-card" data-card-id="thanks-3" data-price-value="1.25" role="listitem" tabindex="0">
                                    <img src="https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Much Appreciated" class="greeting-card-image" loading="lazy">
                                    <div class="greeting-card-content">
                                        <h4 class="greeting-card-title">Much Appreciated</h4>
                                        <p class="greeting-card-price">€1.25</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="slider-controls">
                            <div class="slider-progress-container">
                                <div class="slider-progress-bar" role="progressbar" aria-label="Slider progress">
                                    <div class="slider-progress-fill"></div>
                                </div>
                            </div>
                            <div class="slider-nav-controls">
                                <button class="slider-nav slider-nav-prev" aria-label="Previous" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 18l-6-6 6-6"/>
                                    </svg>
                                </button>
                                <button class="slider-nav slider-nav-next" aria-label="Next" type="button">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
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