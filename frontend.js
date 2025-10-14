jQuery(document).ready(function($) {
    'use strict';
    
    // Frontend functionality for voucher status checking
    function checkNabezkyStatus(orderId) {
        if (!orderId) return;
        
        var checkInterval = setInterval(function() {
            $.ajax({
                url: nabezky_ajax.ajax_url,
                method: 'GET',
                data: {
                    action: 'nabezky_check_status',
                    order_id: orderId,
                    nonce: nabezky_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.voucher_data) {
                        // Update the status display with voucher information
                        $('#nabezky-status-check').html(response.data.html);
                        clearInterval(checkInterval);
                    } else if (response.data && response.data.timeout) {
                        // Stop checking after timeout
                        $('#nabezky-status-check').html(
                            '<p><em>' + nabezky_frontend.i18n.processingLongerExpected + '</em></p>'
                        );
                        clearInterval(checkInterval);
                    }
                },
                error: function() {
                    // Continue checking on error
                }
            });
        }, 5000); // Check every 5 seconds
        
        // Stop checking after 2 minutes
        setTimeout(function() {
            clearInterval(checkInterval);
            if ($('#nabezky-status-check p').length > 0) {
                $('#nabezky-status-check').html(
                    '<p><em>' + nabezky_frontend.i18n.processingLongerExpected + '</em></p>'
                );
            }
        }, 120000);
    }
    
    // Initialize status checking if we're on a thank you page with processing message
    if ($('#nabezky-status-check').length > 0) {
        var orderId = $('#nabezky-status-check').data('order-id');
        checkNabezkyStatus(orderId);
    }
    
    // Handle voucher activation links
    $('.nabezky-map-link, .activate-button').on('click', function(e) {
        var $link = $(this);
        var href = $link.attr('href');
        
        // If it's an external link, open in new tab
        if (href && href.indexOf(window.location.origin) === -1) {
            e.preventDefault();
            window.open(href, '_blank');
        }
    });
    
    // Add loading state to activation buttons
    $('.activate-button').on('click', function() {
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true)
               .text(nabezky_frontend.i18n.opening)
               .addClass('loading');
        
        // Reset button state after 3 seconds (in case of issues)
        setTimeout(function() {
            $button.prop('disabled', false)
                   .text(originalText)
                   .removeClass('loading');
        }, 3000);
    });
    
    // Smooth scroll to voucher info when activated via shortcode
    if (window.location.hash === '#nabezky-voucher-info') {
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('.nabezky-voucher-info').offset().top - 100
            }, 800);
        }, 500);
    }
    
    // Copy voucher number to clipboard functionality
    $('.nabezky-voucher-info').on('click', '.voucher-number', function() {
        var voucherNumber = $(this).text().trim();
        
        if (navigator.clipboard && window.isSecureContext) {
            // Modern clipboard API
            navigator.clipboard.writeText(voucherNumber).then(function() {
                showCopySuccess($(this));
            }.bind(this));
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = voucherNumber;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showCopySuccess($(this));
            } catch (err) {
                console.log('Unable to copy to clipboard');
            }
            document.body.removeChild(textArea);
        }
    });
    
    function showCopySuccess($element) {
        var $original = $element.clone();
        var originalText = $element.text();
        
        $element.text(nabezky_frontend.i18n.copied)
               .css('color', '#46b450')
               .css('font-weight', 'bold');
        
        setTimeout(function() {
            $element.text(originalText)
                   .css('color', '')
                   .css('font-weight', '');
        }, 2000);
    }
    
    // Add copy hint to voucher numbers
    $('.nabezky-voucher-info .voucher-number').each(function() {
        $(this).css('cursor', 'pointer')
               .attr('title', nabezky_frontend.i18n.clickToCopy)
               .after('<small style="color: #666; font-size: 12px; margin-left: 5px;">(' + nabezky_frontend.i18n.clickToCopy + ')</small>');
    });
});

// AJAX handler for status checking (this would be added to WordPress via wp_ajax)
// This is included here for reference - actual implementation would be in the PHP plugin file
/*
function nabezky_ajax_check_status() {
    // Verify nonce
    if (!wp_verify_nonce($_GET['nonce'], 'nabezky_check_status')) {
        wp_die('Security check failed');
    }
    
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_send_json_error(array('message' => 'Order not found'));
    }
    
    $voucher_data = $order->get_meta('_nabezky_vouchers');
    
    if (!empty($voucher_data)) {
        // Render voucher HTML
        ob_start();
        $plugin = WP_Nabezky_Connector::get_instance();
        $plugin->render_voucher_info($voucher_data);
        $html = ob_get_clean();
        
        wp_send_json_success(array(
            'voucher_data' => $voucher_data,
            'html' => $html
        ));
    } else {
        // Check if we should timeout
        $timeout = time() - $order->get_date_created()->getTimestamp();
        if ($timeout > 120) { // 2 minutes
            wp_send_json_success(array('timeout' => true));
        } else {
            wp_send_json_success(array('still_processing' => true));
        }
    }
}
*/