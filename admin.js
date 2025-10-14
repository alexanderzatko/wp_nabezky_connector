jQuery(document).ready(function($) {
    'use strict';
    
    // Admin page functionality
    if ($('body').hasClass('settings_page_wp-nabezky-connector')) {
        
        // Token visibility toggle
        $('#nabezky_access_token').after('<button type="button" id="toggle-token-visibility" class="button">' + nabezky_admin.i18n.show + '</button>');
        
        $('#toggle-token-visibility').on('click', function() {
            var $tokenField = $('#nabezky_access_token');
            var $button = $(this);
            
            if ($tokenField.attr('type') === 'password') {
                $tokenField.attr('type', 'text');
                $button.text(nabezky_admin.i18n.hide);
            } else {
                $tokenField.attr('type', 'password');
                $button.text(nabezky_admin.i18n.show);
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
        
        // Test connection button (placeholder for future implementation)
        $('#nabezky_api_url').after('<button type="button" id="test-connection" class="button">' + nabezky_admin.i18n.testConnection + '</button>');
        
        $('#test-connection').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            
            $button.prop('disabled', true).text(nabezky_admin.i18n.testing);
            
            // Simulate test (replace with actual API test)
            setTimeout(function() {
                $button.prop('disabled', false).text(originalText);
                alert(nabezky_admin.i18n.connectionTestNotImplemented);
            }, 2000);
        });
        
        // Form validation
        $('form').on('submit', function(e) {
            var errors = [];
            
            // Check if plugin is enabled
            if ($('#enabled').is(':checked')) {
                // Check if access token is provided
                if (!$('#nabezky_access_token').val().trim()) {
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