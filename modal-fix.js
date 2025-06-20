/**
 * WooCommerce Gifting Flow - Modal Fix
 * This script fixes the modal display issue by ensuring the modal container is properly created and styled.
 */

jQuery(function($) {
    'use strict';
    
    // Check if the modal container exists, if not create it
    if (!$('.wcflow-modal-container').length) {
        $('body').append('<div class="wcflow-modal-container"></div>');
    }
    
    // Override the showModal function to ensure proper modal display
    window.wcflow = window.wcflow || {};
    
    // Store the original showModal function if it exists
    const originalShowModal = window.wcflow.showModal;
    
    // Define a new showModal function
    window.wcflow.showModal = function(html) {
        console.log('Enhanced showModal called');
        
        // Ensure the modal container exists
        if (!$('.wcflow-modal-container').length) {
            $('body').append('<div class="wcflow-modal-container"></div>');
        }
        
        // Add the HTML to the modal container and make it visible
        $('.wcflow-modal-container').html(html).addClass('visible');
        $('body').addClass('wcflow-modal-open');
        
        // Log for debugging
        console.log('Modal shown with enhanced function');
    };
    
    // Override the closeModal function
    const originalCloseModal = window.wcflow.closeModal;
    
    window.wcflow.closeModal = function() {
        console.log('Enhanced closeModal called');
        
        // Hide and empty the modal container
        $('.wcflow-modal-container').removeClass('visible').empty();
        $('body').removeClass('wcflow-modal-open');
        
        // Log for debugging
        console.log('Modal closed with enhanced function');
    };
    
    // Add inline styles to ensure modal displays correctly
    const modalStyles = `
        .wcflow-modal-container {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background-color: #fff !important; /* Solid white background */
            z-index: 999999 !important;
            display: none !important;
            overflow-y: auto !important;
        }
        
        .wcflow-modal-container.visible {
            display: block !important;
        }
        
        body.wcflow-modal-open {
            overflow: hidden !important;
        }
        
        .wcflow-modal {
            position: relative !important;
            background-color: #fff !important;
            margin: 0 auto !important;
            max-width: 100% !important;
            width: 100% !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            overflow: hidden !important;
        }
        
        /* Content wrapper with max-width */
        .wcflow-content-wrapper {
            max-width: 1344px !important;
            margin: 0 auto !important;
            padding: 0 20px !important;
            box-sizing: border-box !important;
        }
        
        /* Header styling */
        .wcflow-header {
            border-bottom: 1px solid #f0f0f0 !important;
            background-color: #fff !important;
            padding: 15px 0 !important;
        }
        
        .wcflow-header-inner {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        .wcflow-header-left {
            text-align: left !important;
        }
        
        .wcflow-header-right {
            text-align: right !important;
        }
        
        .wcflow-back-btn {
            background: none !important;
            border: none !important;
            color: #333 !important;
            cursor: pointer !important;
            display: flex !important;
            align-items: center !important;
            font-size: 14px !important;
            padding: 0 !important;
        }
        
        .wcflow-back-btn svg {
            margin-right: 5px !important;
        }
        
        .wcflow-secure-checkout {
            display: flex !important;
            align-items: center !important;
            font-size: 14px !important;
            color: #333 !important;
        }
        
        .wcflow-secure-checkout svg {
            margin-right: 5px !important;
        }
        
        /* Modal body styling */
        .wcflow-modal-body {
            padding: 30px 0 100px !important; /* Bottom padding for footer */
        }
        
        /* Fixed footer styling */
        .wcflow-bottom-bar {
            padding: 20px 0 !important;
            border-top: 1px solid #f0f0f0 !important;
            background-color: #fff !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1000000 !important;
        }
        
        .wcflow-bottom-bar-inner {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        /* Order summary styling */
        .wcflow-order-summary {
            text-align: left !important;
        }
        
        .wcflow-order-total-line {
            font-size: 16px !important;
            font-weight: 600 !important;
            margin-bottom: 5px !important;
        }
        
        .wcflow-order-details {
            font-size: 14px !important;
            color: #666 !important;
        }
        
        /* Button styling */
        .wcflow-bottom-bar-btn {
            padding: 12px 24px !important;
            border-radius: 4px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            border: none !important;
            background-color: #007cba !important;
            color: #fff !important;
            font-size: 14px !important;
        }
        
        .wcflow-bottom-bar-btn:hover {
            background-color: #006ba1 !important;
        }
        
        /* Add-ons grid styling */
        .wcflow-addons-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
            gap: 20px !important;
            margin: 20px 0 !important;
        }
        
        /* Section titles */
        .wcflow-main-title {
            font-size: 28px !important;
            margin: 20px 0 10px !important;
            color: #333 !important;
            text-align: center !important;
        }
        
        .wcflow-subtitle {
            font-size: 16px !important;
            margin: 0 0 30px !important;
            color: #666 !important;
            text-align: center !important;
        }
        
        .wcflow-section-title {
            font-size: 22px !important;
            margin: 30px 0 10px !important;
            color: #333 !important;
        }
    `;
    
    // Add the styles to the head
    if (!$('#wcflow-modal-fix-styles').length) {
        $('head').append(`<style id="wcflow-modal-fix-styles">${modalStyles}</style>`);
    }
    
    // Override the start flow function to ensure it uses our enhanced showModal
    $(document).off('click', '.wcflow-start-btn').on('click', '.wcflow-start-btn', function(e) {
        e.preventDefault();
        
        console.log('Start button clicked with enhanced handler');
        
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
                    // Get base product price for calculations
                    wcflow_params.base_product_price = response.data.product_price || 0;
                    console.log('Flow started with base price', wcflow_params.base_product_price);
                    
                    // Use our enhanced loadStep function
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
    
    // Enhanced loadStep function
    function loadStep(step) {
        console.log('Enhanced loadStep called for step', step);
        
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
                    window.wcflow.showModal('<div class="wcflow-loading-container" style="display:flex;align-items:center;justify-content:center;height:100vh;"><div class="wcflow-loader"></div></div>');
                }
            },
            success: function(response) {
                if (response.success) {
                    window.wcflow.showModal(response.data.html);
                    
                    // Initialize step-specific functionality if available
                    if (window.wcflow.initializeStep) {
                        window.wcflow.initializeStep(step);
                    } else if (window.initializeStep) {
                        window.initializeStep(step);
                    } else {
                        console.log('No initializeStep function found, using fallback');
                        // Fallback initialization
                        if (step === 1) {
                            if (window.wcflow.loadAddons) {
                                window.wcflow.loadAddons();
                            }
                        }
                    }
                    
                    $(document).trigger('wcflow_step_loaded', [step]);
                } else {
                    alert('Could not load step. Please try again.');
                    window.wcflow.closeModal();
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                window.wcflow.closeModal();
            }
        });
    }
    
    console.log('WCFlow Modal Fix initialized');
});
