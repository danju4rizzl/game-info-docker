/**
 * Admin JavaScript for PandaScore Tracker Plugin
 * 
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initApiStatus();
        initCacheControls();
    });

    /**
     * Initialize API status display
     */
    function initApiStatus() {
        const statusContainer = $('#pandascore-api-status');
        
        if (!statusContainer.length || !window.pandascoreAdmin) {
            return;
        }

        const apiStatus = window.pandascoreAdmin.apiStatus;
        let html = '';

        // API Key Status
        html += '<div class="pandascore-status-item">';
        html += '<strong>API Key:</strong> ';
        if (apiStatus.api_key_configured) {
            html += '<span class="pandascore-status-good">✓ Configured</span>';
        } else {
            html += '<span class="pandascore-status-error">✗ Not configured</span>';
        }
        html += '</div>';

        // API Connectivity
        html += '<div class="pandascore-status-item">';
        html += '<strong>API Connection:</strong> ';
        if (apiStatus.api_connectivity) {
            html += '<span class="pandascore-status-good">✓ Connected</span>';
        } else {
            html += '<span class="pandascore-status-error">✗ Failed</span>';
            if (apiStatus.api_error) {
                html += '<br><small>' + escapeHtml(apiStatus.api_error) + '</small>';
            }
        }
        html += '</div>';

        // Rate Limit Status
        if (apiStatus.rate_limit_status) {
            const rateLimit = apiStatus.rate_limit_status;
            html += '<div class="pandascore-status-item">';
            html += '<strong>Rate Limit:</strong> ';
            html += rateLimit.requests_made + '/' + rateLimit.limit + ' requests used';
            html += '<br><small>' + rateLimit.remaining + ' requests remaining</small>';
            html += '</div>';
        }

        // Cache Status
        if (apiStatus.cache_status) {
            const cache = apiStatus.cache_status;
            html += '<div class="pandascore-status-item">';
            html += '<strong>Cache:</strong> ';
            if (cache.enabled) {
                html += '<span class="pandascore-status-good">✓ Enabled</span>';
                html += '<br><small>Expires after ' + cache.expiration + ' seconds</small>';
            } else {
                html += '<span class="pandascore-status-warning">⚠ Disabled</span>';
            }
            html += '</div>';
        }

        statusContainer.html(html);
    }

    /**
     * Initialize cache control buttons
     */
    function initCacheControls() {
        const clearCacheBtn = $('#pandascore-clear-cache');
        const testApiBtn = $('#pandascore-test-api');

        if (clearCacheBtn.length) {
            clearCacheBtn.on('click', function(e) {
                e.preventDefault();
                clearCache();
            });
        }

        if (testApiBtn.length) {
            testApiBtn.on('click', function(e) {
                e.preventDefault();
                testApiConnection();
            });
        }
    }

    /**
     * Clear plugin cache via AJAX
     */
    function clearCache() {
        const button = $('#pandascore-clear-cache');
        const originalText = button.text();
        
        // Disable button and show loading state
        button.prop('disabled', true)
              .addClass('pandascore-loading')
              .text('Clearing');

        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pandascore_clear_cache',
                nonce: window.pandascoreAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Cache cleared successfully!', 'success');
                } else {
                    showNotice('Failed to clear cache: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotice('Failed to clear cache: Network error', 'error');
            },
            complete: function() {
                // Re-enable button
                button.prop('disabled', false)
                      .removeClass('pandascore-loading')
                      .text(originalText);
            }
        });
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        
        // Insert after the first h1 in .wrap
        $('.wrap h1').first().after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 5000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    /**
     * Test API connection
     */
    function testApiConnection() {
        const button = $('#pandascore-test-api');
        const originalText = button.text();
        
        button.prop('disabled', true)
              .addClass('pandascore-loading')
              .text('Testing');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'pandascore_test_api',
                nonce: window.pandascoreAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('API connection successful!', 'success');
                    // Refresh API status
                    setTimeout(initApiStatus, 1000);
                } else {
                    showNotice('API test failed: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotice('API test failed: Network error', 'error');
            },
            complete: function() {
                button.prop('disabled', false)
                      .removeClass('pandascore-loading')
                      .text(originalText);
            }
        });
    }

    // Export functions for potential external use
    window.pandascoreAdmin = window.pandascoreAdmin || {};
    window.pandascoreAdmin.clearCache = clearCache;
    window.pandascoreAdmin.testApiConnection = testApiConnection;
    window.pandascoreAdmin.showNotice = showNotice;

})(jQuery);
