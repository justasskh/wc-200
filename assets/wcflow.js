/**
 * WooCommerce Gifting Flow - CONSISTENT BOTTOM BAR & ENHANCED SHIPPING
 * 2025-01-27 - Persistent pricing, enhanced shipping section, date picker
 */

jQuery(function($) {
    'use strict';
    
    // Global state with persistent pricing
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};
    window.wcflow.priceState = window.wcflow.priceState || {
        basePrice: 0,
        addonsTotal: 0,
        cardPrice: 0,
        shippingCost: 0,
        total: 0,
        isLoaded: false
    };
    
    let orderState = window.wcflow.orderState;
    let priceState = window.wcflow.priceState;
    let currentStep = 1;
    
    // Debug helper
    function debug(message, data) {
        if (wcflow_params.debug) {
            console.log('[WCFlow ENHANCED]', message, data || '');
        }
    }
    
    // ENHANCED: Persistent price calculation with state management
    function updatePricing() {
        let basePrice = parseFloat(wcflow_params.base_product_price || 0);
        let addonsTotal = 0;
        let cardPrice = 0;
        let shippingPrice = parseFloat(orderState.shipping_cost || priceState.shippingCost || 0);
        
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
        
        // Update persistent price state
        priceState.basePrice = basePrice;
        priceState.addonsTotal = addonsTotal;
        priceState.cardPrice = cardPrice;
        priceState.shippingCost = shippingPrice;
        priceState.total = total;
        priceState.isLoaded = true;
        
        // Store in order state as well
        orderState.base_price = basePrice;
        orderState.addons_total = addonsTotal;
        orderState.card_price = cardPrice;
        orderState.subtotal = subtotal;
        orderState.shipping_cost = shippingPrice;
        orderState.total = total;
        
        // Update display with proper formatting
        const currencySymbol = wcflow_params.currency_symbol || '€';
        updateBottomBarDisplay(total, shippingPrice, currencySymbol);
        
        debug('Pricing updated and persisted', {
            base: basePrice,
            addons: addonsTotal,
            card: cardPrice,
            shipping: shippingPrice,
            total: total
        });
    }
    
    // ENHANCED: Update bottom bar display with consistent formatting
    function updateBottomBarDisplay(total, shippingPrice, currencySymbol) {
        const $totalElement = $('#wcflow-dynamic-total');
        const $shippingElement = $('#wcflow-shipping-details');
        
        if ($totalElement.length) {
            // Format: "Order total [price] [currency symbol]"
            $totalElement.text(`${total.toFixed(2)} ${currencySymbol}`);
        }
        
        if ($shippingElement.length) {
            // Format: "Įskaičiuotas [price] [currency symbol] pristatymo mokestis"
            if (shippingPrice > 0) {
                $shippingElement.text(`Įskaičiuotas ${shippingPrice.toFixed(2)} ${currencySymbol} pristatymo mokestis`);
            } else {
                $shippingElement.text('Nemokamas pristatymas');
            }
        }
    }
    
    // ENHANCED: Restore pricing state when loading steps
    function restorePricingState() {
        if (priceState.isLoaded) {
            const currencySymbol = wcflow_params.currency_symbol || '€';
            updateBottomBarDisplay(priceState.total, priceState.shippingCost, currencySymbol);
            debug('Pricing state restored', priceState);
        }
    }
    
    // Modal management
    function showModal(html) {
        if (!$('.wcflow-modal-container').length) {
            $('body').append('<div class="wcflow-modal-container"></div>');
        }
        $('.wcflow-modal-container').html(html).addClass('visible');
        $('body').addClass('wcflow-modal-open');
        debug('Modal shown');
        
        // Restore pricing state after modal is shown
        setTimeout(restorePricingState, 100);
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
    
    // Step 1 initialization
    function initStep1() {
        debug('Initializing step 1');
        
        // Load enhanced addons
        loadEnhancedAddons();
        
        // Load cards and setup enhanced selection
        loadCardsWithEnhancedSelection();
        
        // Message textarea functionality
        $(document).on('input', '#wcflow-card-message', function() {
            const length = $(this).val().length;
            $('#wcflow-message-count').text(length);
            orderState.card_message = $(this).val();
        });
        
        // Initial pricing update
        setTimeout(updatePricing, 500);
    }
    
    // ENHANCED: Step 2 initialization with improved shipping section
    function initStep2() {
        debug('Initializing enhanced step 2');
        
        // Initialize floating labels
        initFloatingLabels();
        
        // Form validation
        setupFormValidation();
        
        // ENHANCED: Load delivery options with date picker
        loadEnhancedDeliveryOptions();
        
        // ENHANCED: Load shipping methods with proper state management
        loadEnhancedShippingMethods();
        
        // Country change handler for dynamic shipping updates
        setupCountryChangeHandler();
        
        // Restore pricing state
        setTimeout(restorePricingState, 200);
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
        
        // Restore pricing state
        setTimeout(restorePricingState, 200);
    }
    
    // Load enhanced addons
    function loadEnhancedAddons() {
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
                    renderEnhancedAddons(response.data);
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
    
    // Render enhanced addons
    function renderEnhancedAddons(addons) {
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
                        <button class="wcflow-addon-more-info" data-addon-id="${addon.id}" data-description="${escapeHtml(addon.description)}">
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
        
        // Handle addon selection
        $(document).off('click', '.wcflow-addon-action').on('click', '.wcflow-addon-action', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.wcflow-addon-card');
            const $button = $(this);
            
            if ($card.hasClass('selected')) {
                $card.removeClass('selected');
                $button.removeClass('remove-btn').addClass('add-btn').text('Add');
            } else {
                $card.addClass('selected');
                $button.removeClass('add-btn').addClass('remove-btn').text('Remove');
            }
            
            updateOrderState();
            updatePricing();
        });
        
        // Handle "More information" popup
        $(document).off('click', '.wcflow-addon-more-info').on('click', '.wcflow-addon-more-info', function(e) {
            e.stopPropagation();
            const addonId = $(this).data('addon-id');
            const description = $(this).data('description');
            const addon = addons.find(a => a.id == addonId);
            
            if (addon) {
                showAddonInfoPopup(addon, description);
            }
        });
        
        debug('Enhanced addons rendered', {count: addons.length});
    }
    
    // Helper functions
    function truncateDescription(description) {
        if (!description) return 'No description available';
        const words = description.split(' ');
        if (words.length > 15) {
            return words.slice(0, 15).join(' ') + '...';
        }
        return description;
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showAddonInfoPopup(addon, fullDescription) {
        const popupHtml = `
            <div class="wcflow-addon-popup">
                <div class="wcflow-addon-popup-content">
                    <button class="wcflow-addon-popup-close">&times;</button>
                    <h3 class="wcflow-addon-popup-title">${addon.title}</h3>
                    <p class="wcflow-addon-popup-price">${addon.price}</p>
                    <div class="wcflow-addon-popup-description">${fullDescription || addon.description}</div>
                </div>
            </div>
        `;
        
        $('body').append(popupHtml);
        
        $(document).off('click', '.wcflow-addon-popup-close, .wcflow-addon-popup').on('click', '.wcflow-addon-popup-close, .wcflow-addon-popup', function(e) {
            if (e.target === this) {
                $('.wcflow-addon-popup').remove();
            }
        });
        
        $(document).off('click', '.wcflow-addon-popup-content').on('click', '.wcflow-addon-popup-content', function(e) {
            e.stopPropagation();
        });
    }
    
    // Load cards with enhanced selection
    function loadCardsWithEnhancedSelection() {
        debug('Loading cards with enhanced selection');
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cards',
                nonce: wcflow_params.nonce
            },
            timeout: 15000,
            success: function(response) {
                if (response && response.success && response.data) {
                    renderCardsWithEnhancedSelection(response.data);
                } else {
                    renderFallbackCards();
                }
            },
            error: function() {
                renderFallbackCards();
            }
        });
    }
    
    function renderCardsWithEnhancedSelection(cardsByCategory) {
        const $container = $('#wcflow-dynamic-cards-container');
        $container.empty();
        
        if (!cardsByCategory || Object.keys(cardsByCategory).length === 0) {
            $container.html('<p style="text-align:center;color:#666;padding:40px;">No cards available at this time.</p>');
            return;
        }
        
        Object.entries(cardsByCategory).forEach(function([categoryName, cards]) {
            if (!cards || cards.length === 0) return;
            
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
        
        setTimeout(function() {
            setupEnhancedCardSelection();
            initializeAllCategorySliders();
        }, 100);
    }
    
    function setupEnhancedCardSelection() {
        $(document).off('click', '.greeting-card');
        
        $(document).on('click', '.greeting-card', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $clickedCard = $(this);
            
            if ($clickedCard.hasClass('selected')) {
                $clickedCard.removeClass('selected');
                $('#wcflow-card-message').prop('disabled', true).val('');
                $('.wcflow-message-note').show();
                $('#wcflow-message-count').text('0');
                orderState.card_id = null;
                orderState.card_message = '';
            } else {
                $('.greeting-card').removeClass('selected');
                $clickedCard.addClass('selected');
                $('#wcflow-card-message').prop('disabled', false);
                $('.wcflow-message-note').hide();
                orderState.card_id = $clickedCard.data('card-id');
            }
            
            updateOrderState();
            updatePricing();
        });
    }
    
    function renderFallbackCards() {
        const fallbackCards = {
            'Birthday Cards': [
                {
                    id: 'fallback-1',
                    title: 'Happy Birthday Balloons',
                    price: 'FREE',
                    price_value: 0,
                    img: 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
                }
            ]
        };
        
        renderCardsWithEnhancedSelection(fallbackCards);
    }
    
    function getCategoryDescription(categoryName) {
        const descriptions = {
            'Birthday Cards': 'Perfect cards for birthday celebrations and special moments',
            'Holiday Cards': 'Festive cards for special occasions and celebrations',
            'Thank You Cards': 'Express your gratitude with these beautiful cards'
        };
        
        return descriptions[categoryName] || 'Beautiful greeting cards for every occasion';
    }
    
    function initializeAllCategorySliders() {
        $('.greeting-cards-section').each(function() {
            if (window.GreetingCardsSlider) {
                new window.GreetingCardsSlider(this);
            }
        });
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
        $('.wcflow-form-group input, .wcflow-form-group select').on('blur', function() {
            validateField($(this));
        });
        
        window.wcflowValidateStep2 = function() {
            let isValid = true;
            
            $('.wcflow-form-group').removeClass('has-error');
            $('.wcflow-field-error').text('');
            
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
            
            updateOrderStateFromForm();
            return isValid;
        };
    }
    
    function validateField($field) {
        const $group = $field.closest('.wcflow-form-group');
        const $error = $group.find('.wcflow-field-error');
        const value = $field.val().trim();
        
        if ($field.prop('required') && value === '') {
            $group.addClass('has-error');
            $error.text('This field is required');
            return false;
        }
        
        if ($field.attr('type') === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                $group.addClass('has-error');
                $error.text('Please enter a valid email address');
                return false;
            }
        }
        
        $group.removeClass('has-error');
        $error.text('');
        return true;
    }
    
    // ENHANCED: Load delivery options with improved date picker
    function loadEnhancedDeliveryOptions() {
        debug('Loading enhanced delivery options');
        
        // Setup enhanced date picker
        $(document).on('click', '#wcflow-delivery-date-selector', function() {
            showEnhancedDatePicker();
        });
    }
    
    // ENHANCED: Show date picker with Lithuanian formatting
    function showEnhancedDatePicker() {
        debug('Showing enhanced date picker');
        
        const today = new Date();
        const minDate = new Date(today.getTime() + (2 * 24 * 60 * 60 * 1000)); // 2 days from now
        
        const dateOptions = [];
        const lithuanianDays = ['sekmadienis', 'pirmadienis', 'antradienis', 'trečiadienis', 'ketvirtadienis', 'penktadienis', 'šeštadienis'];
        const lithuanianMonths = ['sausio', 'vasario', 'kovo', 'balandžio', 'gegužės', 'birželio', 'liepos', 'rugpjūčio', 'rugsėjo', 'spalio', 'lapkričio', 'gruodžio'];
        
        for (let i = 0; i < 14; i++) {
            const date = new Date(minDate.getTime() + (i * 24 * 60 * 60 * 1000));
            const dayOfWeek = date.getDay();
            
            // Skip weekends
            if (dayOfWeek === 0 || dayOfWeek === 6) continue;
            
            const dayName = lithuanianDays[dayOfWeek];
            const monthName = lithuanianMonths[date.getMonth()];
            const dayNumber = date.getDate();
            
            // Format: "Pirmadienis, birželio 23 d."
            const formattedDate = `${dayName.charAt(0).toUpperCase() + dayName.slice(1)}, ${monthName} ${dayNumber} d.`;
            
            dateOptions.push({
                value: date.toISOString().split('T')[0],
                label: formattedDate,
                date: date
            });
        }
        
        let html = '<div class="wcflow-popup-content wcflow-calendar-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Pasirinkite pristatymo datą</h3>';
        html += '<div class="wcflow-date-grid">';
        
        dateOptions.forEach(function(option) {
            html += `<div class="wcflow-date-option" data-date="${option.value}" style="padding:16px;border:2px solid #e0e0e0;margin:8px 0;cursor:pointer;border-radius:8px;transition:all 0.3s ease;background:#fff;">
                <div style="font-weight:600;color:#333;margin-bottom:4px;">${option.label}</div>
                <div style="font-size:14px;color:#666;">Darbo diena</div>
            </div>`;
        });
        
        html += '</div></div>';
        
        showPopup(html);
        
        // Enhanced date selection with visual feedback
        $(document).on('click', '.wcflow-date-option', function() {
            const date = $(this).data('date');
            const label = $(this).find('div:first').text();
            
            // Update selector display
            $('#wcflow-delivery-date-selector .selectable-box-value').text(label);
            orderState.delivery_date = date;
            
            // Visual feedback
            $(this).css({
                'border-color': '#007cba',
                'background': '#f0f8ff'
            });
            
            setTimeout(closePopup, 300);
            
            debug('Delivery date selected', {date: date, label: label});
        });
        
        // Hover effects
        $(document).on('mouseenter', '.wcflow-date-option', function() {
            $(this).css({
                'border-color': '#007cba',
                'background': '#f8f9fa'
            });
        }).on('mouseleave', '.wcflow-date-option', function() {
            $(this).css({
                'border-color': '#e0e0e0',
                'background': '#fff'
            });
        });
    }
    
    // ENHANCED: Load shipping methods with state management and caching
    function loadEnhancedShippingMethods() {
        const $selector = $('#wcflow-shipping-method-selector');
        const $valueSpan = $selector.find('.selectable-box-value');
        
        debug('Loading enhanced shipping methods');
        
        // Show loading state with better UX
        $valueSpan.html('<span style="color:#666;">Kraunami pristatymo būdai...</span>');
        
        // Add loading indicator
        $selector.addClass('loading');
        
        const loadingTimeout = setTimeout(function() {
            $valueSpan.html('<span style="color:#ff9800;">Užtrunka ilgiau nei tikėtasi...</span>');
        }, 8000);
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_shipping_methods',
                nonce: wcflow_params.nonce,
                country: orderState.shipping_country || 'LT'
            },
            timeout: 15000,
            success: function(response) {
                clearTimeout(loadingTimeout);
                $selector.removeClass('loading');
                
                debug('Enhanced shipping methods response', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    const firstMethod = response.data[0];
                    const currencySymbol = wcflow_params.currency_symbol || '€';
                    
                    // Update display
                    $valueSpan.html(`<span style="color:#333;">${firstMethod.label} - ${currencySymbol}${firstMethod.cost_with_tax}</span>`);
                    
                    // Update state
                    orderState.shipping_method = firstMethod.id;
                    orderState.shipping_cost = parseFloat(firstMethod.cost_with_tax);
                    priceState.shippingCost = parseFloat(firstMethod.cost_with_tax);
                    
                    // Update pricing immediately
                    updatePricing();
                    
                    // Setup click handler
                    $selector.off('click').on('click', function() {
                        showEnhancedShippingMethodsPopup(response.data);
                    });
                    
                    debug('Enhanced shipping methods loaded', {
                        count: response.data.length,
                        selected: firstMethod
                    });
                } else {
                    $valueSpan.html('<span style="color:#dc3545;">Pristatymo būdai neprieinami</span>');
                    debug('No shipping methods available');
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(loadingTimeout);
                $selector.removeClass('loading');
                $valueSpan.html('<span style="color:#dc3545;">Nepavyko įkelti pristatymo būdų</span>');
                debug('Enhanced shipping methods loading failed', {status: status, error: error});
            }
        });
    }
    
    // ENHANCED: Show shipping methods popup with improved design
    function showEnhancedShippingMethodsPopup(methods) {
        let html = '<div class="wcflow-popup-content wcflow-shipping-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Pasirinkite pristatymo būdą</h3>';
        html += '<div class="wcflow-shipping-methods-grid">';
        
        const currencySymbol = wcflow_params.currency_symbol || '€';
        
        methods.forEach(function(method) {
            const isSelected = orderState.shipping_method === method.id;
            html += `<div class="wcflow-shipping-option ${isSelected ? 'selected' : ''}" 
                          data-method="${method.id}" 
                          data-cost="${method.cost_with_tax}" 
                          data-label="${method.label}"
                          style="padding:20px;border:2px solid ${isSelected ? '#007cba' : '#e0e0e0'};margin:12px 0;cursor:pointer;border-radius:12px;background:${isSelected ? '#f0f8ff' : '#fff'};transition:all 0.3s ease;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div style="flex:1;">
                        <div style="font-size:18px;font-weight:600;color:#333;margin-bottom:4px;">${method.label}</div>
                        <div style="font-size:14px;color:#666;">Pristatymo kaina</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:24px;font-weight:700;color:#007cba;">${currencySymbol}${method.cost_with_tax}</div>
                        ${isSelected ? '<div style="font-size:12px;color:#007cba;margin-top:4px;">✓ Pasirinkta</div>' : ''}
                    </div>
                </div>
            </div>`;
        });
        
        html += '</div></div>';
        
        showPopup(html);
        
        // Enhanced method selection with visual feedback
        $(document).on('click', '.wcflow-shipping-option', function() {
            const methodId = $(this).data('method');
            const methodCost = parseFloat($(this).data('cost'));
            const methodLabel = $(this).data('label');
            
            // Visual feedback
            $('.wcflow-shipping-option').removeClass('selected').css({
                'border-color': '#e0e0e0',
                'background': '#fff'
            });
            
            $(this).addClass('selected').css({
                'border-color': '#007cba',
                'background': '#f0f8ff'
            });
            
            // Update main selector display
            $('#wcflow-shipping-method-selector .selectable-box-value').html(`<span style="color:#333;">${methodLabel} - ${currencySymbol}${methodCost.toFixed(2)}</span>`);
            
            // Update state
            orderState.shipping_method = methodId;
            orderState.shipping_cost = methodCost;
            priceState.shippingCost = methodCost;
            
            // Update pricing
            updatePricing();
            
            debug('Enhanced shipping method selected', {
                id: methodId,
                label: methodLabel,
                cost: methodCost
            });
            
            setTimeout(closePopup, 300);
        });
        
        // Hover effects
        $(document).on('mouseenter', '.wcflow-shipping-option:not(.selected)', function() {
            $(this).css({
                'border-color': '#007cba',
                'background': '#f8f9fa'
            });
        }).on('mouseleave', '.wcflow-shipping-option:not(.selected)', function() {
            $(this).css({
                'border-color': '#e0e0e0',
                'background': '#fff'
            });
        });
    }
    
    // ENHANCED: Setup country change handler for dynamic shipping updates
    function setupCountryChangeHandler() {
        $(document).on('change', '#wcflow-shipping-country', function() {
            const selectedCountry = $(this).val();
            
            if (selectedCountry) {
                debug('Country changed, updating shipping methods', selectedCountry);
                
                // Update order state
                orderState.shipping_country = selectedCountry;
                
                // Show loading state for shipping methods
                const $selector = $('#wcflow-shipping-method-selector');
                const $valueSpan = $selector.find('.selectable-box-value');
                $valueSpan.html('<span style="color:#666;">Atnaujinami pristatymo būdai...</span>');
                
                // Reload shipping methods for new country
                setTimeout(function() {
                    loadEnhancedShippingMethods();
                }, 500);
            }
        });
    }
    
    // Show popup
    function showPopup(html) {
        if (!$('#wcflow-popup-overlay').length) {
            $('body').append('<div id="wcflow-popup-overlay" class="wcflow-popup-overlay"></div>');
        }
        $('#wcflow-popup-overlay').html(html).show();
        
        $(document).on('click', '.wcflow-popup-close, #wcflow-popup-overlay', function(e) {
            if (e.target === this) {
                closePopup();
            }
        });
    }
    
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
                    
                    setTimeout(function() {
                        $(document.body).trigger('init_checkout');
                        $(document.body).trigger('update_checkout');
                        
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
        
        $(document).on('input change', '#wcflow-billing-form input, #wcflow-billing-form select', function() {
            const key = $(this).data('wcflow-billing');
            if (key) {
                orderState[key] = $(this).val();
            }
        });
    }
    
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
    
    function updateOrderStateFromForm() {
        orderState.customer_email = $('#wcflow-customer-email').val();
        orderState.shipping_first_name = $('#wcflow-shipping-first-name').val();
        orderState.shipping_last_name = $('#wcflow-shipping-last-name').val();
        orderState.shipping_address_1 = $('#wcflow-shipping-address-1').val();
        orderState.shipping_city = $('#wcflow-shipping-city').val();
        orderState.shipping_postcode = $('#wcflow-shipping-postcode').val();
        orderState.shipping_country = $('#wcflow-shipping-country').val();
        orderState.shipping_phone = $('#wcflow-shipping-phone').val();
        
        if (!orderState.billing_email) {
            orderState.billing_email = orderState.customer_email;
        }
        
        debug('Order state updated from form', orderState);
    }
    
    function updateOrderState() {
        const selectedAddons = [];
        $('.wcflow-addon-card.selected').each(function() {
            selectedAddons.push($(this).data('addon-id'));
        });
        orderState.addons = selectedAddons;
        
        const $selectedCard = $('.greeting-card.selected');
        if ($selectedCard.length) {
            orderState.card_id = $selectedCard.data('card-id');
            orderState.card_message = $('#wcflow-card-message').val();
        } else {
            orderState.card_id = null;
            orderState.card_message = '';
        }
        
        const selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
        if (selectedPaymentMethod) {
            orderState.payment_method = selectedPaymentMethod;
        }
        
        debug('Order state updated', orderState);
    }
    
    // Navigation handlers
    $(document).on('click', '.wcflow-btn-next:not(#wcflow-place-order-btn)', function() {
        const step = $(this).closest('.wcflow-modal').data('step');
        
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
                    wcflow_params.base_product_price = response.data.product_price || 0;
                    priceState.basePrice = response.data.product_price || 0;
                    priceState.shippingCost = response.data.shipping_cost || 0;
                    orderState.base_price = response.data.product_price || 0;
                    orderState.shipping_cost = response.data.shipping_cost || 0;
                    
                    debug('Flow started with persistent pricing', priceState);
                    
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
        
        updateOrderState();
        updateOrderStateFromForm();
        
        if ($('#wcflow-buyer-same').is(':checked')) {
            copyShippingToBilling();
        }
        
        const selectedMethod = $('input[name="payment_method"]:checked').val();
        if (selectedMethod) {
            orderState.payment_method = selectedMethod;
        }
        
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
        
        if (!orderState.customer_email && !orderState.billing_email) {
            missingFields.push('email address');
        }
        
        if (!orderState.payment_method) {
            missingFields.push('payment method');
        }
        
        if (missingFields.length > 0) {
            $('#wcflow-payment-error').html('Required fields missing: ' + missingFields.join(', ')).show();
            return;
        }
        
        $button.prop('disabled', true).text('Processing...');
        $('#wcflow-payment-error').hide();
        
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
    
    debug('Enhanced WCFlow JavaScript initialized with persistent pricing and enhanced shipping');
});