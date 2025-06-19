/**
 * WooCommerce Gifting Flow - SHIPPING METHODS FIX
 * 2025-01-27 - Real WooCommerce shipping methods with Lithuania default
 */

jQuery(function($) {
    'use strict';
    
    // Global state
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};
    
    let orderState = window.wcflow.orderState;
    let currentStep = 1;
    let sliderInstance = null;
    
    // Debug helper
    function debug(message, data) {
        if (wcflow_params.debug) {
            console.log('[WCFlow]', message, data || '');
        }
    }
    
    // FIXED: Enhanced price calculation with real WooCommerce shipping
    function updatePricing() {
        let basePrice = parseFloat(wcflow_params.base_product_price || 0);
        let addonsTotal = 0;
        let cardPrice = 0;
        let shippingPrice = parseFloat(orderState.shipping_cost || 0);
        
        // Calculate addons total
        $('.wcflow-addon-card.selected').each(function() {
            const priceValue = parseFloat($(this).data('price-value') || 0);
            addonsTotal += priceValue;
            debug('Addon selected', {id: $(this).data('addon-id'), price: priceValue});
        });
        
        // Calculate card price
        const $selectedCard = $('.greeting-card.selected');
        if ($selectedCard.length) {
            cardPrice = parseFloat($selectedCard.data('price-value') || 0);
            debug('Card selected', {id: $selectedCard.data('card-id'), price: cardPrice});
        }
        
        const subtotal = basePrice + addonsTotal + cardPrice;
        const total = subtotal + shippingPrice;
        
        // Update display with proper formatting
        const currencySymbol = wcflow_params.currency_symbol || '€';
        $('#wcflow-dynamic-total').text(currencySymbol + total.toFixed(2));
        
        if (shippingPrice > 0) {
            $('#wcflow-shipping-details').text('Įskaičiuotas ' + currencySymbol + shippingPrice.toFixed(2) + ' pristatymo mokestis');
        } else {
            $('#wcflow-shipping-details').text('Nemokamas pristatymas');
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
    
    // Step 1 initialization
    function initStep1() {
        debug('Initializing step 1');
        
        // Load addons first
        loadAddons();
        
        // Load cards and initialize slider
        loadCardsWithSlider();
        
        // Message textarea functionality
        $(document).on('input', '#wcflow-card-message', function() {
            const length = $(this).val().length;
            $('#wcflow-message-count').text(length);
            orderState.card_message = $(this).val();
        });
        
        // Initial pricing update
        setTimeout(updatePricing, 500);
    }
    
    // FIXED: Step 2 initialization with proper shipping methods loading
    function initStep2() {
        debug('Initializing step 2');
        
        // Initialize floating labels
        initFloatingLabels();
        
        // Form validation
        setupFormValidation();
        
        // Load delivery options
        loadDeliveryOptions();
        
        // FIXED: Load shipping methods immediately with proper error handling
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
    
    // Load addons
    function loadAddons() {
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
                    renderAddons(response.data);
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
    
    // Render addons
    function renderAddons(addons) {
        const $grid = $('#wcflow-addons-grid');
        $grid.empty();
        
        if (addons.length === 0) {
            $grid.html('<p style="text-align:center;color:#666;">No add-ons available.</p>');
            return;
        }
        
        addons.forEach(function(addon) {
            const $card = $(`
                <div class="wcflow-addon-card" data-addon-id="${addon.id}" data-price-value="${addon.price_value}">
                    ${addon.img ? `<img src="${addon.img}" alt="${addon.title}" class="wcflow-addon-image">` : ''}
                    <div class="wcflow-addon-content">
                        <h3 class="wcflow-addon-title">${addon.title}</h3>
                        <p class="wcflow-addon-price">${addon.price}</p>
                        <p class="wcflow-addon-description">${addon.description}</p>
                    </div>
                </div>
            `);
            $grid.append($card);
        });
        
        // Handle addon selection
        $(document).on('click', '.wcflow-addon-card', function() {
            $(this).toggleClass('selected');
            updateOrderState();
            updatePricing();
        });
    }
    
    // Load cards with slider
    function loadCardsWithSlider() {
        const $slider = $('#wcflow-cards-slider');
        $slider.html('<div class="wcflow-loader"></div>');
        
        debug('Starting to load cards...');
        
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
                    renderCardsInSlider(response.data);
                    setTimeout(initializeSlider, 200);
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
    
    // Render fallback cards when AJAX fails
    function renderFallbackCards() {
        debug('Rendering fallback cards');
        
        const fallbackCards = {
            'Populiariausi atvirukai': [
                {
                    id: 'fallback-1',
                    title: 'Gimtadienio apkabinimai',
                    price: 'NEMOKAMA',
                    price_value: 0,
                    img: 'https://images.pexels.com/photos/1666065/pexels-photo-1666065.jpeg?auto=compress&cs=tinysrgb&w=400'
                },
                {
                    id: 'fallback-2',
                    title: 'Birželio gimimo gėlė',
                    price: '€1.50',
                    price_value: 1.50,
                    img: 'https://images.pexels.com/photos/1040173/pexels-photo-1040173.jpeg?auto=compress&cs=tinysrgb&w=400'
                },
                {
                    id: 'fallback-3',
                    title: 'Su gimtadieniu!',
                    price: '€2.50',
                    price_value: 2.50,
                    img: 'https://images.pexels.com/photos/1729931/pexels-photo-1729931.jpeg?auto=compress&cs=tinysrgb&w=400'
                }
            ]
        };
        
        renderCardsInSlider(fallbackCards);
        setTimeout(initializeSlider, 200);
    }
    
    // Render cards in slider format
    function renderCardsInSlider(cardsByCategory) {
        const $slider = $('#wcflow-cards-slider');
        $slider.empty();
        
        debug('Rendering cards in slider', cardsByCategory);
        
        if (!cardsByCategory || Object.keys(cardsByCategory).length === 0) {
            $slider.html('<p style="text-align:center;color:#666;padding:40px;">No cards available at this time.</p>');
            return;
        }
        
        // Render cards by category priority
        const categoryOrder = ['Populiariausi atvirukai', 'Gimtadienio ir švenčių atvirukai'];
        let allCards = [];
        
        // Add cards in category order
        categoryOrder.forEach(function(category) {
            if (cardsByCategory[category]) {
                allCards = allCards.concat(cardsByCategory[category]);
            }
        });
        
        // Add any remaining categories
        Object.entries(cardsByCategory).forEach(function([category, cards]) {
            if (!categoryOrder.includes(category)) {
                allCards = allCards.concat(cards);
            }
        });
        
        if (allCards.length === 0) {
            $slider.html('<p style="text-align:center;color:#666;padding:40px;">No cards found.</p>');
            return;
        }
        
        allCards.forEach(function(card) {
            const $cardItem = $(`
                <div class="greeting-card" data-card-id="${card.id}" data-price-value="${card.price_value}" role="listitem" tabindex="0">
                    ${card.img ? `<img src="${card.img}" alt="${card.title}" class="greeting-card-image" loading="lazy">` : ''}
                    <div class="greeting-card-content">
                        <h4 class="greeting-card-title">${card.title}</h4>
                        <p class="greeting-card-price ${card.price_value == 0 ? 'free' : ''}">${card.price}</p>
                    </div>
                </div>
            `);
            $slider.append($cardItem);
        });
        
        // Handle card selection
        $(document).off('click', '.greeting-card').on('click', '.greeting-card', function() {
            $('.greeting-card').removeClass('selected');
            $(this).addClass('selected');
            
            // Enable message textarea
            $('#wcflow-card-message').prop('disabled', false);
            $('.wcflow-message-note').hide();
            
            updateOrderState();
            updatePricing();
            
            debug('Card selected', {
                id: $(this).data('card-id'),
                price: $(this).data('price-value')
            });
        });
        
        debug('Cards rendered successfully', {total: allCards.length});
    }
    
    // Initialize slider functionality
    function initializeSlider() {
        const $sliderWrapper = $('.greeting-cards-slider-wrapper');
        const $slider = $('#wcflow-cards-slider');
        const $prevBtn = $('.slider-nav-prev');
        const $nextBtn = $('.slider-nav-next');
        const $progressFill = $('.slider-progress-fill');
        
        if (!$slider.length || $slider.find('.greeting-card').length === 0) {
            debug('Slider not found or no cards available');
            return;
        }
        
        let currentIndex = 0;
        const cardWidth = 220; // 200px + 20px gap
        const containerWidth = $sliderWrapper.width();
        const visibleCards = Math.floor(containerWidth / cardWidth);
        const totalCards = $slider.find('.greeting-card').length;
        const maxIndex = Math.max(0, totalCards - visibleCards);
        
        debug('Slider initialized', {
            containerWidth: containerWidth,
            visibleCards: visibleCards,
            totalCards: totalCards,
            maxIndex: maxIndex
        });
        
        function updateSlider() {
            const translateX = -currentIndex * cardWidth;
            $slider.css('transform', `translateX(${translateX}px)`);
            
            // Update navigation
            $prevBtn.toggleClass('disabled', currentIndex === 0);
            $nextBtn.toggleClass('disabled', currentIndex >= maxIndex);
            
            // Update progress
            if (maxIndex > 0) {
                const progress = (currentIndex / maxIndex) * 100;
                $progressFill.css('width', progress + '%');
            } else {
                $progressFill.css('width', '100%');
            }
            
            debug('Slider updated', {currentIndex: currentIndex, translateX: translateX});
        }
        
        // Navigation handlers
        $prevBtn.off('click').on('click', function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateSlider();
            }
        });
        
        $nextBtn.off('click').on('click', function() {
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateSlider();
            }
        });
        
        // Initial update
        updateSlider();
        
        debug('Slider fully initialized and ready');
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
    
    // FIXED: Load shipping methods for Step 2 with comprehensive error handling
    function loadShippingMethodsForStep2() {
        const $selector = $('#wcflow-shipping-method-selector');
        const $valueSpan = $selector.find('.selectable-box-value');
        
        debug('Loading shipping methods for Step 2...');
        
        // Show loading state
        $valueSpan.text('Kraunami pristatymo būdai...');
        
        // Set timeout for loading state
        const loadingTimeout = setTimeout(function() {
            $valueSpan.text('Užtrunka ilgiau nei tikėtasi...');
        }, 8000);
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_shipping_methods',
                nonce: wcflow_params.nonce
            },
            timeout: 15000, // 15 second timeout
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
                    $valueSpan.text('Pristatymo būdai neprieinami');
                    debug('No shipping methods available in response');
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(loadingTimeout);
                $valueSpan.text('Nepavyko įkelti pristatymo būdų');
                debug('Shipping methods loading failed', {status: status, error: error});
                console.error('Shipping methods error:', xhr.responseText);
            }
        });
    }
    
    // Show shipping methods popup
    function showShippingMethodsPopup(methods) {
        let html = '<div class="wcflow-popup-content wcflow-shipping-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Pasirinkite pristatymo būdą</h3>';
        
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
                        <div style="font-size:14px;color:#666;margin-top:4px;">Pristatymo kaina</div>
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
                label: date.toLocaleDateString('lt-LT', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                })
            });
        }
        
        let html = '<div class="wcflow-popup-content wcflow-calendar-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Pasirinkite pristatymo datą</h3>';
        
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
    
    // FIXED: Start flow with proper base price and shipping calculation
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
                    // FIXED: Set base product price and real shipping cost from WooCommerce
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
    
    debug('WCFlow JavaScript initialized with real WooCommerce shipping methods');
});