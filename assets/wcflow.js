/**
 * WooCommerce Gifting Flow - COMPLETE IMPLEMENTATION
 * 2025-01-27 - Fixed greeting card deselection and complete add-ons functionality
 */

jQuery(function($) {
    'use strict';
    
    // Global state
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};
    
    let orderState = window.wcflow.orderState;
    let currentStep = 1;
    
    // Debug helper
    function debug(message, data) {
        console.log('[WCFlow COMPLETE]', message, data || '');
    }
    
    // Enhanced price calculation
    function updatePricing() {
        let basePrice = parseFloat(wcflow_params.base_product_price || 0);
        let addonsTotal = 0;
        let cardPrice = 0;
        let shippingPrice = parseFloat(orderState.shipping_cost || 0);
        
        // Calculate addons total
        $('.wcflow-addon-card.selected').each(function() {
            const priceValue = parseFloat($(this).data('price-value') || 0);
            addonsTotal += priceValue;
        });
        
        // Calculate card price
        const $selectedCard = $('.greeting-card.selected');
        if ($selectedCard.length) {
            cardPrice = parseFloat($selectedCard.data('price-value') || 0);
        }
        
        const subtotal = basePrice + addonsTotal + cardPrice;
        const total = subtotal + shippingPrice;
        
        // Update display with proper formatting
        const currencySymbol = wcflow_params.currency_symbol || '€';
        $('#wcflow-dynamic-total').text(currencySymbol + total.toFixed(2));
        
        if (shippingPrice > 0) {
            $('#wcflow-shipping-details').text('Including ' + currencySymbol + shippingPrice.toFixed(2) + ' delivery');
        } else {
            $('#wcflow-shipping-details').text('Free delivery included');
        }
        
        // Store in order state
        orderState.base_price = basePrice;
        orderState.addons_total = addonsTotal;
        orderState.card_price = cardPrice;
        orderState.subtotal = subtotal;
        orderState.shipping_cost = shippingPrice;
        orderState.total = total;
        
        debug('Pricing updated', {
            base: basePrice,
            addons: addonsTotal,
            card: cardPrice,
            shipping: shippingPrice,
            total: total
        });
    }
    
    // Modal management
    function showModal(html) {
        if (!$('.wcflow-modal-container').length) {
            $('body').append('<div class="wcflow-modal-container"></div>');
        }
        $('.wcflow-modal-container').html(html).addClass('visible');
        $('body').addClass('wcflow-modal-open');
        debug('Modal shown');
    }
    
    function closeModal() {
        $('.wcflow-modal-container').removeClass('visible').empty();
        $('body').removeClass('wcflow-modal-open');
        debug('Modal closed');
    }
    
    // Load step
    function loadStep(step) {
        debug('Loading step', step);
        currentStep = step;
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_step',
                nonce: wcflow_params.nonce,
                step: step
            },
            beforeSend: function() {
                if (!$('.wcflow-modal').length) {
                    showModal('<div class="wcflow-loading-container" style="display:flex;align-items:center;justify-content:center;height:100vh;"><div class="wcflow-loader"></div></div>');
                }
            },
            success: function(response) {
                if (response.success) {
                    showModal(response.data.html);
                    initializeStep(step);
                    $(document).trigger('wcflow_step_loaded', [step]);
                } else {
                    alert('Could not load step. Please try again.');
                    closeModal();
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                closeModal();
            }
        });
    }
    
    // Initialize step-specific functionality
    function initializeStep(step) {
        debug('Initializing step', step);
        
        switch(step) {
            case 1:
                initStep1();
                break;
            case 2:
                initStep2();
                break;
            case 3:
                initStep3();
                break;
        }
    }
    
    // COMPLETE: Step 1 initialization with all features
    function initStep1() {
        debug('Initializing COMPLETE step 1');
        
        // Load complete addons with all functionality
        loadCompleteAddons();
        
        // Load cards and setup deselectable functionality
        loadCardsWithDeselection();
        
        // Message textarea functionality
        $(document).on('input', '#wcflow-card-message', function() {
            const length = $(this).val().length;
            $('#wcflow-message-count').text(length);
            orderState.card_message = $(this).val();
        });
        
        // Initial pricing update
        setTimeout(updatePricing, 500);
    }
    
    // Step 2 initialization
    function initStep2() {
        debug('Initializing step 2');
        
        // Initialize floating labels
        initFloatingLabels();
        
        // Form validation
        setupFormValidation();
        
        // Load delivery options
        loadDeliveryOptions();
        
        // Load shipping methods
        loadShippingMethodsForStep2();
    }
    
    // Step 3 initialization
    function initStep3() {
        debug('Initializing step 3');
        
        // Load cart summary
        loadCartSummary();
        
        // Load payment methods
        loadPaymentMethods();
        
        // Setup billing form toggle
        setupBillingToggle();
    }
    
    // COMPLETE: Load addons with all requested functionality
    function loadCompleteAddons() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_addons',
                nonce: wcflow_params.nonce
            },
            timeout: 10000,
            success: function(response) {
                if (response.success) {
                    renderCompleteAddons(response.data);
                } else {
                    debug('No addons available');
                    $('#wcflow-addons-grid').html('<p style="text-align:center;color:#666;">No add-ons available at this time.</p>');
                }
            },
            error: function() {
                $('#wcflow-addons-grid').html('<p style="text-align:center;color:#666;">Failed to load add-ons.</p>');
            }
        });
    }
    
    // COMPLETE: Render addons with ALL requested features
    function renderCompleteAddons(addons) {
        const $grid = $('#wcflow-addons-grid');
        $grid.empty();
        
        if (addons.length === 0) {
            $grid.html('<p style="text-align:center;color:#666;">No add-ons available.</p>');
            return;
        }
        
        addons.forEach(function(addon) {
            const shortDescription = truncateDescription(addon.description);
            
            const $card = $(`
                <div class="wcflow-addon-card" data-addon-id="${addon.id}" data-price-value="${addon.price_value}">
                    ${addon.img ? `<img src="${addon.img}" alt="${addon.title}" class="wcflow-addon-image">` : ''}
                    <div class="wcflow-addon-content">
                        <h3 class="wcflow-addon-title">${addon.title}</h3>
                        <p class="wcflow-addon-price">${addon.price}</p>
                        <p class="wcflow-addon-description-short">${shortDescription}</p>
                    </div>
                    <div class="wcflow-addon-actions">
                        <button class="wcflow-addon-more-info" data-addon-id="${addon.id}" data-title="${escapeHtml(addon.title)}" data-price="${addon.price}" data-description="${escapeHtml(addon.description)}">
                            More information
                        </button>
                        <button class="wcflow-addon-action add-btn" data-addon-id="${addon.id}">
                            Add
                        </button>
                    </div>
                </div>
            `);
            $grid.append($card);
        });
        
        // COMPLETE: Handle addon selection with toggle functionality
        $(document).off('click', '.wcflow-addon-action').on('click', '.wcflow-addon-action', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.wcflow-addon-card');
            const $button = $(this);
            const $grid = $('#wcflow-addons-grid');
            
            if ($card.hasClass('selected')) {
                // Remove addon
                $card.removeClass('selected');
                $button.removeClass('remove-btn').addClass('add-btn').text('Add');
                debug('Addon removed', {id: $card.data('addon-id')});
            } else {
                // Add addon
                $card.addClass('selected');
                $button.removeClass('add-btn').addClass('remove-btn').text('Remove');
                debug('Addon added', {id: $card.data('addon-id')});
            }
            
            // COMPLETE: Apply dimmed styling only when at least one addon is selected
            updateAddonStyling();
            
            updateOrderState();
            updatePricing();
        });
        
        // COMPLETE: Handle "More information" popup
        $(document).off('click', '.wcflow-addon-more-info').on('click', '.wcflow-addon-more-info', function(e) {
            e.stopPropagation();
            const title = $(this).data('title');
            const price = $(this).data('price');
            const description = $(this).data('description');
            
            showAddonInfoPopup(title, price, description);
        });
        
        // COMPLETE: Handle card click (for selection) - only if not clicking on buttons
        $(document).off('click', '.wcflow-addon-card').on('click', '.wcflow-addon-card', function(e) {
            // Only trigger if not clicking on buttons
            if (!$(e.target).hasClass('wcflow-addon-action') && 
                !$(e.target).hasClass('wcflow-addon-more-info') &&
                !$(e.target).closest('.wcflow-addon-actions').length) {
                $(this).find('.wcflow-addon-action').click();
            }
        });
        
        debug('COMPLETE addons rendered', {count: addons.length});
    }
    
    // COMPLETE: Update addon styling based on selection state
    function updateAddonStyling() {
        const $grid = $('#wcflow-addons-grid');
        const hasSelection = $('.wcflow-addon-card.selected').length > 0;
        
        if (hasSelection) {
            $grid.addClass('has-selection');
        } else {
            $grid.removeClass('has-selection');
        }
        
        debug('Addon styling updated', {hasSelection: hasSelection});
    }
    
    // Helper function to truncate description
    function truncateDescription(description) {
        if (!description) return 'No description available';
        const words = description.split(' ');
        if (words.length > 15) {
            return words.slice(0, 15).join(' ') + '...';
        }
        return description;
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // COMPLETE: Show addon information popup
    function showAddonInfoPopup(title, price, description) {
        const popupHtml = `
            <div class="wcflow-addon-popup">
                <div class="wcflow-addon-popup-content">
                    <button class="wcflow-addon-popup-close">&times;</button>
                    <h3 class="wcflow-addon-popup-title">${title}</h3>
                    <p class="wcflow-addon-popup-price">${price}</p>
                    <div class="wcflow-addon-popup-description">${description}</div>
                </div>
            </div>
        `;
        
        $('body').append(popupHtml);
        
        // Close popup handlers
        $(document).off('click', '.wcflow-addon-popup-close, .wcflow-addon-popup').on('click', '.wcflow-addon-popup-close, .wcflow-addon-popup', function(e) {
            if (e.target === this) {
                $('.wcflow-addon-popup').remove();
            }
        });
        
        // Prevent popup content clicks from closing
        $(document).off('click', '.wcflow-addon-popup-content').on('click', '.wcflow-addon-popup-content', function(e) {
            e.stopPropagation();
        });
        
        debug('Addon popup shown', {title: title});
    }
    
    // COMPLETE: Load cards with deselectable functionality
    function loadCardsWithDeselection() {
        debug('Loading cards with COMPLETE deselectable functionality');
        
        // Load cards from database
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cards',
                nonce: wcflow_params.nonce
            },
            timeout: 15000,
            success: function(response) {
                debug('Cards AJAX response received', response);
                
                if (response && response.success && response.data) {
                    debug('Cards data received successfully', response.data);
                    renderCardsWithDeselection(response.data);
                } else {
                    debug('No cards data in response, using fallback');
                    renderFallbackCards();
                }
            },
            error: function(xhr, status, error) {
                debug('Cards loading failed', {status: status, error: error, xhr: xhr});
                console.error('Cards AJAX Error:', xhr.responseText);
                renderFallbackCards();
            }
        });
    }
    
    // COMPLETE: Render cards with deselectable functionality
    function renderCardsWithDeselection(cardsByCategory) {
        debug('Rendering cards with COMPLETE deselectable selection', cardsByCategory);
        
        const $container = $('#wcflow-dynamic-cards-container');
        $container.empty();
        
        if (!cardsByCategory || Object.keys(cardsByCategory).length === 0) {
            $container.html('<p style="text-align:center;color:#666;padding:40px;">No cards available at this time.</p>');
            return;
        }
        
        // Create a slider for each category
        Object.entries(cardsByCategory).forEach(function([categoryName, cards]) {
            if (!cards || cards.length === 0) return;
            
            debug('Creating COMPLETE slider for category:', categoryName, 'with', cards.length, 'cards');
            
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
        
        debug('All COMPLETE category sliders rendered successfully');
        
        // Initialize card selection after rendering
        setTimeout(function() {
            setupCompleteCardSelection();
            initializeAllCategorySliders();
        }, 100);
    }
    
    // COMPLETE: Setup card selection with deselectable functionality
    function setupCompleteCardSelection() {
        debug('Setting up COMPLETE card selection with deselection capability');
        
        // Remove any existing handlers to prevent duplicates
        $(document).off('click', '.greeting-card');
        $(document).off('keydown', '.greeting-card');
        
        // COMPLETE: Card click handler with deselection
        $(document).on('click', '.greeting-card', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $clickedCard = $(this);
            const cardId = $clickedCard.data('card-id');
            const cardPrice = $clickedCard.data('price-value');
            
            debug('Card clicked', {id: cardId, currentlySelected: $clickedCard.hasClass('selected')});
            
            // COMPLETE: Check if card is already selected
            if ($clickedCard.hasClass('selected')) {
                // Deselect the card
                $clickedCard.removeClass('selected');
                
                // Disable message textarea
                $('#wcflow-card-message').prop('disabled', true).val('');
                $('.wcflow-message-note').show();
                $('#wcflow-message-count').text('0');
                
                // Clear card from order state
                orderState.card_id = null;
                orderState.card_message = '';
                
                debug('Card deselected', {id: cardId});
            } else {
                // Remove selection from all other cards
                $('.greeting-card').removeClass('selected');
                
                // Select this card
                $clickedCard.addClass('selected');
                
                // Enable message textarea
                $('#wcflow-card-message').prop('disabled', false);
                $('.wcflow-message-note').hide();
                
                // Update order state
                orderState.card_id = cardId;
                
                debug('Card selected', {id: cardId, price: cardPrice});
            }
            
            updateOrderState();
            updatePricing();
        });
        
        // Also handle keyboard events for accessibility
        $(document).on('keydown', '.greeting-card', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        debug('COMPLETE card selection setup complete');
    }
    
    // Render fallback cards when AJAX fails
    function renderFallbackCards() {
        debug('Rendering fallback cards');
        
        const fallbackCards = {
            'Birthday Cards': [
                {
                    id: 'fallback-1',
                    title: 'Happy Birthday Balloons',
                    price: 'FREE',
                    price_value: 0,
                    img: 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
                },
                {
                    id: 'fallback-2',
                    title: 'Birthday Cake Celebration',
                    price: '€1.50',
                    price_value: 1.50,
                    img: 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                },
                {
                    id: 'fallback-3',
                    title: 'Birthday Wishes',
                    price: '€2.50',
                    price_value: 2.50,
                    img: 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
                }
            ]
        };
        
        renderCardsWithDeselection(fallbackCards);
    }
    
    // Get category description
    function getCategoryDescription(categoryName) {
        const descriptions = {
            'Birthday Cards': 'Perfect cards for birthday celebrations and special moments',
            'Holiday Cards': 'Festive cards for special occasions and celebrations',
            'Thank You Cards': 'Express your gratitude with these beautiful cards'
        };
        
        return descriptions[categoryName] || 'Beautiful greeting cards for every occasion';
    }
    
    // COMPLETE: Initialize all category sliders
    function initializeAllCategorySliders() {
        debug('Initializing all COMPLETE category sliders...');
        
        $('.greeting-cards-section').each(function() {
            const $section = $(this);
            const categoryName = $section.data('category') || $section.find('.greeting-cards-title').text();
            
            debug('Initializing COMPLETE slider for category:', categoryName);
            
            // Initialize individual slider
            if (window.GreetingCardsSlider) {
                new window.GreetingCardsSlider($section[0]);
            } else {
                // Fallback initialization
                initializeSingleSlider($section);
            }
        });
        
        debug('All COMPLETE category sliders initialized!');
    }
    
    // COMPLETE: Fallback slider initialization
    function initializeSingleSlider($section) {
        const $slider = $section.find('.greeting-cards-slider');
        const $cards = $slider.find('.greeting-card');
        
        if ($cards.length === 0) return;
        
        let currentIndex = 0;
        const cardWidth = 256; // 240px + 16px gap
        const containerWidth = $section.find('.greeting-cards-slider-wrapper').width();
        const visibleCards = Math.floor(containerWidth / cardWidth);
        const maxIndex = Math.max(0, $cards.length - visibleCards);
        
        function updateSlider() {
            const translateX = -currentIndex * cardWidth;
            $slider.css('transform', `translateX(${translateX}px)`);
            
            // Update navigation
            $section.find('.slider-nav-prev').toggleClass('disabled', currentIndex === 0);
            $section.find('.slider-nav-next').toggleClass('disabled', currentIndex >= maxIndex);
            
            // Update progress
            const progress = maxIndex > 0 ? (currentIndex / maxIndex) * 100 : 100;
            $section.find('.slider-progress-fill').css('width', progress + '%');
        }
        
        // Navigation handlers
        $section.find('.slider-nav-prev').on('click', function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        });
        
        $section.find('.slider-nav-next').on('click', function() {
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
        
        // Initial update
        updateSlider();
    }
    
    // Initialize floating labels
    function initFloatingLabels() {
        $('.floating-label input, .floating-label select').each(function() {
            const $input = $(this);
            const $group = $input.closest('.floating-label');
            
            function updateLabel() {
                $group.toggleClass('has-value', $input.val() !== '' || $input.is(':focus'));
            }
            
            $input.on('focus blur input change', updateLabel);
            updateLabel();
        });
    }
    
    // Setup form validation
    function setupFormValidation() {
        // Real-time validation
        $('.wcflow-form-group input, .wcflow-form-group select').on('blur', function() {
            validateField($(this));
        });
        
        // Form submission validation
        window.wcflowValidateStep2 = function() {
            let isValid = true;
            
            // Clear previous errors
            $('.wcflow-form-group').removeClass('has-error');
            $('.wcflow-field-error').text('');
            
            // Required fields
            const requiredFields = [
                '#wcflow-customer-email',
                '#wcflow-shipping-first-name',
                '#wcflow-shipping-last-name',
                '#wcflow-shipping-address-1',
                '#wcflow-shipping-city',
                '#wcflow-shipping-postcode',
                '#wcflow-shipping-country',
                '#wcflow-shipping-phone'
            ];
            
            requiredFields.forEach(function(selector) {
                const $field = $(selector);
                if (!validateField($field)) {
                    isValid = false;
                }
            });
            
            // Update order state
            updateOrderStateFromForm();
            
            return isValid;
        };
    }
    
    // Validate individual field
    function validateField($field) {
        const $group = $field.closest('.wcflow-form-group');
        const $error = $group.find('.wcflow-field-error');
        const value = $field.val().trim();
        
        // Required field check
        if ($field.prop('required') && value === '') {
            $group.addClass('has-error');
            $error.text('This field is required');
            return false;
        }
        
        // Email validation
        if ($field.attr('type') === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                $group.addClass('has-error');
                $error.text('Please enter a valid email address');
                return false;
            }
        }
        
        // Clear error if valid
        $group.removeClass('has-error');
        $error.text('');
        return true;
    }
    
    // Load delivery options
    function loadDeliveryOptions() {
        // Setup date picker
        $(document).on('click', '#wcflow-delivery-date-selector', function() {
            showDatePicker();
        });
    }
    
    // Load shipping methods for Step 2
    function loadShippingMethodsForStep2() {
        const $selector = $('#wcflow-shipping-method-selector');
        const $valueSpan = $selector.find('.selectable-box-value');
        
        debug('Loading shipping methods for Step 2...');
        
        // Show loading state
        $valueSpan.text('Loading shipping methods...');
        
        // Set timeout for loading state
        const loadingTimeout = setTimeout(function() {
            $valueSpan.text('Taking longer than expected...');
        }, 8000);
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_shipping_methods',
                nonce: wcflow_params.nonce
            },
            timeout: 15000,
            success: function(response) {
                clearTimeout(loadingTimeout);
                debug('Shipping methods response received', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    // Set first method as default
                    const firstMethod = response.data[0];
                    const currencySymbol = wcflow_params.currency_symbol || '€';
                    $valueSpan.text(firstMethod.label + ' - ' + currencySymbol + firstMethod.cost_with_tax);
                    
                    orderState.shipping_method = firstMethod.id;
                    orderState.shipping_cost = parseFloat(firstMethod.cost_with_tax);
                    
                    // Update pricing immediately
                    updatePricing();
                    
                    // Setup click handler for popup
                    $selector.off('click').on('click', function() {
                        showShippingMethodsPopup(response.data);
                    });
                    
                    debug('Shipping methods loaded for Step 2', {
                        count: response.data.length,
                        selected: firstMethod,
                        cost: firstMethod.cost_with_tax
                    });
                } else {
                    $valueSpan.text('No shipping methods available');
                    debug('No shipping methods available in response');
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(loadingTimeout);
                $valueSpan.text('Failed to load shipping methods');
                debug('Shipping methods loading failed', {status: status, error: error});
                console.error('Shipping methods error:', xhr.responseText);
            }
        });
    }
    
    // Show shipping methods popup
    function showShippingMethodsPopup(methods) {
        let html = '<div class="wcflow-popup-content wcflow-shipping-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Select Shipping Method</h3>';
        
        const currencySymbol = wcflow_params.currency_symbol || '€';
        
        methods.forEach(function(method) {
            const isSelected = orderState.shipping_method === method.id;
            html += `<div class="wcflow-shipping-option ${isSelected ? 'selected' : ''}" 
                          data-method="${method.id}" 
                          data-cost="${method.cost_with_tax}" 
                          data-label="${method.label}"
                          style="padding:16px;border:2px solid ${isSelected ? '#007cba' : '#e0e0e0'};margin:12px 0;cursor:pointer;border-radius:8px;background:${isSelected ? '#f0f8ff' : '#fff'};transition:all 0.3s ease;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong style="font-size:16px;color:#333;">${method.label}</strong>
                        <div style="font-size:14px;color:#666;margin-top:4px;">Delivery cost</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:18px;font-weight:bold;color:#007cba;">${currencySymbol}${method.cost_with_tax}</div>
                    </div>
                </div>
            </div>`;
        });
        
        html += '</div>';
        
        showPopup(html);
        
        // Handle method selection
        $(document).on('click', '.wcflow-shipping-option', function() {
            const methodId = $(this).data('method');
            const methodCost = parseFloat($(this).data('cost'));
            const methodLabel = $(this).data('label');
            
            // Update main selector display
            $('#wcflow-shipping-method-selector .selectable-box-value').text(methodLabel + ' - ' + currencySymbol + methodCost.toFixed(2));
            
            // Update order state
            orderState.shipping_method = methodId;
            orderState.shipping_cost = methodCost;
            
            // Update pricing
            updatePricing();
            
            debug('Shipping method selected', {
                id: methodId,
                label: methodLabel,
                cost: methodCost
            });
            
            closePopup();
        });
    }
    
    // Show date picker
    function showDatePicker() {
        // Simple date picker implementation
        const today = new Date();
        const minDate = new Date(today.getTime() + (2 * 24 * 60 * 60 * 1000)); // 2 days from now
        
        const dateOptions = [];
        for (let i = 0; i < 14; i++) {
            const date = new Date(minDate.getTime() + (i * 24 * 60 * 60 * 1000));
            const dayOfWeek = date.getDay();
            
            // Skip weekends (assuming delivery only on weekdays)
            if (dayOfWeek === 0 || dayOfWeek === 6) continue;
            
            dateOptions.push({
                value: date.toISOString().split('T')[0],
                label: date.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                })
            });
        }
        
        let html = '<div class="wcflow-popup-content wcflow-calendar-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Select Delivery Date</h3>';
        
        dateOptions.forEach(function(option) {
            html += `<div class="wcflow-date-option" data-date="${option.value}" style="padding:12px;border:1px solid #ddd;margin:8px 0;cursor:pointer;border-radius:4px;">${option.label}</div>`;
        });
        
        html += '</div>';
        
        showPopup(html);
        
        // Handle date selection
        $(document).on('click', '.wcflow-date-option', function() {
            const date = $(this).data('date');
            const label = $(this).text();
            
            $('#wcflow-delivery-date-selector .selectable-box-value').text(label);
            orderState.delivery_date = date;
            
            closePopup();
        });
    }
    
    // Show popup
    function showPopup(html) {
        if (!$('#wcflow-popup-overlay').length) {
            $('body').append('<div id="wcflow-popup-overlay" class="wcflow-popup-overlay"></div>');
        }
        $('#wcflow-popup-overlay').html(html).show();
        
        // Close popup handlers
        $(document).on('click', '.wcflow-popup-close, #wcflow-popup-overlay', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    }
    
    // Close popup
    function closePopup() {
        $('#wcflow-popup-overlay').hide().empty();
    }
    
    // Load cart summary
    function loadCartSummary() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cart_summary',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wcflow-basket-summary-container').html(response.data.html);
                }
            }
        });
    }
    
    // Load payment methods
    function loadPaymentMethods() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_checkout_form',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wcflow-payment-options-container').html(response.data.html);
                    
                    // Initialize payment forms
                    setTimeout(function() {
                        $(document.body).trigger('init_checkout');
                        $(document.body).trigger('update_checkout');
                        
                        // Select first payment method
                        if ($('input[name="payment_method"]:checked').length === 0) {
                            $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
                        }
                    }, 500);
                }
            }
        });
    }
    
    // Setup billing toggle
    function setupBillingToggle() {
        $(document).on('change', '#wcflow-buyer-same', function() {
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                $('#wcflow-billing-form').slideUp();
                copyShippingToBilling();
            } else {
                $('#wcflow-billing-form').slideDown();
                prefillBillingForm();
            }
        });
        
        // Monitor billing form changes
        $(document).on('input change', '#wcflow-billing-form input, #wcflow-billing-form select', function() {
            const key = $(this).data('wcflow-billing');
            if (key) {
                orderState[key] = $(this).val();
            }
        });
    }
    
    // Copy shipping to billing
    function copyShippingToBilling() {
        orderState.billing_first_name = orderState.shipping_first_name || '';
        orderState.billing_last_name = orderState.shipping_last_name || '';
        orderState.billing_address_1 = orderState.shipping_address_1 || '';
        orderState.billing_city = orderState.shipping_city || '';
        orderState.billing_postcode = orderState.shipping_postcode || '';
        orderState.billing_country = orderState.shipping_country || '';
        orderState.billing_phone = orderState.shipping_phone || '';
        orderState.billing_email = orderState.customer_email || '';
    }
    
    // Prefill billing form
    function prefillBillingForm() {
        $('input[data-wcflow-billing="billing_first_name"]').val(orderState.shipping_first_name || '');
        $('input[data-wcflow-billing="billing_last_name"]').val(orderState.shipping_last_name || '');
        $('input[data-wcflow-billing="billing_address_1"]').val(orderState.shipping_address_1 || '');
        $('input[data-wcflow-billing="billing_city"]').val(orderState.shipping_city || '');
        $('input[data-wcflow-billing="billing_postcode"]').val(orderState.shipping_postcode || '');
        $('input[data-wcflow-billing="billing_country"]').val(orderState.shipping_country || '');
        $('input[data-wcflow-billing="billing_phone"]').val(orderState.shipping_phone || '');
        $('input[data-wcflow-billing="billing_email"]').val(orderState.customer_email || '');
    }
    
    // Update order state from form
    function updateOrderStateFromForm() {
        // Customer email
        orderState.customer_email = $('#wcflow-customer-email').val();
        
        // Shipping fields
        orderState.shipping_first_name = $('#wcflow-shipping-first-name').val();
        orderState.shipping_last_name = $('#wcflow-shipping-last-name').val();
        orderState.shipping_address_1 = $('#wcflow-shipping-address-1').val();
        orderState.shipping_city = $('#wcflow-shipping-city').val();
        orderState.shipping_postcode = $('#wcflow-shipping-postcode').val();
        orderState.shipping_country = $('#wcflow-shipping-country').val();
        orderState.shipping_phone = $('#wcflow-shipping-phone').val();
        
        // Ensure billing email is set
        if (!orderState.billing_email) {
            orderState.billing_email = orderState.customer_email;
        }
        
        debug('Order state updated from form', orderState);
    }
    
    // Update order state
    function updateOrderState() {
        // Selected addons
        const selectedAddons = [];
        $('.wcflow-addon-card.selected').each(function() {
            selectedAddons.push($(this).data('addon-id'));
        });
        orderState.addons = selectedAddons;
        
        // Selected card
        const $selectedCard = $('.greeting-card.selected');
        if ($selectedCard.length) {
            orderState.card_id = $selectedCard.data('card-id');
            orderState.card_message = $('#wcflow-card-message').val();
        } else {
            // Clear card data if no card selected
            orderState.card_id = null;
            orderState.card_message = '';
        }
        
        // Payment method
        const selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
        if (selectedPaymentMethod) {
            orderState.payment_method = selectedPaymentMethod;
        }
        
        debug('Order state updated', orderState);
    }
    
    // Navigation handlers
    $(document).on('click', '.wcflow-btn-next:not(#wcflow-place-order-btn)', function() {
        const step = $(this).closest('.wcflow-modal').data('step');
        
        // Validate step 2
        if (step === 2 && window.wcflowValidateStep2) {
            if (!window.wcflowValidateStep2()) {
                return;
            }
        }
        
        loadStep(step + 1);
    });
    
    $(document).on('click', '.wcflow-btn-prev', function() {
        const step = $(this).closest('.wcflow-modal').data('step');
        if (step > 1) {
            loadStep(step - 1);
        }
    });
    
    // Start flow
    $(document).on('click', '.wcflow-start-btn', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('product-id') || window.wcflow_product_id;
        if (!productId) {
            alert('Product not found.');
            return;
        }
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_start_flow',
                nonce: wcflow_params.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    // Set base product price and real shipping cost from WooCommerce
                    wcflow_params.base_product_price = response.data.product_price || 0;
                    orderState.base_price = response.data.product_price || 0;
                    orderState.shipping_cost = response.data.shipping_cost || 0;
                    
                    debug('Flow started with base price', wcflow_params.base_product_price);
                    debug('Default shipping cost from WooCommerce', orderState.shipping_cost);
                    
                    loadStep(1);
                } else {
                    alert(response.data ? response.data.message : 'Failed to start checkout');
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    });
    
    // Place order
    $(document).on('click', '#wcflow-place-order-btn', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        
        // Update order state
        updateOrderState();
        updateOrderStateFromForm();
        
        // Handle buyer same checkbox
        if ($('#wcflow-buyer-same').is(':checked')) {
            copyShippingToBilling();
        }
        
        // Get payment method
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        if (selectedMethod) {
            orderState.payment_method = selectedMethod;
        }
        
        // Validate required fields
        const requiredFields = [
            'shipping_first_name', 'shipping_last_name', 'shipping_address_1',
            'shipping_city', 'shipping_postcode', 'shipping_country'
        ];
        
        let missingFields = [];
        requiredFields.forEach(function(field) {
            if (!orderState[field] || orderState[field].trim() === '') {
                missingFields.push(field.replace('_', ' '));
            }
        });
        
        // Check email
        if (!orderState.customer_email && !orderState.billing_email) {
            missingFields.push('email address');
        }
        
        // Check payment method
        if (!orderState.payment_method) {
            missingFields.push('payment method');
        }
        
        if (missingFields.length > 0) {
            $('#wcflow-payment-error').html('Required fields missing: ' + missingFields.join(', ')).show();
            return;
        }
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Processing...');
        $('#wcflow-payment-error').hide();
        
        // Create order
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_create_order',
                nonce: wcflow_params.nonce,
                state: orderState
            },
            success: function(response) {
                if (response.success && response.data && response.data.redirect_url) {
                    $('.wcflow-modal-content').html(`
                        <div style="text-align:center;padding:40px;">
                            <h2>Order Created Successfully!</h2>
                            <p>Your order #${response.data.order_id} has been created.</p>
                            <p>Redirecting to payment...</p>
                            <div class="wcflow-loader" style="margin:20px auto;"></div>
                        </div>
                    `);
                    
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    $('#wcflow-payment-error').html(response.data && response.data.message ? response.data.message : 'Payment error').show();
                    $button.prop('disabled', false).text('Place an order');
                }
            },
            error: function() {
                $('#wcflow-payment-error').html('Network error. Please try again.').show();
                $button.prop('disabled', false).text('Place an order');
            }
        });
    });
    
    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('.wcflow-modal-container.visible').length) {
            closeModal();
        }
    });
    
    debug('COMPLETE WCFlow JavaScript initialized with all features working');
});