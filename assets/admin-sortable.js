/**
 * WooCommerce Gifting Flow Admin Sortable Functionality
 * Drag and drop ordering for cards and categories
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Make post tables sortable
    if ($('.wp-list-table tbody').length) {
        $('.wp-list-table tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            containment: 'parent',
            scrollSensitivity: 40,
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                ui.css('left', '0');
                return ui;
            },
            start: function(e, ui) {
                ui.item.css('background-color', '#f6f7f7');
            },
            stop: function(e, ui) {
                ui.item.removeAttr('style');
                
                // Update menu order for all visible items
                var order = 0;
                $('.wp-list-table tbody tr').each(function() {
                    var postId = $(this).find('input[name="post[]"]').val();
                    if (postId) {
                        updateMenuOrder(postId, order);
                        order++;
                    }
                });
            }
        });
        
        // Add visual indicator
        $('.wp-list-table tbody tr').css('cursor', 'move');
        $('.wp-list-table tbody tr').attr('title', 'Drag to reorder');
    }
    
    // Make category table sortable
    if ($('.wp-list-table.tags tbody').length) {
        $('.wp-list-table.tags tbody').sortable({
            items: 'tr',
            cursor: 'move',
            axis: 'y',
            containment: 'parent',
            scrollSensitivity: 40,
            helper: function(e, ui) {
                ui.children().each(function() {
                    $(this).width($(this).width());
                });
                ui.css('left', '0');
                return ui;
            },
            start: function(e, ui) {
                ui.item.css('background-color', '#f6f7f7');
            },
            stop: function(e, ui) {
                ui.item.removeAttr('style');
                
                // Update category order for all visible items
                var order = 0;
                $('.wp-list-table.tags tbody tr').each(function() {
                    var termId = $(this).find('input[name="delete_tags[]"]').val();
                    if (termId) {
                        updateCategoryOrder(termId, order);
                        order++;
                    }
                });
            }
        });
        
        // Add visual indicator
        $('.wp-list-table.tags tbody tr').css('cursor', 'move');
        $('.wp-list-table.tags tbody tr').attr('title', 'Drag to reorder');
    }
});

// Update menu order function
function updateMenuOrder(postId, order) {
    jQuery.ajax({
        url: wcflow_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'wcflow_update_menu_order',
            nonce: wcflow_admin.nonce,
            post_id: postId,
            menu_order: order
        },
        success: function(response) {
            if (response.success) {
                // Update the order input field if it exists
                jQuery('tr').each(function() {
                    var currentPostId = jQuery(this).find('input[name="post[]"]').val();
                    if (currentPostId == postId) {
                        jQuery(this).find('input[type="number"]').val(order);
                    }
                });
            }
        }
    });
}

// Update category order function
function updateCategoryOrder(termId, order) {
    jQuery.ajax({
        url: wcflow_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'wcflow_update_category_order',
            nonce: wcflow_admin.nonce,
            term_id: termId,
            order: order
        },
        success: function(response) {
            if (response.success) {
                // Update the order input field if it exists
                jQuery('tr').each(function() {
                    var currentTermId = jQuery(this).find('input[name="delete_tags[]"]').val();
                    if (currentTermId == termId) {
                        jQuery(this).find('input[type="number"]').val(order);
                    }
                });
            }
        }
    });
}

// Global functions for inline editing
window.updateMenuOrder = updateMenuOrder;
window.updateCategoryOrder = updateCategoryOrder;