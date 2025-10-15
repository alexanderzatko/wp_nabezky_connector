jQuery(document).ready(function($) {
    'use strict';
    
    // Admin page functionality
    if ($('body').hasClass('settings_page_wp-nabezky-connector')) {
        
        // Token visibility toggle
        var $tokenField = $('#nabezky_access_token');
        var $toggleButton = $('#toggle-token-visibility');
        var originalValue = $tokenField.val();
        var isHidden = originalValue.length > 0; // Hide by default if there's a value
        
        // Initialize: hide the token if there's a value
        if (isHidden) {
            $tokenField.hide();
            $toggleButton.text(nabezky_admin.i18n.show);
        } else {
            $toggleButton.text(nabezky_admin.i18n.hide);
        }
        
        $toggleButton.on('click', function() {
            if (isHidden) {
                // Show the token
                $tokenField.show().val(originalValue);
                $toggleButton.text(nabezky_admin.i18n.hide);
                isHidden = false;
            } else {
                // Hide the token - store current value before hiding
                var currentValue = $tokenField.val();
                if (currentValue && currentValue.length > 0) {
                    originalValue = currentValue;
                }
                $tokenField.hide();
                $toggleButton.text(nabezky_admin.i18n.show);
                isHidden = true;
            }
        });
        
        // Product selection helpers
        $('.nabezky-products-fieldset').before(
            '<p>' +
            '<button type="button" id="select-all-products" class="button">' + nabezky_admin.i18n.selectAll + '</button> ' +
            '<button type="button" id="deselect-all-products" class="button">' + nabezky_admin.i18n.deselectAll + '</button>' +
            '</p>'
        );
        
        $('#select-all-products').on('click', function() {
            $('.nabezky-products-fieldset input[type="checkbox"]').prop('checked', true);
        });
        
        $('#deselect-all-products').on('click', function() {
            $('.nabezky-products-fieldset input[type="checkbox"]').prop('checked', false);
        });
        
        // Test connection button with real API testing
        $('#nabezky_api_url').after('<button type="button" id="test-connection" class="button">' + nabezky_admin.i18n.testConnection + '</button>');
        
        // Add test results container
        $('<div id="test-results-container" style="display: none; margin-top: 15px;"></div>').insertAfter('#test-connection');
        
        $('#test-connection').on('click', function() {
            var $button = $(this);
            var $container = $('#test-results-container');
            var originalText = $button.text();
            
            // Reset and show loading state
            $button.prop('disabled', true).text(nabezky_admin.i18n.testing);
            $container.hide().empty();
            
            // Make AJAX request to test connection
            $.ajax({
                url: nabezky_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'test_nabezky_connection',
                    nonce: nabezky_admin.nonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    $button.prop('disabled', false).text(originalText);
                    displayTestResults(response);
                },
                error: function(xhr, status, error) {
                $button.prop('disabled', false).text(originalText);
                    displayTestError(xhr, status, error);
                }
            });
        });
        
        // Function to display test results
        function displayTestResults(response) {
            var $container = $('#test-results-container');
            var html = '<div class="test-results" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; background: #f9f9f9;">';
            
            // Overall status
            var statusClass = response.success ? 'notice-success' : 'notice-error';
            var statusIcon = response.success ? '✓' : '✗';
            var statusText = response.success ? nabezky_admin.i18n.testSuccess : nabezky_admin.i18n.testFailed;
            
            html += '<div class="' + statusClass + '" style="padding: 10px; margin-bottom: 15px; border-radius: 3px;">';
            html += '<strong>' + statusIcon + ' ' + statusText + '</strong><br>';
            html += '<em>' + response.message + '</em>';
            html += '</div>';
            
            // Response time
            if (response.response_time) {
                html += '<p><strong>' + nabezky_admin.i18n.responseTime + '</strong> ' + response.response_time + 'ms</p>';
            }
            
            // Individual test results (excluding authentication test)
            if (response.tests && Object.keys(response.tests).length > 0) {
                html += '<h4>' + nabezky_admin.i18n.testResults + '</h4>';
                html += '<div class="test-details" style="margin-top: 10px;">';
                
                $.each(response.tests, function(testName, testResult) {
                    // Skip authentication test as it will always fail and might confuse users
                    if (testName === 'authentication') {
                        return;
                    }
                    
                    var testIcon = testResult.success ? '✓' : '✗';
                    var testClass = testResult.success ? 'color: green;' : 'color: red;';
                    
                    html += '<div style="margin-bottom: 10px; padding: 8px; border-left: 3px solid ' + (testResult.success ? '#46b450' : '#dc3232') + '; background: white;">';
                    html += '<strong style="' + testClass + '">' + testIcon + ' ' + formatTestName(testName) + '</strong>';
                    
                    // Show details if available
                    if (testResult.details && Object.keys(testResult.details).length > 0) {
                        html += '<ul style="margin: 5px 0 0 20px; font-size: 12px;">';
                        $.each(testResult.details, function(key, value) {
                            html += '<li><strong>' + key + ':</strong> ' + value + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    // Show errors
                    if (testResult.errors && testResult.errors.length > 0) {
                        html += '<ul style="margin: 5px 0 0 20px; font-size: 12px; color: #dc3232;">';
                        $.each(testResult.errors, function(index, error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    // Show warnings
                    if (testResult.warnings && testResult.warnings.length > 0) {
                        html += '<ul style="margin: 5px 0 0 20px; font-size: 12px; color: #ffb900;">';
                        $.each(testResult.warnings, function(index, warning) {
                            html += '<li>' + warning + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            // Global errors
            if (response.errors && response.errors.length > 0) {
                html += '<div style="background: #fbeaea; border: 1px solid #dc3232; padding: 10px; margin-top: 10px; border-radius: 3px;">';
                html += '<h4 style="color: #dc3232; margin: 0 0 5px 0;">' + nabezky_admin.i18n.errors + '</h4>';
                html += '<ul style="margin: 0; color: #dc3232;">';
                $.each(response.errors, function(index, error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            // Global warnings
            if (response.warnings && response.warnings.length > 0) {
                html += '<div style="background: #fff8e5; border: 1px solid #ffb900; padding: 10px; margin-top: 10px; border-radius: 3px;">';
                html += '<h4 style="color: #ffb900; margin: 0 0 5px 0;">' + nabezky_admin.i18n.warnings + '</h4>';
                html += '<ul style="margin: 0; color: #ffb900;">';
                $.each(response.warnings, function(index, warning) {
                    html += '<li>' + warning + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            html += '</div>';
            
            $container.html(html).show();
        }
        
        // Function to display test errors
        function displayTestError(xhr, status, error) {
            var $container = $('#test-results-container');
            var html = '<div class="test-error" style="border: 1px solid #dc3232; border-radius: 5px; padding: 15px; background: #fbeaea;">';
            html += '<div class="notice-error" style="padding: 10px; margin-bottom: 10px; border-radius: 3px;">';
            html += '<strong>✗ ' + nabezky_admin.i18n.testError + '</strong><br>';
            html += '<em>' + nabezky_admin.i18n.testFailed + '</em>';
            html += '</div>';
            html += '<p><strong>Error Details:</strong></p>';
            html += '<ul>';
            html += '<li><strong>Status:</strong> ' + status + '</li>';
            html += '<li><strong>Error:</strong> ' + error + '</li>';
            if (xhr.responseText) {
                html += '<li><strong>Response:</strong> ' + xhr.responseText.substring(0, 200) + '</li>';
            }
            html += '</ul>';
            html += '</div>';
            
            $container.html(html).show();
        }
        
        // Helper function to format test names
        function formatTestName(name) {
            var formatted = name.charAt(0).toUpperCase() + name.slice(1);
            switch(name) {
                case 'configuration': return nabezky_admin.i18n.configurationValidation;
                case 'connectivity': return nabezky_admin.i18n.serverConnectivity;
                case 'endpoint': return nabezky_admin.i18n.endpointAvailability;
                case 'authentication': return nabezky_admin.i18n.authentication;
                default: return formatted;
            }
        }
        
        // Form validation
        $('form').on('submit', function(e) {
            var errors = [];
            
            // Check if plugin is enabled
            if ($('#enabled').is(':checked')) {
                // Check if access token is provided
                var tokenValue = '';
                if (isHidden) {
                    // Token is hidden, check if there's an original value
                    tokenValue = originalValue;
                } else {
                    // Token is visible, get current value
                    tokenValue = $('#nabezky_access_token').val();
                }
                
                if (!tokenValue || !tokenValue.trim()) {
                    errors.push(nabezky_admin.i18n.accessTokenRequired);
                }
                
                // Check if at least one product is selected
                if (!$('.nabezky-products-fieldset input[type="checkbox"]:checked').length) {
                    errors.push(nabezky_admin.i18n.productSelectionRequired);
                }
                
                // Check if API URL is valid
                var apiUrl = $('#nabezky_api_url').val().trim();
                if (!apiUrl || !isValidUrl(apiUrl)) {
                    errors.push(nabezky_admin.i18n.validApiUrlRequired);
                }
                
                // Check if Map URL is valid
                var mapUrl = $('#nabezky_map_url').val().trim();
                if (!mapUrl || !isValidUrl(mapUrl)) {
                    errors.push(nabezky_admin.i18n.validMapUrlRequired);
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                alert(nabezky_admin.i18n.fixErrors + '\n\n' + errors.join('\n'));
                return false;
            }
        });
        
        // URL validation helper
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        // Auto-generate callback URL when API URL changes
        $('#nabezky_api_url').on('change', function() {
            var apiUrl = $(this).val().trim();
            if (apiUrl) {
                // This is a placeholder - in reality, the callback URL should be the WordPress site URL
                var callbackUrl = window.location.origin + '/wp-json/wp-nabezky-connector/v1/callback';
                $('#nabezky_callback_url').val(callbackUrl);
            }
        });
    }
});