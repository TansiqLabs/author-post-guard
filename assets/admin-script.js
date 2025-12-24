/**
 * Author Post Guard - Admin JavaScript
 * 
 * Handles form submissions, AJAX operations, and UI interactions.
 * 
 * @package AuthorPostGuard
 * @author Tansiq Labs <support@tansiqlabs.com>
 * @version 1.0.0
 */

(function($) {
    'use strict';

    /**
     * APG Admin Module
     */
    const APGAdmin = {
        
        /**
         * Initialize module
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Form submission
            $('#apg-settings-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Test webhook buttons
            $('.apg-test-webhook').on('click', this.handleTestWebhook.bind(this));
            
            // Check for updates button
            $('#apg-check-updates').on('click', this.handleCheckUpdates.bind(this));
            
            // Toggle password visibility for tokens
            $('[type="password"]').on('dblclick', function() {
                const type = $(this).attr('type') === 'password' ? 'text' : 'password';
                $(this).attr('type', type);
            });

            // Auto-save indicator
            $('input, select, textarea').on('change', function() {
                $('.apg-save-indicator').removeClass('visible').text('');
            });
        },

        /**
         * Handle settings form submission via AJAX
         * 
         * @param {Event} e Form submit event
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const $button = $form.find('.apg-btn-save');
            const $indicator = $('.apg-save-indicator');
            
            // Set loading state
            $button.addClass('loading').prop('disabled', true);
            
            // Collect form data
            const formData = $form.serialize();
            
            $.ajax({
                url: apgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apg_save_settings',
                    nonce: apgAdmin.nonce,
                    settings: formData
                },
                success: function(response) {
                    if (response.success) {
                        APGAdmin.showToast(response.data.message, 'success');
                        $indicator.text('✓ ' + apgAdmin.strings.saved).addClass('visible');
                    } else {
                        APGAdmin.showToast(response.data.message || apgAdmin.strings.error, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('APG Save Error:', error);
                    APGAdmin.showToast(apgAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Handle test webhook button click
         * 
         * @param {Event} e Click event
         */
        handleTestWebhook: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const type = $button.data('type');
            
            // Set loading state
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: apgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apg_test_webhook',
                    nonce: apgAdmin.nonce,
                    type: type
                },
                success: function(response) {
                    if (response.success) {
                        APGAdmin.showToast(response.data.message, 'success');
                    } else {
                        APGAdmin.showToast(response.data.message || 'Test failed', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('APG Test Error:', error);
                    APGAdmin.showToast('Failed to send test notification', 'error');
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Handle check for updates button click
         * 
         * @param {Event} e Click event
         */
        handleCheckUpdates: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            
            // Set loading state
            $button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: apgAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apg_check_updates',
                    nonce: apgAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        if (data.has_update) {
                            APGAdmin.showToast(data.message, 'success');
                            // Optionally refresh the page to show update notice
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        } else {
                            APGAdmin.showToast(data.message, 'success');
                        }
                        
                        // Update last check display
                        $('.apg-status-item:contains("Last Check") .apg-status-value').text(data.last_check);
                    } else {
                        APGAdmin.showToast(response.data.message || 'Update check failed', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('APG Update Check Error:', error);
                    APGAdmin.showToast('Failed to check for updates', 'error');
                },
                complete: function() {
                    $button.removeClass('loading').prop('disabled', false);
                }
            });
        },

        /**
         * Show toast notification
         * 
         * @param {string} message Message to display
         * @param {string} type    Type: 'success', 'error', 'info'
         */
        showToast: function(message, type) {
            // Remove existing toast
            $('.apg-toast').remove();
            
            // Create toast element
            const $toast = $('<div class="apg-toast"></div>')
                .addClass(type || '')
                .text(message)
                .appendTo('body');
            
            // Show toast with animation
            setTimeout(function() {
                $toast.addClass('visible');
            }, 10);
            
            // Auto-hide after 4 seconds
            setTimeout(function() {
                $toast.removeClass('visible');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 4000);
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Simple native tooltip enhancement
            $('[title]').each(function() {
                const $el = $(this);
                const title = $el.attr('title');
                
                if (title) {
                    $el.attr('data-tooltip', title).removeAttr('title');
                }
            });
        },

        /**
         * Confirm action with dialog
         * 
         * @param {string} message Confirmation message
         * @param {function} callback Callback on confirm
         */
        confirm: function(message, callback) {
            if (window.confirm(message)) {
                callback();
            }
        },

        /**
         * Copy text to clipboard
         * 
         * @param {string} text Text to copy
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    APGAdmin.showToast('Copied to clipboard!', 'success');
                }).catch(function(err) {
                    console.error('Failed to copy:', err);
                });
            } else {
                // Fallback for older browsers
                const $temp = $('<textarea>')
                    .val(text)
                    .appendTo('body')
                    .select();
                
                document.execCommand('copy');
                $temp.remove();
                APGAdmin.showToast('Copied to clipboard!', 'success');
            }
        }
    };

    /**
     * Tab navigation enhancement
     */
    const TabNavigation = {
        init: function() {
            // Smooth tab transitions
            $('.apg-tab').on('click', function(e) {
                // Don't prevent default - let the page navigate
                // Just add a loading indicator
                $(this).addClass('loading');
            });
            
            // Keyboard navigation
            $('.apg-tabs').on('keydown', function(e) {
                const $tabs = $(this).find('.apg-tab');
                const $current = $tabs.filter('.apg-tab-active');
                const currentIndex = $tabs.index($current);
                
                let newIndex = currentIndex;
                
                if (e.key === 'ArrowRight') {
                    newIndex = (currentIndex + 1) % $tabs.length;
                } else if (e.key === 'ArrowLeft') {
                    newIndex = (currentIndex - 1 + $tabs.length) % $tabs.length;
                }
                
                if (newIndex !== currentIndex) {
                    e.preventDefault();
                    $tabs.eq(newIndex).focus().click();
                }
            });
        }
    };

    /**
     * Menu Control Module
     */
    const MenuControl = {
        init: function() {
            // Select all / deselect all functionality
            this.addBulkControls();
        },

        addBulkControls: function() {
            $('.apg-role-card').each(function() {
                const $card = $(this);
                const $checkboxes = $card.find('input[type="checkbox"]');
                
                if ($checkboxes.length > 3) {
                    const $header = $card.find('.apg-role-header');
                    const $toggleAll = $('<button type="button" class="apg-btn apg-btn-sm apg-btn-outline" style="margin-left: auto;">Toggle All</button>');
                    
                    $toggleAll.on('click', function(e) {
                        e.preventDefault();
                        const allChecked = $checkboxes.filter(':checked').length === $checkboxes.length;
                        $checkboxes.prop('checked', !allChecked);
                    });
                    
                    $header.css('display', 'flex').append($toggleAll);
                }
            });
        }
    };

    /**
     * Form Validation Module
     */
    const FormValidation = {
        init: function() {
            this.bindValidation();
        },

        bindValidation: function() {
            // Webhook URL validation
            $('input[type="url"]').on('blur', function() {
                const $input = $(this);
                const value = $input.val().trim();
                
                if (value && !FormValidation.isValidUrl(value)) {
                    $input.addClass('apg-input-error');
                    FormValidation.showFieldError($input, 'Please enter a valid URL');
                } else {
                    $input.removeClass('apg-input-error');
                    FormValidation.clearFieldError($input);
                }
            });

            // Telegram chat ID format
            $('#telegram_chat_id').on('blur', function() {
                const $input = $(this);
                const value = $input.val().trim();
                
                if (value && !/^-?\d+$/.test(value)) {
                    $input.addClass('apg-input-error');
                    FormValidation.showFieldError($input, 'Chat ID should be a number (can start with -)');
                } else {
                    $input.removeClass('apg-input-error');
                    FormValidation.clearFieldError($input);
                }
            });
        },

        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        showFieldError: function($input, message) {
            this.clearFieldError($input);
            $('<span class="apg-field-error"></span>')
                .text(message)
                .css({
                    'color': 'var(--apg-error)',
                    'font-size': '0.75rem',
                    'display': 'block',
                    'margin-top': '4px'
                })
                .insertAfter($input);
        },

        clearFieldError: function($input) {
            $input.siblings('.apg-field-error').remove();
        }
    };

    /**
     * Document Ready
     */
    $(document).ready(function() {
        APGAdmin.init();
        TabNavigation.init();
        MenuControl.init();
        FormValidation.init();
        
        // Log initialization for debugging
        if (window.console && window.console.log) {
            console.log('Author Post Guard Admin: Initialized');
        }
    });

    // Expose to global scope for potential extensions
    window.APGAdmin = APGAdmin;

})(jQuery);
