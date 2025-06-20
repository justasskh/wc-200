/**
 * Debug script for WooCommerce Gifting Flow order creation
 * This script helps identify data transmission issues
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🔍 WCFlow Debug Script Loaded');
    
    // Override the original setupOrderPlacement function to add debugging
    if (window.wcflow && window.wcflow.setupOrderPlacement) {
        const originalSetupOrderPlacement = window.wcflow.setupOrderPlacement;
        window.wcflow.setupOrderPlacement = function() {
            console.log('🔧 Setting up order placement with debugging...');
            originalSetupOrderPlacement.call(this);
        };
    }
    
    // Monitor order state changes
    let lastOrderState = null;
    
    function monitorOrderState() {
        if (window.wcflow && window.wcflow.orderState) {
            const currentState = JSON.stringify(window.wcflow.orderState);
            if (currentState !== lastOrderState) {
                console.log('📊 Order State Changed:', window.wcflow.orderState);
                lastOrderState = currentState;
                
                // Check for required fields
                const requiredFields = [
                    'customer_email', 'shipping_first_name', 'shipping_last_name',
                    'shipping_phone', 'shipping_address_1', 'shipping_city', 
                    'shipping_postcode', 'shipping_country'
                ];
                
                const missingFields = [];
                requiredFields.forEach(field => {
                    if (!window.wcflow.orderState[field] || !window.wcflow.orderState[field].trim()) {
                        missingFields.push(field);
                    }
                });
                
                if (missingFields.length > 0) {
                    console.warn('⚠️ Missing required fields in order state:', missingFields);
                } else {
                    console.log('✅ All required fields present in order state');
                }
            }
        }
    }
    
    // Monitor every 1 second
    setInterval(monitorOrderState, 1000);
    
    // Intercept AJAX requests to wcflow_create_order
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('wcflow_create_order')) {
            console.log('🚀 AJAX Request to wcflow_create_order');
            console.log('📤 Request URL:', settings.url);
            console.log('📤 Request Data:', settings.data);
            
            // Parse the data to see what's being sent
            const params = new URLSearchParams(settings.data);
            const state = params.get('state');
            
            if (state) {
                try {
                    const parsedState = JSON.parse(state);
                    console.log('📋 Parsed State Object:', parsedState);
                    
                    // Check required fields in the actual request
                    const requiredFields = [
                        'customer_email', 'shipping_first_name', 'shipping_last_name',
                        'shipping_phone', 'shipping_address_1', 'shipping_city', 
                        'shipping_postcode', 'shipping_country'
                    ];
                    
                    const missingInRequest = [];
                    requiredFields.forEach(field => {
                        if (!parsedState[field] || !parsedState[field].trim()) {
                            missingInRequest.push(field);
                        }
                    });
                    
                    if (missingInRequest.length > 0) {
                        console.error('❌ Missing required fields in AJAX request:', missingInRequest);
                        console.log('🔍 Full state being sent:', parsedState);
                    } else {
                        console.log('✅ All required fields present in AJAX request');
                    }
                    
                } catch (e) {
                    console.error('❌ Failed to parse state JSON:', e);
                    console.log('📄 Raw state string:', state);
                }
            } else {
                console.error('❌ No state parameter found in request');
            }
        }
    });
    
    // Monitor AJAX responses
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('wcflow_create_order')) {
            console.log('📥 AJAX Response from wcflow_create_order');
            console.log('📊 Status:', xhr.status);
            
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('📋 Response Data:', response);
                
                if (!response.success && response.data && response.data.message) {
                    console.error('❌ Server Error:', response.data.message);
                    
                    // Check if it's a missing field error
                    if (response.data.message.includes('Missing required field')) {
                        console.log('🔍 This is a missing field error - checking current form state...');
                        
                        // Check current form values
                        const formData = {};
                        $('input[id^="wcflow-"], select[id^="wcflow-"]').each(function() {
                            const id = $(this).attr('id');
                            const value = $(this).val();
                            formData[id] = value;
                            console.log(`📝 Form field ${id}: "${value}"`);
                        });
                        
                        console.log('📋 Current form data:', formData);
                    }
                }
            } catch (e) {
                console.error('❌ Failed to parse response:', e);
                console.log('📄 Raw response:', xhr.responseText);
            }
        }
    });
    
    // Monitor form field changes
    $(document).on('input change keyup blur', 'input[id^="wcflow-"], select[id^="wcflow-"]', function() {
        const fieldId = $(this).attr('id');
        const value = $(this).val();
        const stateKey = fieldId.replace('wcflow-', '').replace('-', '_');
        
        console.log(`📝 Form field changed: ${fieldId} = "${value}" (maps to ${stateKey})`);
        
        // Check if it's being saved to orderState
        setTimeout(() => {
            if (window.wcflow && window.wcflow.orderState && window.wcflow.orderState[stateKey]) {
                console.log(`✅ Field saved to orderState: ${stateKey} = "${window.wcflow.orderState[stateKey]}"`);
            } else {
                console.warn(`⚠️ Field NOT saved to orderState: ${stateKey}`);
            }
        }, 100);
    });
    
    // Add a manual test function
    window.debugWCFlow = function() {
        console.log('🧪 Manual WCFlow Debug Test');
        
        if (!window.wcflow || !window.wcflow.orderState) {
            console.error('❌ WCFlow not initialized or orderState missing');
            return;
        }
        
        console.log('📋 Current Order State:', window.wcflow.orderState);
        
        // Check sessionStorage
        const sessionData = sessionStorage.getItem('wcflow_order_state');
        if (sessionData) {
            try {
                const parsedSession = JSON.parse(sessionData);
                console.log('💾 SessionStorage Data:', parsedSession);
            } catch (e) {
                console.error('❌ Failed to parse sessionStorage data:', e);
            }
        } else {
            console.warn('⚠️ No data in sessionStorage');
        }
        
        // Check form fields
        console.log('📝 Current Form Fields:');
        $('input[id^="wcflow-"], select[id^="wcflow-"]').each(function() {
            const id = $(this).attr('id');
            const value = $(this).val();
            console.log(`  ${id}: "${value}"`);
        });
        
        // Test serialization
        const serialized = JSON.stringify(window.wcflow.orderState);
        console.log('📤 Serialized State:', serialized);
        
        try {
            const parsed = JSON.parse(serialized);
            console.log('✅ Serialization test passed');
        } catch (e) {
            console.error('❌ Serialization test failed:', e);
        }
    };
    
    console.log('🔍 Debug script ready. Use debugWCFlow() in console for manual testing.');
});
