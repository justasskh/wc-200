/**
 * WooCommerce Gifting Flow Admin Tools JavaScript
 * Enhanced admin interface for database management
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Auto-refresh stats every 30 seconds
    setInterval(function() {
        if ($('#wcflow-database-tools').length) {
            refreshDatabaseStats();
        }
    }, 30000);
    
    // Real-time validation
    function validateDatabaseConnection() {
        return $.ajax({
            url: wcflow_admin_tools.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_validate_database',
                nonce: wcflow_admin_tools.nonce
            }
        });
    }
    
    // Refresh database stats
    function refreshDatabaseStats() {
        validateDatabaseConnection().done(function(response) {
            if (response.success) {
                updateStatsDisplay(response.data);
            }
        });
    }
    
    // Update stats display
    function updateStatsDisplay(data) {
        $('.database-stats .total-cards').text(data.total_cards);
        $('.database-stats .published-cards').text(data.published_cards);
        $('.database-stats .total-categories').text(data.total_categories);
        $('.database-stats .relationships').text(data.relationships);
    }
    
    // Enhanced error handling
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (settings.url === wcflow_admin_tools.ajax_url) {
            console.error('WCFlow Admin Tools AJAX Error:', error);
            
            // Show user-friendly error message
            if ($('#debug-console').length) {
                const timestamp = new Date().toLocaleTimeString();
                $('#debug-console').val($('#debug-console').val() + 
                    '[' + timestamp + '] ❌ AJAX Error: ' + error + '\n');
            }
        }
    });
    
    // Auto-save functionality for inline edits
    $('.wcflow-inline-edit').on('change', function() {
        const $this = $(this);
        const postId = $this.data('post-id');
        const field = $this.data('field');
        const value = $this.val();
        
        // Show saving indicator
        $this.addClass('saving');
        
        $.ajax({
            url: wcflow_admin_tools.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_inline_save',
                nonce: wcflow_admin_tools.nonce,
                post_id: postId,
                field: field,
                value: value
            },
            success: function(response) {
                $this.removeClass('saving');
                if (response.success) {
                    $this.addClass('saved');
                    setTimeout(() => $this.removeClass('saved'), 2000);
                } else {
                    $this.addClass('error');
                    setTimeout(() => $this.removeClass('error'), 2000);
                }
            },
            error: function() {
                $this.removeClass('saving').addClass('error');
                setTimeout(() => $this.removeClass('error'), 2000);
            }
        });
    });
    
    // Bulk actions
    $('#wcflow-bulk-action').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selected = $('.wcflow-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selected.length === 0) {
            alert('Please select items to perform bulk action.');
            return;
        }
        
        if (!confirm('Are you sure you want to perform this action on ' + selected.length + ' items?')) {
            return;
        }
        
        $.ajax({
            url: wcflow_admin_tools.ajax_url,
            type: 'POST',
            data: {
                action: 'wcflow_bulk_action',
                nonce: wcflow_admin_tools.nonce,
                bulk_action: action,
                items: selected
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Bulk action failed: ' + response.data);
                }
            }
        });
    });
    
    // Image upload preview
    $('.wcflow-image-upload').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(this).siblings('.image-preview').attr('src', e.target.result).show();
            }.bind(this);
            reader.readAsDataURL(file);
        }
    });
    
    // Sortable functionality for reordering
    if ($('.wcflow-sortable').length) {
        $('.wcflow-sortable').sortable({
            items: '.wcflow-sortable-item',
            cursor: 'move',
            axis: 'y',
            update: function(event, ui) {
                const order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: wcflow_admin_tools.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcflow_update_order',
                        nonce: wcflow_admin_tools.nonce,
                        order: order
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success indicator
                            ui.item.effect('highlight', {}, 1000);
                        }
                    }
                });
            }
        });
    }
    
    // Real-time search
    $('#wcflow-search').on('input', function() {
        const query = $(this).val().toLowerCase();
        
        $('.wcflow-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(query));
        });
    });
    
    // Export functionality
    window.exportToCSV = function(data, filename) {
        const csv = convertToCSV(data);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    };
    
    function convertToCSV(data) {
        if (!data || data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const csvHeaders = headers.join(',');
        
        const csvRows = data.map(row => {
            return headers.map(header => {
                const value = row[header];
                return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
            }).join(',');
        });
        
        return [csvHeaders, ...csvRows].join('\n');
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('.wcflow-save-button').click();
        }
        
        // Ctrl+R to refresh
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            refreshDatabaseStats();
        }
    });
    
    // Initialize tooltips
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip({
            position: { my: "center bottom-20", at: "center top" }
        });
    }
    
    // Auto-save drafts
    let autoSaveTimer;
    $('.wcflow-auto-save').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(() => {
            const $form = $(this).closest('form');
            if ($form.length) {
                saveFormData($form);
            }
        }, 2000);
    });
    
    function saveFormData($form) {
        const formData = $form.serialize();
        
        $.ajax({
            url: wcflow_admin_tools.ajax_url,
            type: 'POST',
            data: formData + '&action=wcflow_auto_save&nonce=' + wcflow_admin_tools.nonce,
            success: function(response) {
                if (response.success) {
                    $('.auto-save-indicator').text('✓ Saved').show().fadeOut(2000);
                }
            }
        });
    }
});