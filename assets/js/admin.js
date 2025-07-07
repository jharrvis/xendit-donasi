jQuery(document).ready(function($) {
    // Existing functionality
    $('.reset-data-button').on('click', function() {
        return confirm('Apakah Anda yakin ingin mereset semua data donasi? Tindakan ini tidak dapat dibatalkan.');
    });
    
    $('.toggle-api-key').on('click', function(e) {
        e.preventDefault();
        var apiKeyField = $('input[name="wp_xendit_donation_api_key"]');
        
        if (apiKeyField.attr('type') === 'password') {
            apiKeyField.attr('type', 'text');
            $(this).text('Sembunyikan');
        } else {
            apiKeyField.attr('type', 'password');
            $(this).text('Tampilkan');
        }
    });

    // New Exchange Rate functionality
    
    // Update rate from API
    $('#update-rate-now').on('click', function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: wp_xendit_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'update_exchange_rate',
                nonce: wp_xendit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Exchange rate updated successfully: 1 USD = ' + response.data.formatted, 'success');
                    // Update display on page
                    updateRateDisplay(response.data.rate, response.data.formatted);
                } else {
                    showAdminNotice('Error: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Connection error: ' + error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Toggle manual rate update form
    $('#manual-rate-update').on('click', function() {
        $('#manual-rate-input').toggle();
        $(this).text(function(i, text) {
            return text === 'Manual Update' ? 'Cancel' : 'Manual Update';
        });
    });
    
    // Save manual rate
    $('#save-manual-rate').on('click', function() {
        var rate = $('#manual_rate').val();
        var button = $(this);
        var originalText = button.text();
        
        if (!rate || parseFloat(rate) <= 0) {
            showAdminNotice('Please enter a valid exchange rate', 'error');
            return;
        }
        
        button.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: wp_xendit_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'manual_rate_update',
                rate: rate,
                nonce: wp_xendit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Manual rate saved successfully: 1 USD = ' + response.data.formatted, 'success');
                    updateRateDisplay(response.data.rate, response.data.formatted);
                    $('#manual-rate-input').hide();
                    $('#manual_rate').val('');
                    $('#manual-rate-update').text('Manual Update');
                } else {
                    showAdminNotice('Error: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminNotice('Connection error: ' + error, 'error');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Cancel manual rate update
    $('#cancel-manual-rate').on('click', function() {
        $('#manual-rate-input').hide();
        $('#manual_rate').val('');
        $('#manual-rate-update').text('Manual Update');
    });
    
    // Exchange API selection change
    $('select[name="wp_xendit_donation_exchange_api"]').on('change', function() {
        var selectedApi = $(this).val();
        var fixerKeyRow = $('input[name="wp_xendit_fixer_api_key"]').closest('tr');
        
        if (selectedApi === 'fixer') {
            fixerKeyRow.show();
        } else {
            fixerKeyRow.hide();
        }
    }).trigger('change');
    
    // Auto-update checkbox change
    $('input[name="wp_xendit_donation_auto_update_rate"]').on('change', function() {
        var intervalSelect = $('select[name="wp_xendit_donation_update_interval"]');
        
        if ($(this).is(':checked')) {
            intervalSelect.prop('disabled', false);
        } else {
            intervalSelect.prop('disabled', true);
        }
    }).trigger('change');
    
    // USD enable/disable toggle
    $('input[name="wp_xendit_donation_enable_usd"]').on('change', function() {
        var usdSettings = $('.usd-settings-row');
        var exchangeSettings = $('.exchange-settings-row');
        
        if ($(this).is(':checked')) {
            usdSettings.show();
            exchangeSettings.show();
        } else {
            usdSettings.hide();
            exchangeSettings.hide();
        }
    }).trigger('change');
    
    // Cron management buttons (if on exchange rates page)
    $('#reschedule-cron').on('click', function() {
        var button = $(this);
        
        if (!confirm('Are you sure you want to reschedule the exchange rate update cron job?')) {
            return;
        }
        
        button.prop('disabled', true).text('Rescheduling...');
        
        $.ajax({
            url: wp_xendit_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'reschedule_exchange_cron',
                nonce: wp_xendit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Cron job rescheduled successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAdminNotice('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showAdminNotice('Connection error', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Reschedule Cron');
            }
        });
    });
    
    $('#clear-cron').on('click', function() {
        var button = $(this);
        
        if (!confirm('Are you sure you want to clear the exchange rate update cron job? This will disable automatic updates.')) {
            return;
        }
        
        button.prop('disabled', true).text('Clearing...');
        
        $.ajax({
            url: wp_xendit_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_exchange_cron',
                nonce: wp_xendit_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotice('Cron job cleared successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showAdminNotice('Error: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showAdminNotice('Connection error', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Clear Cron');
            }
        });
    });
    
    // Bulk actions for donations list
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).siblings('select').val();
        var checkedBoxes = $('input[name="donation[]"]:checked');
        
        if (action === 'delete' && checkedBoxes.length > 0) {
            if (!confirm('Are you sure you want to delete the selected donations? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        }
        
        if (action === 'export' && checkedBoxes.length > 0) {
            e.preventDefault();
            exportSelectedDonations();
        }
    });
    
    // Export functionality
    function exportSelectedDonations() {
        var selectedIds = [];
        $('input[name="donation[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            showAdminNotice('Please select donations to export', 'error');
            return;
        }
        
        // Create and submit form for export
        var form = $('<form>', {
            method: 'POST',
            action: wp_xendit_admin.ajax_url
        });
        
        form.append($('<input>', {type: 'hidden', name: 'action', value: 'export_donations'}));
        form.append($('<input>', {type: 'hidden', name: 'nonce', value: wp_xendit_admin.nonce}));
        form.append($('<input>', {type: 'hidden', name: 'donation_ids', value: selectedIds.join(',')}));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    // Real-time validation for manual rate input
    $('#manual_rate').on('input', function() {
        var value = parseFloat($(this).val());
        var saveButton = $('#save-manual-rate');
        
        if (isNaN(value) || value <= 0) {
            saveButton.prop('disabled', true);
            $(this).css('border-color', '#dc3545');
        } else {
            saveButton.prop('disabled', false);
            $(this).css('border-color', '#28a745');
        }
    });
    
    // Form validation before submit
    $('form').on('submit', function() {
        var form = $(this);
        var hasErrors = false;
        
        // Validate USD minimum amount
        var usdMin = form.find('input[name="wp_xendit_donation_usd_minimum"]');
        if (usdMin.length && (isNaN(usdMin.val()) || parseFloat(usdMin.val()) < 1)) {
            showAdminNotice('USD minimum amount must be at least 1', 'error');
            hasErrors = true;
        }
        
        // Validate IDR minimum amount
        var idrMin = form.find('input[name="wp_xendit_donation_minimum_amount"]');
        if (idrMin.length && (isNaN(idrMin.val()) || parseFloat(idrMin.val()) < 1000)) {
            showAdminNotice('IDR minimum amount must be at least 1000', 'error');
            hasErrors = true;
        }
        
        // Validate Fixer API key if Fixer is selected
        var exchangeApi = form.find('select[name="wp_xendit_donation_exchange_api"]').val();
        var fixerKey = form.find('input[name="wp_xendit_fixer_api_key"]').val();
        
        if (exchangeApi === 'fixer' && !fixerKey.trim()) {
            showAdminNotice('Fixer.io API key is required when using Fixer.io as exchange rate source', 'error');
            hasErrors = true;
        }
        
        return !hasErrors;
    });
    
    /**
     * Helper function to update rate display on page
     */
    function updateRateDisplay(rate, formatted) {
        $('.rate-value .currency-to').text(formatted);
        $('.current-rate strong').html('1 USD = ' + formatted);
        
        // Update timestamp
        var now = new Date();
        var timestamp = now.getFullYear() + '-' + 
                       String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(now.getDate()).padStart(2, '0') + ' ' +
                       String(now.getHours()).padStart(2, '0') + ':' + 
                       String(now.getMinutes()).padStart(2, '0') + ':' + 
                       String(now.getSeconds()).padStart(2, '0');
        
        $('.rate-meta p').html('<strong>Last Updated:</strong> ' + timestamp);
    }
    
    /**
     * Helper function to show admin notices
     */
    function showAdminNotice(message, type) {
        type = type || 'info';
        
        var notice = $('<div>', {
            class: 'notice notice-' + type + ' is-dismissible',
            html: '<p>' + message + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
        });
        
        $('.wrap h1').after(notice);
        
        // Auto dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                notice.fadeOut(function() {
                    notice.remove();
                });
            }, 5000);
        }
        
        // Handle dismiss button
        notice.find('.notice-dismiss').on('click', function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        });
    }
    
    // Initialize tooltips if available
    if (typeof jQuery.fn.tooltip === 'function') {
        $('[data-tooltip]').tooltip();
    }
    
    // Auto-save draft functionality for settings
    var settingsForm = $('#wp-xendit-donation-settings-form');
    var autoSaveTimer;
    
    if (settingsForm.length) {
        settingsForm.find('input, select, textarea').on('change', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                showAdminNotice('Settings auto-saved as draft', 'info');
            }, 2000);
        });
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 83) {
            e.preventDefault();
            var submitButton = $('input[type="submit"], button[type="submit"]').first();
            if (submitButton.length) {
                submitButton.click();
            }
        }
        
        // Ctrl/Cmd + U to update exchange rate
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 85) {
            e.preventDefault();
            var updateButton = $('#update-rate-now');
            if (updateButton.length && updateButton.is(':visible')) {
                updateButton.click();
            }
        }
    });
    
    // Initialize page-specific functionality
    var currentPage = $('body').attr('class');
    
    if (currentPage && currentPage.includes('xendit-donation-exchange-rates')) {
        initExchangeRatesPage();
    }
    
    if (currentPage && currentPage.includes('xendit-donation-donations')) {
        initDonationsPage();
    }
    
    /**
     * Initialize exchange rates page specific functionality
     */
    function initExchangeRatesPage() {
        // Check if rates are outdated
        var lastUpdate = $('.rate-meta p').text();
        if (lastUpdate.includes('Never') || isRateOutdated(lastUpdate)) {
            showAdminNotice('Exchange rates may be outdated. Consider updating them.', 'warning');
        }
        
        // Auto-refresh every 5 minutes if page is active
        var refreshInterval = setInterval(function() {
            if (document.visibilityState === 'visible') {
                checkForRateUpdates();
            }
        }, 300000); // 5 minutes
        
        // Clear interval when page is unloaded
        $(window).on('beforeunload', function() {
            clearInterval(refreshInterval);
        });
    }
    
    /**
     * Initialize donations page specific functionality
     */
    function initDonationsPage() {
        // Add currency column sorting
        $('.column-amount').on('click', function() {
            var currentUrl = new URL(window.location);
            var orderby = currentUrl.searchParams.get('orderby');
            var order = currentUrl.searchParams.get('order');
            
            if (orderby === 'amount') {
                order = order === 'asc' ? 'desc' : 'asc';
            } else {
                order = 'desc';
            }
            
            currentUrl.searchParams.set('orderby', 'amount');
            currentUrl.searchParams.set('order', order);
            window.location = currentUrl.toString();
        });
        
        // Enhanced search with currency filter
        $('#donation-search').on('keyup', debounce(function() {
            var searchTerm = $(this).val();
            filterDonationsTable(searchTerm);
        }, 300));
    }
    
    /**
     * Check if rate is outdated (more than 12 hours old)
     */
    function isRateOutdated(lastUpdateText) {
        // Simple check - in real implementation, parse the date properly
        return lastUpdateText.includes('Never') || 
               (new Date() - new Date(lastUpdateText.split(': ')[1])) > 12 * 60 * 60 * 1000;
    }
    
    /**
     * Check for rate updates via AJAX
     */
    function checkForRateUpdates() {
        $.ajax({
            url: wp_xendit_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'check_rate_updates',
                nonce: wp_xendit_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data.updated) {
                    updateRateDisplay(response.data.rate, response.data.formatted);
                    showAdminNotice('Exchange rate updated automatically', 'info');
                }
            }
        });
    }
    
    /**
     * Filter donations table
     */
    function filterDonationsTable(searchTerm) {
        $('.wp-list-table tbody tr').each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            
            if (text.indexOf(searchTerm.toLowerCase()) > -1) {
                row.show();
            } else {
                row.hide();
            }
        });
    }
    
    /**
     * Debounce function to limit rate of function calls
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});