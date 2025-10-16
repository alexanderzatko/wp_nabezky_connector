<?php
/**
 * Admin page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle manual data cleanup
if (isset($_POST['manual_cleanup']) && wp_verify_nonce($_POST['nabezky_cleanup_nonce'], 'nabezky_manual_cleanup')) {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    global $wpdb;
    $cleanup_order_data = isset($_POST['cleanup_order_data']);
    
    // Remove database table
    $table_name = $wpdb->prefix . 'nabezky_connector';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    // Remove plugin options (but keep current settings for the form)
    // delete_option('wp_nabezky_connector_options'); // Commented out to preserve settings
    
    // Remove order metadata if requested
    if ($cleanup_order_data) {
        $deleted_vouchers = $wpdb->delete($wpdb->postmeta, array('meta_key' => '_nabezky_vouchers'), array('%s'));
        $deleted_processed = $wpdb->delete($wpdb->postmeta, array('meta_key' => '_nabezky_processed'), array('%s'));
        $deleted_request_ids = $wpdb->delete($wpdb->postmeta, array('meta_key' => '_nabezky_request_id'), array('%s'));
        
        echo '<div class="notice notice-success"><p>' . 
             sprintf(__('Manual cleanup completed. Removed: %d voucher records, %d processed records, %d request ID records.', 'wp-nabezky-connector'), 
                     $deleted_vouchers, $deleted_processed, $deleted_request_ids) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>' . 
             __('Manual cleanup completed. Database table removed, order metadata preserved.', 'wp-nabezky-connector') . '</p></div>';
    }
    
    // Remove transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wp_nabezky_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wp_nabezky_%'");
}

// Handle test order completion
if (isset($_POST['test_order_completion']) && !empty($_POST['test_order_id'])) {
    $test_order_id = intval($_POST['test_order_id']);
    $order = wc_get_order($test_order_id);
    
    if ($order) {
        // Manually trigger the order completion handler
        $plugin = WP_Nabezky_Connector::get_instance();
        $plugin->handle_order_completion($test_order_id);
        echo '<div class="notice notice-success"><p>' . sprintf(__('Test order completion triggered for order #%d. Check debug logs.', 'wp-nabezky-connector'), $test_order_id) . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . sprintf(__('Order #%d not found.', 'wp-nabezky-connector'), $test_order_id) . '</p></div>';
    }
}

// Handle form submission
if (isset($_POST['submit'])) {
    $options = array(
        'nabezky_api_url' => sanitize_text_field($_POST['nabezky_api_url'] ?? ''),
        'nabezky_map_url' => sanitize_text_field($_POST['nabezky_map_url'] ?? ''),
        'nabezky_access_token' => sanitize_text_field($_POST['nabezky_access_token'] ?? ''),
        'nabezky_products' => array_map('intval', $_POST['nabezky_products'] ?? array()),
        'default_region_id' => intval($_POST['default_region_id'] ?? 1),
        'enabled' => isset($_POST['enabled']) ? 1 : 0,
        'remove_data_on_uninstall' => isset($_POST['remove_data_on_uninstall']) ? 1 : 0,
    );
    
    update_option('wp_nabezky_connector_options', $options);
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wp-nabezky-connector') . '</p></div>';
}

// Get plugin options
$options = get_option('wp_nabezky_connector_options', array());

// Get WooCommerce products for selection
$products = array();
if (class_exists('WooCommerce')) {
    $products = wc_get_products(array(
        'status' => 'publish',
        'limit' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?> <span style="font-size: 14px; color: #666; font-weight: normal;">v<?php echo WP_NABEZKY_CONNECTOR_VERSION; ?></span></h1>
    
    <form method="post" action="" autocomplete="off" data-form-type="other">
        <!-- Hidden field to prevent browser password detection -->
        <input type="text" name="fake_username" style="display:none" tabindex="-1" autocomplete="off">
        <input type="password" name="fake_password" style="display:none" tabindex="-1" autocomplete="off">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="nabezky_api_url"><?php _e('Nabezky API URL', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="nabezky_api_url" 
                           name="nabezky_api_url" 
                           value="<?php echo esc_attr($options['nabezky_api_url'] ?? 'https://nabezky.sk'); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Base URL for the Nabezky API (e.g., https://nabezky.sk)', 'wp-nabezky-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="nabezky_map_url"><?php _e('Nabezky Map URL', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="url" 
                           id="nabezky_map_url" 
                           name="nabezky_map_url" 
                           value="<?php echo esc_attr($options['nabezky_map_url'] ?? 'https://mapa.nabezky.sk'); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('URL where customers will access the map (can be mapa.nabezky.sk or embedded iframe URL)', 'wp-nabezky-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="nabezky_access_token"><?php _e('Access Token', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="text" 
                           id="nabezky_access_token" 
                           name="nabezky_access_token" 
                           value="<?php echo esc_attr($options['nabezky_access_token'] ?? ''); ?>" 
                           class="regular-text"
                           autocomplete="off"
                           data-form-type="other" 
                           placeholder="<?php _e('Enter access token', 'wp-nabezky-connector'); ?>" />
                    <button type="button" id="toggle-token-visibility" class="button">
                        <?php _e('Show', 'wp-nabezky-connector'); ?>
                    </button>
                    <p class="description"><?php _e('Access token for authenticating with Nabezky API', 'wp-nabezky-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="nabezky_products"><?php _e('Select Products', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <?php if (!empty($products)): ?>
                        <fieldset class="nabezky-products-fieldset">
                            <legend class="screen-reader-text"><?php _e('Select products that trigger Nabezky integration', 'wp-nabezky-connector'); ?></legend>
                            <?php foreach ($products as $product): ?>
                                <label>
                                    <input type="checkbox" 
                                           name="nabezky_products[]" 
                                           value="<?php echo esc_attr($product->get_id()); ?>"
                                           <?php checked(in_array($product->get_id(), $options['nabezky_products'] ?? array())); ?> />
                                    <?php echo esc_html($product->get_name()); ?>
                                    <span class="description">(ID: <?php echo esc_html($product->get_id()); ?>)</span>
                                </label><br>
                            <?php endforeach; ?>
                        </fieldset>
                        <p class="description"><?php _e('Select WooCommerce products that should trigger Nabezky voucher generation when purchased', 'wp-nabezky-connector'); ?></p>
                    <?php else: ?>
                        <p><?php _e('No WooCommerce products found. Please create some products first.', 'wp-nabezky-connector'); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_region_id"><?php _e('Region ID', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="number" 
                           id="default_region_id" 
                           name="default_region_id" 
                           value="<?php echo esc_attr($options['default_region_id'] ?? 1); ?>" 
                           min="1" 
                           max="99" 
                           class="small-text" />
                    <p class="description"><?php _e('Region ID for voucher generation', 'wp-nabezky-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="enabled"><?php _e('Enable Plugin', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="enabled" 
                           name="enabled" 
                           value="1" 
                           <?php checked($options['enabled'] ?? false, 1); ?> />
                    <label for="enabled"><?php _e('Enable the Nabezky connector', 'wp-nabezky-connector'); ?></label>
                    <p class="description"><?php _e('Enable automatic voucher generation when WooCommerce orders are completed', 'wp-nabezky-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="remove_data_on_uninstall"><?php _e('Data Retention', 'wp-nabezky-connector'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                           id="remove_data_on_uninstall" 
                           name="remove_data_on_uninstall" 
                           value="1" 
                           <?php checked($options['remove_data_on_uninstall'] ?? false, 1); ?> />
                    <label for="remove_data_on_uninstall"><?php _e('Remove all plugin data when uninstalling', 'wp-nabezky-connector'); ?></label>
                    <p class="description">
                        <?php _e('When enabled: All plugin data (vouchers, order metadata, database tables) will be permanently deleted when the plugin is uninstalled.', 'wp-nabezky-connector'); ?><br>
                        <?php _e('When disabled (default): Plugin files will be removed but order voucher data will be preserved for historical records and customer support.', 'wp-nabezky-connector'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'wp-nabezky-connector')); ?>
    </form>
    
    <hr>
    
    <div class="wp-nabezky-connector-info">
        <h2><?php _e('Plugin Information', 'wp-nabezky-connector'); ?></h2>
        
        <div class="card">
            <h3><?php _e('How it works', 'wp-nabezky-connector'); ?></h3>
            <ol>
                <li><?php _e('Customer completes a WooCommerce order containing configured products marked on plugin settings page', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Plugin sends order details to Nabezky API for voucher generation', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Nabezky generates vouchers and sends voucher numbers back to the plugin', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Customer receives voucher information via email and on the thank you page', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Customer can use voucher numbers to access the Nabezky interactive map', 'wp-nabezky-connector'); ?></li>
            </ol>
        </div>
        
        <div class="card">
            <h3><?php _e('Voucher Types', 'wp-nabezky-connector'); ?></h3>
            <ul>
                <li><strong><?php _e('Seasonal Vouchers:', 'wp-nabezky-connector'); ?></strong> <?php _e('Generated when order value >= Season Pass Price. Valid until end of ski season.', 'wp-nabezky-connector'); ?></li>
                <li><strong><?php _e('3-Day Vouchers:', 'wp-nabezky-connector'); ?></strong> <?php _e('Generated when order value < Season Pass Price. Valid for 3 days from first use.', 'wp-nabezky-connector'); ?></li>
                <li><strong><?php _e('Registered User Access:', 'wp-nabezky-connector'); ?></strong> <?php _e('If customer is registered in Nabezky, they get access to all premium features.', 'wp-nabezky-connector'); ?></li>
            </ul>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <h2><?php _e('Plugin Management', 'wp-nabezky-connector'); ?></h2>
        
        <div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
            <h3><?php _e('Manual Data Cleanup', 'wp-nabezky-connector'); ?></h3>
            <p><?php _e('You can manually remove plugin data without uninstalling the plugin. This is useful for testing or if you want to clean up old data while keeping the plugin active.', 'wp-nabezky-connector'); ?></p>
            <form method="post" action="" onsubmit="return confirm('<?php _e('Are you sure you want to remove all plugin data? This action cannot be undone.', 'wp-nabezky-connector'); ?>')">
                <?php wp_nonce_field('nabezky_manual_cleanup', 'nabezky_cleanup_nonce'); ?>
                <label>
                    <input type="checkbox" name="cleanup_order_data" value="1" />
                    <?php _e('Also remove order metadata (voucher data)', 'wp-nabezky-connector'); ?>
                </label>
                <br><br>
                <button type="submit" name="manual_cleanup" class="button button-secondary" style="background: #dc3545; color: white; border-color: #dc3545;">
                    <?php _e('Clean Up Plugin Data', 'wp-nabezky-connector'); ?>
                </button>
            </form>
        </div>
        
    </div>
</div>
