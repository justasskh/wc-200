/**
 * WooCommerce Gifting Flow - COMPLETE FIXED VERSION
 * 2025-01-27 - All features working with proper data capture and order creation
 */

jQuery(function($) {
    'use strict';
    
    // Global state management
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};
    
    let orderState = window.wcflow.orderState;
    let currentStep = 1;
    
    // Debug helper
    function debug(message, data) {
        if (wcflow_params && wcflow_params.debug) {
            console.log('[WCFlow]', message, data || '');
        }
    }
    
    // CRITICAL: Save state to sessionStorage
    function saveOrderState() {
        try {
            sessionStorage.setItem('wcflow_order_state', JSON.stringify(orderState));
            debug('✅ Order state saved:', orderState);
        } catch (e) {
            console.warn('Failed to save order state');
        }
    }
    
    // Load state from sessionStorage
    function loadOrderState() {
        try {
            const saved = sessionStorage.getItem('wcflow_order_state');
            if (saved) {
                orderState = JSON.parse(saved);
                window.wcflow.orderState = orderState;
                debug('✅ Order state loaded:', orderState);
            }
        } catch (e) {
            console.warn('Failed to load order state');
        }
    }
    
    // Initialize
    $(document).ready(function() {
        loadOrderState();
        debug('WCFlow initialized');
    });
    
    // Modal management
    function showModal(html) {
        if (!$('.wcflow-modal-container').length) {
            $('body').append('<div class="wcflow-modal-container"></div>');
        }
        
        const $container = $('.wcflow-modal-container');
        $container.html(html).addClass('visible');
        $('body').addClass('wcflow-modal-open');
    }
    
    function closeModal() {
        $('.wcflow-modal-container').removeClass('visible').empty();
        $('body').removeClass('wcflow-modal-open');
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
            success: function(response) {
                if (response.success) {
                    showModal(response.data.html);
                    initializeStep(step);
                } else {
                    alert('Could not load step. Please try again.');
                }
            },
            error: function() {
                alert('Network error. Please try again.');
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
        loadAddons();
        loadCardsWithSlider();
        
        // Message textarea
        $(document).on('input', '#wcflow-card-message', function() {
            const message = $(this).val();
            orderState.card_message = message;
            saveOrderState();
            $('#wcflow-message-count').text(message.length);
        });
        
        updatePricing();
    }
    
    // Step 2 initialization - COMPLETELY REWRITTEN
    function initStep2() {
        debug('🔧 Initializing Step 2 with PROPER data capture');
        
        // Initialize floating labels
        initFloatingLabels();
        
        // CRITICAL: Setup IMMEDIATE form data capture
        setupRealTimeFormCapture();
        
        // Setup validation
        setupFormValidation();
        
        // Load delivery and shipping options
        loadDeliveryOptions();
        loadShippingMethodsForStep2();
        
        // Update pricing
        updatePricingForStep2();
        
        // Pre-fill any existing data
        prefillFormData();
    }
    
    // CRITICAL: Real-time form data capture
    function setupRealTimeFormCapture() {
        debug('🎯 Setting up REAL-TIME form data capture');
        
        // Field mapping - EXACT IDs from template
        const fieldMap = {
            'wcflow-customer-email': 'customer_email',
            'wcflow-shipping-first-name': 'shipping_first_name', 
            'wcflow-shipping-last-name': 'shipping_last_name',
            'wcflow-shipping-phone': 'shipping_phone',
            'wcflow-shipping-address-1': 'shipping_address_1',
            'wcflow-shipping-city': 'shipping_city',
            'wcflow-shipping-postcode': 'shipping_postcode',
            'wcflow-shipping-country': 'shipping_country'
        };
        
        // Capture data on EVERY input event
        Object.entries(fieldMap).forEach(([fieldId, stateKey]) => {
            $(document).on('input change keyup blur', `#${fieldId}`, function() {
                const value = $(this).val().trim();
                orderState[stateKey] = value;
                saveOrderState();
                
                debug(`📝 CAPTURED: ${stateKey} = "${value}"`);
                
                // Clear validation error when user types
                $(this).closest('.wcflow-form-group').removeClass('error');
                $(this).closest('.wcflow-form-group').find('.wcflow-field-error').text('');
            });
        });
        
        // Also capture billing email as customer email
        $(document).on('input change keyup blur', '#wcflow-customer-email', function() {
            const email = $(this).val().trim();
            orderState.customer_email = email;
            orderState.billing_email = email;
            saveOrderState();
            debug(`📧 EMAIL CAPTURED: ${email}`);
        });
    }
    
    // Pre-fill form data if it exists
    function prefillFormData() {
        debug('🔄 Pre-filling form data');
        
        const fieldMap = {
            'wcflow-customer-email': 'customer_email',
            'wcflow-shipping-first-name': 'shipping_first_name',
            'wcflow-shipping-last-name': 'shipping_last_name', 
            'wcflow-shipping-phone': 'shipping_phone',
            'wcflow-shipping-address-1': 'shipping_address_1',
            'wcflow-shipping-city': 'shipping_city',
            'wcflow-shipping-postcode': 'shipping_postcode',
            'wcflow-shipping-country': 'shipping_country'
        };
        
        Object.entries(fieldMap).forEach(([fieldId, stateKey]) => {
            if (orderState[stateKey]) {
                $(`#${fieldId}`).val(orderState[stateKey]).trigger('change');
                debug(`✅ Pre-filled ${fieldId} with: ${orderState[stateKey]}`);
            }
        });
    }
    
    // Form validation setup
    function setupFormValidation() {
        debug('🛡️ Setting up form validation');
        
        // Validate on blur
        $('.wcflow-form-group input, .wcflow-form-group select').on('blur', function() {
            validateField($(this));
        });
        
        // CRITICAL: Intercept next button click for validation
        $(document).on('click', '.wcflow-btn-next', function(e) {
            const $modal = $(this).closest('.wcflow-modal');
            const step = parseInt($modal.data('step'));
            
            if (step === 2) {
                debug('🔍 VALIDATING STEP 2 BEFORE PROCEEDING');
                
                if (!validateStep2Complete()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                
                debug('✅ Step 2 validation PASSED - proceeding to Step 3');
            }
        });
    }
    
    // Validate individual field
    function validateField($input) {
        const $group = $input.closest('.wcflow-form-group');
        const $error = $group.find('.wcflow-field-error');
        const value = $input.val().trim();
        
        $group.removeClass('error');
        $error.text('');
        
        let isValid = true;
        let errorMessage = '';
        
        // Required field check
        if ($input.attr('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Email validation
        if ($input.attr('type') === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        
        // Phone validation
        if ($input.attr('type') === 'tel' && value) {
            const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
        }
        
        if (!isValid) {
            $group.addClass('error');
            $error.text(errorMessage);
        }
        
        return isValid;
    }
    
    // CRITICAL: Complete Step 2 validation
    function validateStep2Complete() {
        debug('🔍 COMPLETE Step 2 validation starting...');
        
        let isValid = true;
        const errors = [];
        
        // 1. Check ALL required form fields
        const requiredFields = {
            'wcflow-customer-email': 'Customer email',
            'wcflow-shipping-first-name': 'First name',
            'wcflow-shipping-last-name': 'Last name',
            'wcflow-shipping-phone': 'Phone number',
            'wcflow-shipping-address-1': 'Address',
            'wcflow-shipping-city': 'City',
            'wcflow-shipping-postcode': 'Postal code',
            'wcflow-shipping-country': 'Country'
        };
        
        // CRITICAL: Force capture all form data before validation
        Object.entries(requiredFields).forEach(([fieldId, label]) => {
            const $field = $(`#${fieldId}`);
            const value = $field.val() ? $field.val().trim() : '';
            
            // FORCE capture the data into orderState
            const stateKey = fieldId.replace('wcflow-', '').replace('-', '_');
            orderState[stateKey] = value;
            
            if (!value) {
                isValid = false;
                errors.push(label);
                
                const $group = $field.closest('.wcflow-form-group');
                $group.addClass('error');
                $group.find('.wcflow-field-error').text('This field is required');
                
                debug(`❌ MISSING: ${label} (${fieldId})`);
            } else {
                debug(`✅ VALID: ${label} = "${value}" -> ${stateKey}`);
            }
        });
        
        // CRITICAL: Ensure billing email is set
        if (orderState.customer_email) {
            orderState.billing_email = orderState.customer_email;
        }
        
        // 2. Check delivery date
        if (!orderState.delivery_date || !orderState.delivery_date_formatted) {
            isValid = false;
            errors.push('Delivery date');
            $('#wcflow-delivery-date-selector').addClass('error');
            debug('❌ MISSING: Delivery date');
        } else {
            debug('✅ VALID: Delivery date = ' + orderState.delivery_date_formatted);
        }
        
        // 3. Check shipping method
        if (!orderState.shipping_method || !orderState.shipping_method_name) {
            isValid = false;
            errors.push('Shipping method');
            $('#wcflow-shipping-method-selector').addClass('error');
            debug('❌ MISSING: Shipping method');
        } else {
            debug('✅ VALID: Shipping method = ' + orderState.shipping_method_name);
        }
        
        // CRITICAL: Save the updated state
        saveOrderState();
        
        if (!isValid) {
            debug('❌ VALIDATION FAILED - Missing fields:', errors);
            showValidationError(errors);
            scrollToFirstError();
            return false;
        }
        
        debug('✅ ALL VALIDATION PASSED');
        debug('📋 Final order state:', orderState);
        return true;
    }
    
    // Show validation error
    function showValidationError(errors) {
        const errorHtml = `
            <div class="wcflow-validation-summary">
                <div class="wcflow-validation-icon">⚠️</div>
                <div class="wcflow-validation-content">
                    <h4>Please complete all required fields:</h4>
                    <ul>${errors.map(error => `<li>${error}</li>`).join('')}</ul>
                </div>
                <button class="wcflow-validation-close">&times;</button>
            </div>
        `;
        
        $('.wcflow-validation-summary').remove();
        $('.wcflow-form-wrapper').prepend(errorHtml);
        
        $('.wcflow-validation-close').on('click', function() {
            $('.wcflow-validation-summary').fadeOut();
        });
        
        setTimeout(() => $('.wcflow-validation-summary').fadeOut(), 8000);
    }
    
    // Scroll to first error
    function scrollToFirstError() {
        const $firstError = $('.wcflow-form-group.error').first();
        if ($firstError.length) {
            $firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => $firstError.find('input, select').first().focus(), 500);
        }
    }
    
    // Initialize floating labels
    function initFloatingLabels() {
        $('.floating-label input, .floating-label select').each(function() {
            const $input = $(this);
            const $parent = $input.parent('.floating-label');
            
            function updateLabel() {
                const hasValue = $input.val() !== '' && $input.val() !== null;
                $parent.toggleClass('has-value', hasValue);
            }
            
            updateLabel();
            $input.on('input change blur focus', updateLabel);
        });
    }
    
    // Load delivery options
    function loadDeliveryOptions() {
        // Set default delivery date (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        selectDeliveryDate(tomorrow.toISOString().split('T')[0]);
        
        // Setup calendar popup
        $('#wcflow-delivery-date-selector').on('click', showCalendarPopup);
    }
    
    // Calendar popup
    function showCalendarPopup() {
        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();
        
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        let calendarHTML = `
            <div id="wcflow-datepicker-popup" class="wcflow-popup-overlay visible">
                <div class="wcflow-popup-content wcflow-calendar-popup">
                    <div class="wcflow-calendar-header">
                        <h3>${monthNames[currentMonth]} ${currentYear}</h3>
                        <button class="wcflow-popup-close">&times;</button>
                    </div>
                    <div class="wcflow-calendar-grid">
                        <div class="wcflow-calendar-weekdays">
                            <div>S</div><div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div>
                        </div>
                        <div class="wcflow-calendar-days">
        `;
        
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        
        // Empty cells
        for (let i = 0; i < firstDay; i++) {
            calendarHTML += '<div class="wcflow-calendar-day disabled"></div>';
        }
        
        // Days
        for (let i = 1; i <= daysInMonth; i++) {
            const date = new Date(currentYear, currentMonth, i);
            const isDisabled = date < today;
            const classes = isDisabled ? 'wcflow-calendar-day disabled' : 'wcflow-calendar-day';
            
            calendarHTML += `<div class="${classes}" data-date="${date.toISOString().split('T')[0]}">${i}</div>`;
        }
        
        calendarHTML += `
                        </div>
                    </div>
                    <div class="wcflow-calendar-footer">
                        <button class="wcflow-btn wcflow-btn-primary wcflow-calendar-confirm">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(calendarHTML);
        
        // Day selection
        $('.wcflow-calendar-day:not(.disabled)').on('click', function() {
            $('.wcflow-calendar-day').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // Confirm selection
        $('.wcflow-calendar-confirm').on('click', function() {
            const selectedDate = $('.wcflow-calendar-day.selected').data('date');
            if (selectedDate) {
                selectDeliveryDate(selectedDate);
                $('#wcflow-datepicker-popup').remove();
            }
        });
        
        // Close popup
        $('.wcflow-popup-close, #wcflow-datepicker-popup').on('click', function(e) {
            if (e.target === this) {
                $('#wcflow-datepicker-popup').remove();
            }
        });
    }
    
    // Select delivery date
    function selectDeliveryDate(dateString) {
        const date = new Date(dateString);
        const options = { weekday: 'long', month: 'long', day: 'numeric' };
        const formattedDate = date.toLocaleDateString('en-US', options);
        
        $('#wcflow-delivery-date-selector .selectable-box-value').text(formattedDate);
        $('#wcflow-delivery-date-selector').removeClass('error');
        
        orderState.delivery_date = dateString;
        orderState.delivery_date_formatted = formattedDate;
        saveOrderState();
        
        debug('✅ Delivery date selected:', formattedDate);
        updatePricingForStep2();
    }
    
    // Load shipping methods
    function loadShippingMethodsForStep2() {
        const country = $('#wcflow-shipping-country').val() || 'LT';
        
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_shipping_methods',
                nonce: wcflow_params.nonce,
                country: country
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    orderState.shipping_methods = response.data;
                    
                    // Select first method by default
                    const defaultMethod = response.data[0];
                    selectShippingMethod(defaultMethod.id, defaultMethod.name, defaultMethod.price);
                    
                    // Setup shipping popup
                    $('#wcflow-shipping-method-selector').on('click', showShippingMethodPopup);
                } else {
                    console.error('No shipping methods available');
                }
            },
            error: function() {
                console.error('Failed to load shipping methods');
            }
        });
    }
    
    // Show shipping method popup
    function showShippingMethodPopup() {
        if (!orderState.shipping_methods || orderState.shipping_methods.length === 0) {
            alert('No shipping methods available');
            return;
        }
        
        let popupHTML = `
            <div id="wcflow-shipping-popup" class="wcflow-popup-overlay visible">
                <div class="wcflow-popup-content wcflow-shipping-popup">
                    <div class="wcflow-shipping-popup-title">Select Shipping Method</div>
                    <div class="wcflow-shipping-options">
        `;
        
        orderState.shipping_methods.forEach(method => {
            const isSelected = orderState.shipping_method === method.id;
            popupHTML += `
                <div class="wcflow-shipping-option ${isSelected ? 'selected' : ''}" data-method-id="${method.id}" data-price="${method.price}">
                    <div class="wcflow-shipping-option-info">
                        <div class="wcflow-shipping-option-name">${method.name}</div>
                        <div class="wcflow-shipping-option-description">${method.description}</div>
                    </div>
                    <div class="wcflow-shipping-option-price">${method.price_formatted}</div>
                </div>
            `;
        });
        
        popupHTML += `
                    </div>
                    <div class="wcflow-shipping-popup-footer">
                        <button class="wcflow-btn wcflow-btn-primary wcflow-shipping-confirm">Confirm</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(popupHTML);
        
        // Method selection
        $('.wcflow-shipping-option').on('click', function() {
            $('.wcflow-shipping-option').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // Confirm selection
        $('.wcflow-shipping-confirm').on('click', function() {
            const $selected = $('.wcflow-shipping-option.selected');
            if ($selected.length) {
                const methodId = $selected.data('method-id');
                const price = parseFloat($selected.data('price'));
                const name = $selected.find('.wcflow-shipping-option-name').text();
                
                selectShippingMethod(methodId, name, price);
                $('#wcflow-shipping-popup').remove();
            }
        });
        
        // Close popup
        $('#wcflow-shipping-popup').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }
    
    // Select shipping method
    function selectShippingMethod(methodId, name, price) {
        $('#wcflow-shipping-method-selector .selectable-box-value').text(name);
        $('#wcflow-shipping-method-selector').removeClass('error');
        
        orderState.shipping_method = methodId;
        orderState.shipping_method_name = name;
        orderState.shipping_cost = price;
        saveOrderState();
        
        debug('✅ Shipping method selected:', name);
        updatePricingForStep2();
    }
    
    // Update pricing for Step 2
    function updatePricingForStep2() {
        const basePrice = parseFloat(orderState.base_price || wcflow_params.base_product_price || 0);
        const addonsTotal = parseFloat(orderState.addons_total || 0);
        const cardPrice = parseFloat(orderState.card_price || 0);
        const shippingPrice = parseFloat(orderState.shipping_cost || 0);
        
        const subtotal = basePrice + addonsTotal + cardPrice;
        const total = subtotal + shippingPrice;
        
        $('#wcflow-dynamic-total').text('€' + total.toFixed(2));
        
        if (shippingPrice > 0) {
            $('#wcflow-shipping-details').text('Including €' + shippingPrice.toFixed(2) + ' shipping');
        } else {
            $('#wcflow-shipping-details').text('Including free shipping');
        }
        
        orderState.subtotal = subtotal;
        orderState.total = total;
        saveOrderState();
    }
    
    // Step 3 initialization
    function initStep3() {
        debug('Initializing step 3');
        loadCartSummaryForStep3();
        loadPaymentMethodsForStep3();
        setupOrderPlacement();
    }
    
    // Load cart summary for Step 3
    function loadCartSummaryForStep3() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cart_summary',
                nonce: wcflow_params.nonce,
                order_state: JSON.stringify(orderState)
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('#wcflow-basket-summary-container').html(response.data.html);
                }
            }
        });
    }
    
    // Load payment methods
    function loadPaymentMethodsForStep3() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_checkout_form',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('#wcflow-payment-options-container').html(response.data.html);
                }
            }
        });
    }
    
    // Setup order placement
    function setupOrderPlacement() {
        $(document).on('click', '#wcflow-place-order-btn', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            // Validate payment method
            const selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
            if (!selectedPaymentMethod) {
                alert('Please select a payment method.');
                return;
            }
            
            orderState.payment_method = selectedPaymentMethod;
            saveOrderState();
            
            $btn.text('Processing...').prop('disabled', true);
            
            debug('🛒 Creating order with state:', orderState);
            
            $.ajax({
                url: wcflow_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcflow_create_order',
                    nonce: wcflow_params.nonce,
                    state: JSON.stringify(orderState)
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert('Order created successfully! Order ID: ' + response.data.order_id);
                            closeModal();
                        }
                    } else {
                        alert('Failed to create order: ' + (response.data ? response.data.message : 'Unknown error'));
                        $btn.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
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
            success: function(response) {
                if (response.success && response.data) {
                    renderAddons(response.data);
                }
            }
        });
    }
    
    // Render addons
    function renderAddons(addons) {
        const $grid = $('#wcflow-addons-grid');
        $grid.empty();
        
        addons.forEach(function(addon) {
            const $card = $(`
                <div class="wcflow-addon-card" data-addon-id="${addon.id}" data-price-value="${addon.price_value}">
                    ${addon.img ? `<img src="${addon.img}" alt="${addon.title}" class="wcflow-addon-image">` : ''}
                    <div class="wcflow-addon-content">
                        <h3 class="wcflow-addon-title">${addon.title}</h3>
                        <p class="wcflow-addon-price">${addon.price}</p>
                        <p class="wcflow-addon-description-short">${addon.description}</p>
                        <div class="wcflow-addon-actions">
                            <button class="wcflow-addon-action add-btn" data-addon-id="${addon.id}">Add</button>
                        </div>
                    </div>
                </div>
            `);
            $grid.append($card);
        });
        
        // Handle addon selection
        $(document).on('click', '.wcflow-addon-action', function(e) {
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
    }
    
    // Load cards with slider
    function loadCardsWithSlider() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cards',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderCardsInSlider(response.data);
                }
            }
        });
    }
    
    // Render cards in slider
    function renderCardsInSlider(cardsByCategory) {
        const $slider = $('#wcflow-cards-slider');
        $slider.empty();
        
        let allCards = [];
        Object.values(cardsByCategory).forEach(cards => {
            allCards = allCards.concat(cards);
        });
        
        allCards.forEach(function(card) {
            const $cardItem = $(`
                <div class="greeting-card" data-card-id="${card.id}" data-price-value="${card.price_value}">
                    ${card.img ? `<img src="${card.img}" alt="${card.title}" class="greeting-card-image">` : ''}
                    <div class="greeting-card-content">
                        <h4 class="greeting-card-title">${card.title}</h4>
                        <p class="greeting-card-price">${card.price}</p>
                    </div>
                </div>
            `);
            $slider.append($cardItem);
        });
        
        // Handle card selection
        $(document).on('click', '.greeting-card', function() {
            $('.greeting-card').removeClass('selected');
            $(this).addClass('selected');
            
            const cardId = $(this).data('card-id');
            const cardPrice = parseFloat($(this).data('price-value')) || 0;
            
            orderState.card_id = cardId;
            orderState.card_price = cardPrice;
            saveOrderState();
            
            updatePricing();
        });
    }
    
    // Update order state from Step 1 selections
    function updateOrderState() {
        // Get selected addons
        const selectedAddons = [];
        let addonsTotal = 0;
        
        $('.wcflow-addon-card.selected').each(function() {
            const addonId = $(this).data('addon-id');
            const addonPrice = parseFloat($(this).data('price-value')) || 0;
            
            selectedAddons.push(addonId);
            addonsTotal += addonPrice;
        });
        
        orderState.addons = selectedAddons;
        orderState.addons_total = addonsTotal;
        
        saveOrderState();
    }
    
    // Update pricing display
    function updatePricing() {
        const basePrice = parseFloat(wcflow_params.base_product_price || 0);
        const addonsTotal = parseFloat(orderState.addons_total || 0);
        const cardPrice = parseFloat(orderState.card_price || 0);
        
        const total = basePrice + addonsTotal + cardPrice;
        
        orderState.base_price = basePrice;
        orderState.subtotal = total;
        orderState.total = total;
        
        // Update display
        $('#wcflow-dynamic-total').text('€' + total.toFixed(2));
        
        saveOrderState();
    }
    
    // Global event handlers
    $(document).ready(function() {
        // Start flow button
        $(document).on('click', '.wcflow-start-btn', function(e) {
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            if (!productId) {
                alert('Product ID not found');
                return;
            }
            
            // Start the flow
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
                        orderState.base_price = response.data.product_price;
                        orderState.shipping_cost = response.data.shipping_cost;
                        saveOrderState();
                        
                        loadStep(1);
                    } else {
                        alert('Could not start flow. Please try again.');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                }
            });
        });
        
        // Modal close
        $(document).on('click', '.wcflow-modal-close', function() {
            closeModal();
        });
        
        // Step navigation
        $(document).on('click', '.wcflow-btn-next', function(e) {
            const $modal = $(this).closest('.wcflow-modal');
            const currentStep = parseInt($modal.data('step'));
            const nextStep = currentStep + 1;
            
            if (nextStep <= 3) {
                loadStep(nextStep);
            }
        });
        
        $(document).on('click', '.wcflow-btn-prev', function(e) {
            const $modal = $(this).closest('.wcflow-modal');
            const currentStep = parseInt($modal.data('step'));
            const prevStep = currentStep - 1;
            
            if (prevStep >= 1) {
                loadStep(prevStep);
            }
        });
    });

});