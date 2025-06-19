/**
 * WooCommerce Gifting Flow - Modal Checkout JS
 * Handles multi-step modal checkout, shipping, billing, and payment logic
 * Supports dynamic payment method loading and buyer/billing logic.
 * Updated: 2025-06-19 12:05:54 UTC - Fixed data transfer and validation
 */

jQuery(function($) {
    // --- State ---
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};
    let orderState = window.wcflow.orderState;
    let $body = $('body');
    let $document = $(document);

    // --- Modal UI helpers ---
    function showModal(html) {
        if (!$('.wcflow-modal-container').length) {
            $body.append('<div class="wcflow-modal-container"></div>');
        }
        $('.wcflow-modal-container').html(html).addClass('visible');
        $body.addClass('wcflow-modal-open');
    }
    function closeModal() {
        $('.wcflow-modal-container').removeClass('visible').empty();
        $body.removeClass('wcflow-modal-open');
    }
    function closeDetailsModal() {
        $('.wcflow-details-modal').fadeOut(200);
    }
    function showStepLoading(step) {
        const modal = $('.wcflow-modal[data-step="' + step + '"]');
        if (modal.length) {
            modal.find('.wcflow-loading-overlay').show();
        }
    }
    function hideStepLoading(step) {
        const modal = $('.wcflow-modal[data-step="' + step + '"]');
        if (modal.length) {
            modal.find('.wcflow-loading-overlay').hide();
        }
    }

    function debug(msg, obj) {
        console.log('[wcflow]', msg, obj || '');
    }

    // --- Step loader ---
    function loadStep(step) {
        const loadingHtml = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;"><div class="wcflow-loader"></div></div>';
        debug(`Loading step ${step}`, orderState);
        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: { action: 'wcflow_get_step', nonce: wcflow_params.nonce, step },
            beforeSend: function () {
                if (!$('.wcflow-modal').length) {
                    showModal(loadingHtml);
                }
            }
        }).done(function (response) {
            if (response.success) {
                showModal(response.data.html);
                updateOrderTotals();
                if (step === 1) initStep1();
                if (step === 2) initStep2();
                if (step === 3) initStep3();
            } else {
                alert('Could not load next step.');
                closeModal();
            }
        }).fail(function () {
            alert('Critical error. See console for details.');
            closeModal();
        });
    }

    // --- Step 1 initialization (cart/addons, if any) ---
    function initStep1() {
        // Your original Step 1 logic here (if any)
    }

    // --- Step 2: Customer/shipping - COMPLETELY FIXED ---
    function initStep2() {
        debug('Initializing step 2', orderState);

        function initFloatingLabels() {
            $('.floating-label input, .floating-label select').each(function() {
                const $input = $(this);
                const $group = $input.closest('.floating-label');
                function updateFloatingLabel() {
                    $group.toggleClass('has-value', $input.val() || $input.is(':focus'));
                }
                $input.on('focus blur input change', updateFloatingLabel);
                updateFloatingLabel();
            });
        }
        initFloatingLabels();

        // COMPREHENSIVE field mapping and monitoring
        function syncFormToState() {
            // Map all Step 2 form fields to orderState
            const fieldMappings = {
                'wcflow-customer-email': 'customer_email',
                'wcflow-shipping-first-name': 'shipping_first_name',
                'wcflow-shipping-last-name': 'shipping_last_name',
                'wcflow-shipping-address-1': 'shipping_address_1',
                'wcflow-shipping-city': 'shipping_city',
                'wcflow-shipping-postcode': 'shipping_postcode',
                'wcflow-shipping-country': 'shipping_country',
                'wcflow-shipping-phone': 'shipping_phone'
            };

            Object.entries(fieldMappings).forEach(([fieldId, stateKey]) => {
                const $field = $('#' + fieldId);
                if ($field.length) {
                    const value = $field.val();
                    if (value !== undefined && value !== null) {
                        window.wcflow.orderState[stateKey] = value;
                        orderState[stateKey] = value;
                        debug('Synced ' + stateKey + ' = ' + value);
                    }
                }
            });

            // Ensure billing_email is synced with customer_email
            if (window.wcflow.orderState.customer_email) {
                window.wcflow.orderState.billing_email = window.wcflow.orderState.customer_email;
                orderState.billing_email = orderState.customer_email;
            }

            debug('Form synced to state:', window.wcflow.orderState);
        }

        // Set up real-time field monitoring with more specific selectors
        $('.wcflow-modal input, .wcflow-modal select').on('input change keyup blur', function() {
            setTimeout(syncFormToState, 10); // Small delay to ensure value is set
        });

        // Initial sync
        setTimeout(syncFormToState, 100);

        // IMPROVED Validation
        window.wcflowValidateStep2 = function() {
            // Force sync before validation
            syncFormToState();

            let isValid = true;
            $('.wcflow-form-group').removeClass('has-error');
            $('.wcflow-field-error').text('');
            
            const requiredFields = [
                'customer_email', 'shipping_first_name', 'shipping_last_name',
                'shipping_country', 'shipping_address_1', 'shipping_city',
                'shipping_postcode'
            ];
            
            requiredFields.forEach(field => {
                const value = window.wcflow.orderState[field];
                if (!value || value.trim() === '') {
                    // Find corresponding form field
                    const fieldMappings = {
                        'customer_email': 'wcflow-customer-email',
                        'shipping_first_name': 'wcflow-shipping-first-name',
                        'shipping_last_name': 'wcflow-shipping-last-name',
                        'shipping_address_1': 'wcflow-shipping-address-1',
                        'shipping_city': 'wcflow-shipping-city',
                        'shipping_postcode': 'wcflow-shipping-postcode',
                        'shipping_country': 'wcflow-shipping-country',
                        'shipping_phone': 'wcflow-shipping-phone'
                    };
                    
                    const fieldId = fieldMappings[field];
                    if (fieldId) {
                        const $field = $('#' + fieldId);
                        $field.closest('.wcflow-form-group').addClass('has-error')
                            .find('.wcflow-field-error').text('This field is required');
                    }
                    isValid = false;
                    debug('Missing required field:', field);
                }
            });
            
            // Email validation
            const email = window.wcflow.orderState.customer_email;
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                const $group = $('#wcflow-customer-email').closest('.wcflow-form-group');
                $group.addClass('has-error');
                $group.find('.wcflow-field-error').text('Invalid email format');
                isValid = false;
            }
            
            debug('Step 2 validation result:', isValid, window.wcflow.orderState);
            return isValid;
        };
    }

    // --- Step 3: Payment + buyer/billing - COMPLETELY FIXED ---
    function copyShippingToBilling() {
        var s = window.wcflow.orderState;
        s['billing_first_name'] = s['shipping_first_name'] || '';
        s['billing_last_name'] = s['shipping_last_name'] || '';
        s['billing_address_1'] = s['shipping_address_1'] || '';
        s['billing_city'] = s['shipping_city'] || '';
        s['billing_postcode'] = s['shipping_postcode'] || '';
        s['billing_country'] = s['shipping_country'] || '';
        s['billing_phone'] = s['shipping_phone'] || '';
        s['billing_email'] = s['customer_email'] || '';
        
        // Update hidden form fields immediately
        updateHiddenFormFields();
        
        debug('Copied shipping to billing:', s);
    }

    function initStep3() {
        debug('Initializing step 3 with orderState:', window.wcflow.orderState);
        
        // FIXED: Buyer checkbox logic with proper event handling
        $(document).off('change', '#wcflow-buyer-same').on('change', '#wcflow-buyer-same', function() {
            const isChecked = $(this).is(':checked');
            debug('Buyer same checkbox changed to:', isChecked);
            
            if (isChecked) {
                debug('Hiding billing form and copying shipping data');
                $('#wcflow-billing-form').slideUp(200);
                copyShippingToBilling();
            } else {
                debug('Showing billing form');
                $('#wcflow-billing-form').slideDown(200);
                
                // Pre-fill billing form with shipping data
                const s = window.wcflow.orderState;
                $('input[data-wcflow-billing="billing_first_name"]').val(s.shipping_first_name || '');
                $('input[data-wcflow-billing="billing_last_name"]').val(s.shipping_last_name || '');
                $('input[data-wcflow-billing="billing_address_1"]').val(s.shipping_address_1 || '');
                $('input[data-wcflow-billing="billing_city"]').val(s.shipping_city || '');
                $('input[data-wcflow-billing="billing_postcode"]').val(s.shipping_postcode || '');
                $('input[data-wcflow-billing="billing_country"]').val(s.shipping_country || '');
                $('input[data-wcflow-billing="billing_phone"]').val(s.shipping_phone || '');
                $('input[data-wcflow-billing="billing_email"]').val(s.customer_email || '');
                
                // Update orderState
                window.wcflow.orderState.billing_first_name = s.shipping_first_name || '';
                window.wcflow.orderState.billing_last_name = s.shipping_last_name || '';
                window.wcflow.orderState.billing_address_1 = s.shipping_address_1 || '';
                window.wcflow.orderState.billing_city = s.shipping_city || '';
                window.wcflow.orderState.billing_postcode = s.shipping_postcode || '';
                window.wcflow.orderState.billing_country = s.shipping_country || '';
                window.wcflow.orderState.billing_phone = s.shipping_phone || '';
                window.wcflow.orderState.billing_email = s.customer_email || '';
            }
        });

        $(document).off('input change', '#wcflow-billing-form input, #wcflow-billing-form select')
        .on('input change', '#wcflow-billing-form input, #wcflow-billing-form select', function() {
            const key = $(this).data('wcflow-billing');
            if (key) {
                const value = $(this).val();
                window.wcflow.orderState[key] = value;
                debug('Updated billing field:', key, '=', value);
                
                // Update hidden form field immediately
                const $hiddenField = $('input[name="' + key + '"]');
                if ($hiddenField.length) {
                    $hiddenField.val(value);
                }
            }
        });

        loadPaymentMethods();
    }

    // Helper to update hidden form fields 
    function updateHiddenFormFields() {
        var s = window.wcflow.orderState;
        const fieldsToUpdate = [
            'billing_first_name', 'billing_last_name', 'billing_email', 'billing_address_1',
            'billing_city', 'billing_postcode', 'billing_country', 'billing_phone',
            'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_city',
            'shipping_postcode', 'shipping_country', 'shipping_phone', 'customer_email'
        ];
        
        fieldsToUpdate.forEach(function(field) {
            var $hiddenField = $('input[name="' + field + '"]');
            if ($hiddenField.length && s[field]) {
                $hiddenField.val(s[field]);
                debug('Updated hidden field:', field, '=', s[field]);
            }
        });
    }

    // AJAX: Load payment methods
    function loadPaymentMethods() {
        var $container = $('#wcflow-payment-options-container');
        if (!$container.length) return;
        $container.html('<div class="wcflow-loader"></div>');
        
        debug('Loading payment methods...');
        
        $.post(wcflow_params.ajax_url, {
            action: 'wcflow_get_checkout_form',
            nonce: wcflow_params.nonce
        }, function(response) {
            debug('Payment methods response:', response);
            
            if (response.success && response.data && response.data.html) {
                $container.html(response.data.html);
                
                // Remove duplicate nonce fields to fix the DOM warning
                $('input[name="nonce"]').not(':first').remove();
                
                // Update hidden form fields with current orderState
                setTimeout(function() {
                    updateHiddenFormFields();
                    
                    // Trigger WooCommerce JS
                    $(document.body).trigger('init_checkout');
                    $(document.body).trigger('update_checkout');
                    $(document.body).trigger('wc-credit-card-form-init');
                    
                    // Select first payment method
                    if ($('input[name="payment_method"]:checked').length === 0) {
                        $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
                    }
                }, 100);
            } else {
                $container.html('<div style="color:red;">Failed to load payment methods.</div>');
            }
        }).fail(function(xhr, status, error) {
            debug('Payment methods load failed:', error);
            $container.html('<div style="color:red;">Failed to load payment methods.</div>');
        });
    }

    // COMPLETELY FIXED: Place order logic with enhanced data collection
    $(document).off('click', '#wcflow-place-order-btn').on('click', '#wcflow-place-order-btn', function(e) {
        e.preventDefault();
        
        debug('Place order clicked - current orderState:', window.wcflow.orderState);
        
        // FORCE complete sync of all form data from ALL steps
        const fieldMappings = {
            'wcflow-customer-email': 'customer_email',
            'wcflow-shipping-first-name': 'shipping_first_name',
            'wcflow-shipping-last-name': 'shipping_last_name',
            'wcflow-shipping-address-1': 'shipping_address_1',
            'wcflow-shipping-city': 'shipping_city',
            'wcflow-shipping-postcode': 'shipping_postcode',
            'wcflow-shipping-country': 'shipping_country',
            'wcflow-shipping-phone': 'shipping_phone'
        };

        // Sync Step 2 fields from DOM
        Object.entries(fieldMappings).forEach(([fieldId, stateKey]) => {
            const $field = $('#' + fieldId);
            if ($field.length) {
                const value = $field.val();
                if (value !== undefined && value !== null) {
                    window.wcflow.orderState[stateKey] = value;
                    debug('Final sync:', stateKey, '=', value);
                }
            }
        });
        
        // Sync billing form fields from Step 3
        $('input[data-wcflow-billing], select[data-wcflow-billing]').each(function() {
            const $field = $(this);
            const billingKey = $field.data('wcflow-billing');
            if (billingKey) {
                const value = $field.val();
                if (value !== undefined && value !== null) {
                    window.wcflow.orderState[billingKey] = value;
                    debug('Final billing sync:', billingKey, '=', value);
                }
            }
        });

        // Handle buyer same checkbox logic
        if ($('#wcflow-buyer-same').is(':checked')) {
            copyShippingToBilling();
        }

        // Ensure email fields are properly set
        if (!window.wcflow.orderState.billing_email && window.wcflow.orderState.customer_email) {
            window.wcflow.orderState.billing_email = window.wcflow.orderState.customer_email;
        }
        if (!window.wcflow.orderState.customer_email && window.wcflow.orderState.billing_email) {
            window.wcflow.orderState.customer_email = window.wcflow.orderState.billing_email;
        }

        // Update all hidden form fields
        updateHiddenFormFields();

        // Get payment method
        var selectedMethod = $('input[name="payment_method"]:checked').val();
        if (selectedMethod) {
            window.wcflow.orderState.payment_method = selectedMethod;
        }

        debug('Final orderState before validation:', window.wcflow.orderState);

        // COMPREHENSIVE validation
        let s = window.wcflow.orderState;
        let valid = true;
        let missingFields = [];
        
        // Check required shipping fields
        const requiredShippingFields = [
            'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 
            'shipping_city', 'shipping_postcode', 'shipping_country'
        ];
        
        requiredShippingFields.forEach(function(field) {
            if (!s[field] || String(s[field]).trim() === '') {
                valid = false;
                missingFields.push(field.replace('_', ' '));
                debug('Missing required field:', field, 'value:', s[field]);
            }
        });
        
        // Check email (more flexible - either customer_email or billing_email)
        if ((!s['customer_email'] || String(s['customer_email']).trim() === '') && 
            (!s['billing_email'] || String(s['billing_email']).trim() === '')) {
            valid = false;
            missingFields.push('email address');
        }
        
        // Check payment method
        if (!s['payment_method'] || String(s['payment_method']).trim() === '') {
            valid = false;
            missingFields.push('payment method');
        }
        
        // Check billing fields when buyer is not same
        if (!$('#wcflow-buyer-same').is(':checked')) {
            const requiredBillingFields = [
                'billing_first_name', 'billing_last_name', 'billing_address_1', 
                'billing_city', 'billing_postcode', 'billing_country'
            ];
            requiredBillingFields.forEach(function(field) {
                if (!s[field] || String(s[field]).trim() === '') {
                    valid = false;
                    missingFields.push(field.replace('_', ' '));
                }
            });
        }
        
        if (!valid) {
            const errorMsg = 'Please fill all required fields: ' + missingFields.join(', ');
            $('#wcflow-payment-error').text(errorMsg).show();
            debug('Validation failed - missing fields:', missingFields);
            return;
        } else {
            $('#wcflow-payment-error').hide();
        }

        // Create order via AJAX with proper data structure
        var orderData = {
            action: 'wcflow_create_order',
            nonce: wcflow_params.nonce,
            state: s
        };

        debug('Sending order data:', orderData);

        // Loading state
        $(this).prop('disabled', true).text('Processing...');
        $('.wcflow-spinner').remove();
        $(this).after('<span class="wcflow-spinner" style="margin-left:8px;">⏳</span>');

        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: orderData,
            success: function(response) {
                debug('Order creation response:', response);
                if (response.success && response.data && response.data.redirect_url) {
                    $('.wcflow-modal-content').html(`
                        <div style="text-align:center;padding:40px;">
                            <h2>Order Created Successfully!</h2>
                            <p>Your order #${response.data.order_id} has been created.</p>
                            <p>You will now be redirected to complete your payment.</p>
                            <div class="wcflow-loader" style="margin:20px auto;"></div>
                        </div>
                    `);
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else if (response.success) {
                    alert('Order created successfully! Order ID: ' + (response.data && response.data.order_id ? response.data.order_id : ''));
                    closeModal();
                } else {
                    $('#wcflow-payment-error').html(response.data && response.data.message ? response.data.message : 'Payment error').show();
                    $('#wcflow-place-order-btn').prop('disabled', false).text('Place an order');
                    $('.wcflow-spinner').remove();
                }
            },
            error: function(xhr, status, error) {
                debug('Order creation error:', xhr, status, error);
                $('#wcflow-payment-error').html('Network error. Please try again.').show();
                $('#wcflow-place-order-btn').prop('disabled', false).text('Place an order');
                $('.wcflow-spinner').remove();
            }
        });
    });

    // --- Step navigation with validation ---
    $document.on('click', '.wcflow-btn-next:not(#wcflow-place-order-btn)', function () {
        const currentStep = $(this).closest('.wcflow-modal').data('step');
        if (currentStep === 2 && window.wcflowValidateStep2) {
            if (!window.wcflowValidateStep2()) {
                return;
            }
        }
        loadStep(currentStep + 1);
    });
    $document.on('click', '.wcflow-btn-prev', function () {
        const currentStep = $(this).closest('.wcflow-modal').data('step');
        if (currentStep > 1) {
            loadStep(currentStep - 1);
        }
    });

    // --- Launch flow (e.g. from product page) ---
    $document.on('click', '.wcflow-start-btn', function (e) {
        e.preventDefault();
        const productId = $(this).data('product-id') || window.wcflow_product_id;
        if (!productId) return alert('Product not found.');
        $.post(wcflow_params.ajax_url, {
            action: 'wcflow_start_flow',
            nonce: wcflow_params.nonce,
            product_id: productId
        }).done(function (response) {
            if (response.success) loadStep(1);
            else alert(response.data ? response.data.message : 'Failed to start checkout');
        }).fail(function () {
            alert('Network error. Please try again.');
        });
    });

    // --- Utility: Order totals update (if you have this logic)
    function updateOrderTotals() {
        // If you have basket summary logic, include here.
        // Otherwise, leave as is.
    }
});