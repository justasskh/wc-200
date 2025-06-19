/**
 * WooCommerce Gifting Flow - Main JavaScript
 * Recreated: 2025-01-27 - Complete rebuild with proper functionality
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
        if (wcflow_params.debug) {
            console.log('[WCFlow]', message, data || '');
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
        
        // Load addons
        loadAddons();
        
        // Load cards
        loadCards();
        
        // Message textarea functionality
        $(document).on('input', '#wcflow-card-message', function() {
            const length = $(this).val().length;
            $('#wcflow-message-count').text(length);
        });
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
        loadShippingMethods();
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
            success: function(response) {
                if (response.success) {
                    renderAddons(response.data);
                }
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
                <div class="wcflow-addon-card" data-addon-id="${addon.id}">
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
        });
    }
    
    // Load cards
    function loadCards() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_cards',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderCards(response.data);
                }
            }
        });
    }
    
    // Render cards
    function renderCards(cardsByCategory) {
        const $container = $('#wcflow-all-cards-container');
        $container.empty();
        
        if (Object.keys(cardsByCategory).length === 0) {
            $container.html('<p style="text-align:center;color:#666;">No cards available.</p>');
            return;
        }
        
        Object.entries(cardsByCategory).forEach(function([category, cards]) {
            const $categoryDiv = $(`
                <div class="wcflow-cards-category">
                    <h3 class="wcflow-cards-category-title">${category}</h3>
                    <div class="wcflow-cards-grid"></div>
                </div>
            `);
            
            const $grid = $categoryDiv.find('.wcflow-cards-grid');
            
            cards.forEach(function(card) {
                const $cardItem = $(`
                    <div class="wcflow-card-item" data-card-id="${card.id}">
                        ${card.img ? `<img src="${card.img}" alt="${card.title}" class="wcflow-card-image">` : ''}
                        <div class="wcflow-card-content">
                            <h4 class="wcflow-card-title">${card.title}</h4>
                            <p class="wcflow-card-price">${card.price}</p>
                        </div>
                    </div>
                `);
                $grid.append($cardItem);
            });
            
            $container.append($categoryDiv);
        });
        
        // Handle card selection
        $(document).on('click', '.wcflow-card-item', function() {
            $('.wcflow-card-item').removeClass('selected');
            $(this).addClass('selected');
            
            // Enable message textarea
            $('#wcflow-card-message').prop('disabled', false);
            $('.wcflow-message-note').hide();
            
            updateOrderState();
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
    
    // Load shipping methods
    function loadShippingMethods() {
        $(document).on('click', '#wcflow-shipping-method-selector', function() {
            showShippingMethods();
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
    
    // Show shipping methods
    function showShippingMethods() {
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_get_shipping_methods',
                nonce: wcflow_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderShippingMethods(response.data);
                }
            }
        });
    }
    
    // Render shipping methods
    function renderShippingMethods(methods) {
        let html = '<div class="wcflow-popup-content wcflow-shipping-popup">';
        html += '<button class="wcflow-popup-close">&times;</button>';
        html += '<h3>Select Shipping Method</h3>';
        
        methods.forEach(function(method) {
            html += `<div class="wcflow-shipping-option" data-method="${method.id}" style="padding:12px;border:1px solid #ddd;margin:8px 0;cursor:pointer;border-radius:4px;">
                <strong>${method.label}</strong><br>
                <span style="color:#666;">${wcflow_params.currency_symbol}${method.cost_with_tax}</span>
            </div>`;
        });
        
        html += '</div>';
        
        showPopup(html);
        
        // Handle method selection
        $(document).on('click', '.wcflow-shipping-option', function() {
            const methodId = $(this).data('method');
            const label = $(this).find('strong').text();
            
            $('#wcflow-shipping-method-selector .selectable-box-value').text(label);
            orderState.shipping_method = methodId;
            
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
        const $selectedCard = $('.wcflow-card-item.selected');
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
    
    debug('WCFlow JavaScript initialized');
});