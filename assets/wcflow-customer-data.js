/**
 * WooCommerce Gifting Flow - Customer Data Capture
 * Enhanced data persistence and transfer between steps
 * Updated: 2025-01-27 - Fixed data transfer and session management
 */
jQuery(function($) {
    window.wcflow = window.wcflow || {};
    window.wcflow.orderState = window.wcflow.orderState || {};

    // Enhanced function to add customer data to form
    function addCustomerDataToForm() {
        const $form = $('#wcflow-checkout-form, form[name="checkout"]');
        if (!$form.length) return;
        
        // Remove previous hidden fields to prevent duplicates
        $form.find('input[name^="customer_data["]').remove();
        
        // Add each field from orderState as hidden inputs
        Object.entries(window.wcflow.orderState).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                $form.append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'customer_data[' + key + ']',
                        value: String(value),
                        'data-wcflow-field': key
                    })
                );
                console.log('[WCFlow] Added hidden field:', key, '=', value);
            }
        });
        
        console.log('[WCFlow] Added', Object.keys(window.wcflow.orderState).length, 'customer data fields to form');
    }

    // Enhanced function to save data to session
    function saveCustomerDataToSession() {
        if (Object.keys(window.wcflow.orderState).length === 0) {
            console.log('[WCFlow] No customer data to save');
            return;
        }

        $.ajax({
            url: wcflow_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_save_customer_data',
                nonce: wcflow_params.nonce,
                customer_data: window.wcflow.orderState
            },
            success: function(response) {
                if (response.success) {
                    console.log('[WCFlow] Customer data saved to session:', response.data);
                } else {
                    console.error('[WCFlow] Failed to save customer data:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('[WCFlow] AJAX error saving customer data:', error);
            }
        });
    }

    // Function to sync form data to orderState
    function syncFormDataToState() {
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

        let hasChanges = false;
        Object.entries(fieldMappings).forEach(([fieldId, stateKey]) => {
            const $field = $('#' + fieldId);
            if ($field.length) {
                const value = $field.val();
                if (value !== undefined && value !== null && value !== '') {
                    if (window.wcflow.orderState[stateKey] !== value) {
                        window.wcflow.orderState[stateKey] = value;
                        hasChanges = true;
                        console.log('[WCFlow] Synced', stateKey, '=', value);
                    }
                }
            }
        });

        // Sync billing fields if present
        $('input[data-wcflow-billing], select[data-wcflow-billing]').each(function() {
            const $field = $(this);
            const key = $field.data('wcflow-billing');
            if (key) {
                const value = $field.val();
                if (value !== undefined && value !== null && value !== '') {
                    if (window.wcflow.orderState[key] !== value) {
                        window.wcflow.orderState[key] = value;
                        hasChanges = true;
                        console.log('[WCFlow] Synced billing', key, '=', value);
                    }
                }
            }
        });

        if (hasChanges) {
            console.log('[WCFlow] Order state updated:', window.wcflow.orderState);
            // Auto-save to session when data changes
            saveCustomerDataToSession();
        }
    }

    // Event handlers for form submission and order placement
    $(document).on('click', '#wcflow-place-order-btn', function() {
        console.log('[WCFlow] Place order button clicked - ensuring data is synced');
        syncFormDataToState();
        addCustomerDataToForm();
    });

    $(document).on('submit', '#wcflow-checkout-form, form[name="checkout"]', function() {
        console.log('[WCFlow] Form submission detected - ensuring data is synced');
        syncFormDataToState();
        addCustomerDataToForm();
    });

    // Auto-sync data when moving from step 2 to step 3
    $(document).on('click', '.wcflow-step[data-step="2"] .wcflow-next-btn, .wcflow-btn-next', function() {
        const currentStep = $(this).closest('.wcflow-modal, .wcflow-step').data('step');
        if (currentStep === 2) {
            console.log('[WCFlow] Moving from step 2 - syncing customer data');
            syncFormDataToState();
            saveCustomerDataToSession();
        }
    });

    // Auto-sync data on form field changes (debounced)
    let syncTimeout;
    $(document).on('input change', 'input[id^="wcflow-"], select[id^="wcflow-"], input[data-wcflow-billing], select[data-wcflow-billing]', function() {
        clearTimeout(syncTimeout);
        syncTimeout = setTimeout(function() {
            syncFormDataToState();
        }, 500); // 500ms debounce
    });

    // Ensure email fields are synchronized
    $(document).on('input change', '#wcflow-customer-email', function() {
        const email = $(this).val();
        if (email) {
            window.wcflow.orderState.customer_email = email;
            window.wcflow.orderState.billing_email = email;
        }
    });

    // Initialize on document ready
    $(document).ready(function() {
        console.log('[WCFlow] Customer data handler initialized');
        
        // Initial sync if we're on a step with form data
        setTimeout(function() {
            syncFormDataToState();
        }, 500);
    });

    // Debug function to check current state
    window.wcflowDebugState = function() {
        console.log('[WCFlow] Current order state:', window.wcflow.orderState);
        console.log('[WCFlow] Hidden form fields:', $('input[name^="customer_data["]').length);
        return window.wcflow.orderState;
    };
});