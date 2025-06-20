/**
 * Fix for duplicate nonce fields issue
 * This script removes duplicate nonce fields that may be created by WooCommerce or other plugins
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Function to remove duplicate nonce fields
    function removeDuplicateNonces() {
        const nonces = $('input[name="nonce"], input[id="nonce"]');
        
        if (nonces.length > 1) {
            console.log('Found ' + nonces.length + ' nonce fields, removing duplicates...');
            
            // Keep only the first nonce field and remove the rest
            nonces.slice(1).each(function(index) {
                console.log('Removing duplicate nonce field #' + (index + 2));
                $(this).remove();
            });
            
            console.log('Duplicate nonce fields removed successfully');
        }
    }
    
    // Remove duplicates on page load
    removeDuplicateNonces();
    
    // Also check after AJAX requests complete
    $(document).ajaxComplete(function() {
        setTimeout(removeDuplicateNonces, 100);
    });
    
    // Check when modals are opened
    $(document).on('DOMNodeInserted', '.wcflow-modal', function() {
        setTimeout(removeDuplicateNonces, 100);
    });
});
