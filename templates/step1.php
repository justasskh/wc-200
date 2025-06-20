<?php
/**
 * WooCommerce Gifting Flow Step 1 - FIXED VERSION
 * Updated: 2025-01-27 - Fixed greeting card deselection and enhanced add-ons functionality
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
            
            <!-- Add-ons Gallery with improved spacing and functionality -->
            <div class="wcflow-addons-gallery">
                <h2 class="wcflow-section-title">Add-ons</h2>
                <p class="wcflow-message-subheading">Enhance your gift with these special add-ons</p>
                <div id="wcflow-addons-grid" class="wcflow-addons-grid">
                    <div class="wcflow-loader"></div>
                </div>
            </div>
        </div>
        
        <!-- Greeting Cards Section with proper heading and sustainability message -->
        <div class="wcflow-cards-container">
            <div class="wcflow-content-wrapper">
                <div style="margin-bottom: 40px;">
                    <h2 class="wcflow-section-title">Choose your card</h2>
                    <p class="wcflow-message-subheading">Our cards are 100% tree-free, made from zero-waste recycled sugarcane. With a lovely eggshell texture, they can be recycled, composted, or treasured for years.</p>
                </div>
            </div>
            
            <!-- Dynamic Category Sliders Container -->
            <div id="wcflow-dynamic-cards-container">
                <!-- This will be populated by JavaScript with real database data -->
                <div class="wcflow-loader" style="text-align: center; padding: 40px;">
                    <p>Loading greeting cards...</p>
                </div>
            </div>
        </div>

        <div class="wcflow-content-wrapper">
            <section class="wcflow-message-section">
                <h2 class="wcflow-section-title">Write your message</h2>
                <div class="wcflow-message-wrapper">
                    <textarea id="wcflow-card-message" class="wcflow-message-textarea" disabled maxlength="250" placeholder=""></textarea>
                    <div class="wcflow-message-placeholder">
                        A few heartfelt words can mean the world. Remember to include your name so they know it's from you.
                    </div>
                </div>
                <div class="wcflow-message-count"><span id="wcflow-message-count">0</span> of 250 characters</div>
                <p class="wcflow-message-note">Tip: Select a card above to enable the message field</p>
            </section>
        </div>
    </div>
    
    <footer class="wcflow-bottom-bar">
        <div class="wcflow-content-wrapper">
            <div class="wcflow-bottom-bar-inner">
                <div class="wcflow-order-summary">
                    <div class="wcflow-order-total-line">
                        <span class="wcflow-order-label">Order Total:</span>
                        <span class="wcflow-order-amount" id="wcflow-dynamic-total">€0.00</span>
                    </div>
                    <div class="wcflow-order-details">
                        <span class="wcflow-order-breakdown" id="wcflow-shipping-details">Including €0.00 shipping</span>
                    </div>
                </div>
                <div class="wcflow-bottom-bar-action">
                    <button type="button" class="wcflow-btn-next wcflow-bottom-bar-btn">Continue to Shipping</button>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
// Load cards dynamically from admin dashboard with deselectable functionality
jQuery(document).ready(function($) {
    console.log('Loading cards from database...');
    
    // Load cards from database via AJAX
    $.ajax({
        url: wcflow_params.ajax_url,
        type: 'POST',
        data: {
            action: 'wcflow_get_cards',
            nonce: wcflow_params.nonce
        },
        timeout: 15000,
        success: function(response) {
            console.log('Database response received');
            
            if (response && response.success && response.data) {
                renderDynamicCategorySliders(response.data);
            } else {
                console.log('No data received, showing fallback message');
                $('#wcflow-dynamic-cards-container').html(
                    '<div style="text-align: center; padding: 40px; color: #666;">' +
                    '<p>No greeting cards available at this time.</p>' +
                    '<p><small>Please add cards in the admin dashboard.</small></p>' +
                    '</div>'
                );
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load cards:', error);
            $('#wcflow-dynamic-cards-container').html(
                '<div style="text-align: center; padding: 40px; color: #dc3545;">' +
                '<p>Failed to load greeting cards.</p>' +
                '<p><small>Please check your database connection.</small></p>' +
                '</div>'
            );
        }
    });
    
    // Render category sliders dynamically with deselectable cards
    function renderDynamicCategorySliders(cardsByCategory) {
        console.log('Rendering category sliders:', Object.keys(cardsByCategory));
        
        const $container = $('#wcflow-dynamic-cards-container');
        $container.empty();
        
        if (!cardsByCategory || Object.keys(cardsByCategory).length === 0) {
            $container.html(
                '<div style="text-align: center; padding: 40px; color: #666;">' +
                '<p>No greeting card categories found.</p>' +
                '</div>'
            );
            return;
        }
        
        // Create a slider for each category
        Object.entries(cardsByCategory).forEach(function([categoryName, cards]) {
            if (!cards || cards.length === 0) return;
            
            console.log('Creating slider for category:', categoryName, 'with', cards.length, 'cards');
            
            const categorySlug = categoryName.toLowerCase().replace(/[^a-z0-9]/g, '-');
            
            const sliderHtml = `
                <section class="greeting-cards-section" role="region" aria-label="${categoryName}" data-category="${categoryName}">
                    <div class="greeting-cards-container">
                        <div class="greeting-cards-header">
                            <h3 class="greeting-cards-title">${categoryName}</h3>
                            <a href="#" class="greeting-cards-see-all">See all</a>
                        </div>
                        
                        <p class="greeting-cards-description">
                            ${getCategoryDescription(categoryName)}
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
                            
                            <div class="greeting-cards-slider" role="list">
                                ${cards.map(card => `
                                    <div class="greeting-card" data-card-id="${card.id}" data-price-value="${card.price_value}" role="listitem" tabindex="0">
                                        <img src="${card.img}" alt="${card.title}" class="greeting-card-image" loading="lazy">
                                        <div class="greeting-card-content">
                                            <h4 class="greeting-card-title">${card.title}</h4>
                                            <p class="greeting-card-price ${card.price_value == 0 ? 'free' : ''}">${card.price}</p>
                                        </div>
                                    </div>
                                `).join('')}
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
            `;
            
            $container.append(sliderHtml);
        });
        
        console.log('All category sliders rendered successfully');
        
        // Initialize sliders after rendering
        setTimeout(function() {
            initializeAllCategorySliders();
        }, 100);
    }
    
    // Get category description
    function getCategoryDescription(categoryName) {
        const descriptions = {
            'Birthday Cards': 'Perfect cards for birthday celebrations and special moments',
            'Holiday Cards': 'Festive cards for special occasions and celebrations',
            'Thank You Cards': 'Express your gratitude with these beautiful cards',
            'Birthday': 'Because it wouldn't be a birthday without a card. Pick your fave design, and add your own celebratory note.'
        };
        
        return descriptions[categoryName] || 'Beautiful greeting cards for every occasion';
    }
    
    // Initialize all category sliders with deselectable functionality
    function initializeAllCategorySliders() {
        console.log('Initializing all category sliders...');
        
        $('.greeting-cards-section').each(function() {
            const $section = $(this);
            const categoryName = $section.data('category') || $section.find('.greeting-cards-title').text();
            
            console.log('Initializing slider for category:', categoryName);
            
            // Initialize individual slider
            initializeSingleSlider($section);
        });
        
        console.log('All category sliders initialized!');
    }
    
    // Initialize single slider with deselectable cards
    function initializeSingleSlider($section) {
        const $slider = $section.find('.greeting-cards-slider');
        const $cards = $slider.find('.greeting-card');
        const $prevBtn = $section.find('.slider-nav-prev');
        const $nextBtn = $section.find('.slider-nav-next');
        const $progressFill = $section.find('.slider-progress-fill');
        
        if ($cards.length === 0) return;
        
        let currentIndex = 0;
        const cardWidth = 256; // 240px + 16px gap
        const containerWidth = $section.find('.greeting-cards-slider-wrapper').width();
        const visibleCards = Math.floor(containerWidth / cardWidth);
        const maxIndex = Math.max(0, $cards.length - visibleCards);
        let selectedCard = null;
        
        function updateSlider() {
            const translateX = -currentIndex * cardWidth;
            $slider.css('transform', `translateX(${translateX}px)`);
            
            // Update navigation
            $prevBtn.toggleClass('disabled', currentIndex === 0);
            $nextBtn.toggleClass('disabled', currentIndex >= maxIndex);
            
            // Update progress
            const progress = maxIndex > 0 ? (currentIndex / maxIndex) * 100 : 100;
            $progressFill.css('width', progress + '%');
        }
        
        // Navigation handlers
        $prevBtn.on('click', function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        });
        
        $nextBtn.on('click', function() {
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateSlider();
            }
        });
        
        // See all toggle
        $section.find('.greeting-cards-see-all').on('click', function(e) {
            e.preventDefault();
            $section.toggleClass('grid-view');
            $(this).text($section.hasClass('grid-view') ? 'See less' : 'See all');
        });
        
        // Card click handler with deselection functionality
        $cards.each(function() {
            $(this).on('click', function() {
                const $card = $(this);
                
                // Toggle selection
                if (selectedCard === this) {
                    // Deselect current card
                    $card.removeClass('selected');
                    selectedCard = null;
                    
                    // Disable message textarea
                    $('#wcflow-card-message').prop('disabled', true);
                    $('.wcflow-message-note').show();
                    
                    // Update order state
                    if (window.wcflow && window.wcflow.orderState) {
                        window.wcflow.orderState.card_id = null;
                        window.wcflow.orderState.card_price = 0;
                    }
                } else {
                    // Remove previous selection
                    if (selectedCard) {
                        $(selectedCard).removeClass('selected');
                    }
                    
                    // Select new card
                    $card.addClass('selected');
                    selectedCard = this;
                    
                    // Enable message textarea
                    $('#wcflow-card-message').prop('disabled', false);
                    $('.wcflow-message-note').hide();
                    
                    // Update order state
                    if (window.wcflow && window.wcflow.orderState) {
                        window.wcflow.orderState.card_id = $card.data('card-id');
                        window.wcflow.orderState.card_price = parseFloat($card.data('price-value')) || 0;
                    }
                }
                
                // Update pricing if available
                if (typeof window.updatePricing === 'function') {
                    window.updatePricing();
                }
            });
        });
        
        // Initial update
        updateSlider();
    }
});
</script>