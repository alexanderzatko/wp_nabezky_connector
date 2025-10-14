<?php
/**
 * Admin page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
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
    
    <form method="post" action="">
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
                    <input type="password" 
                           id="nabezky_access_token" 
                           name="nabezky_access_token" 
                           value="<?php echo esc_attr($options['nabezky_access_token'] ?? ''); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('Access token for authenticating with Nabezky API (communicated out-of-band)', 'wp-nabezky-connector'); ?></p>
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
        </table>
        
        <?php submit_button(__('Save Settings', 'wp-nabezky-connector')); ?>
    </form>
    
    <hr>
    
    <div class="wp-nabezky-connector-info">
        <h2><?php _e('Plugin Information', 'wp-nabezky-connector'); ?></h2>
        
        <div class="card">
            <h3><?php _e('How it works', 'wp-nabezky-connector'); ?></h3>
            <ol>
                <li><?php _e('Customer completes a WooCommerce order containing configured Nabezky products', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Plugin sends order details to Nabezky API for voucher generation', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Nabezky generates vouchers and sends callback with voucher information', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Customer receives voucher information via email and on the thank you page', 'wp-nabezky-connector'); ?></li>
                <li><?php _e('Customer can use voucher numbers to access the Nabezky interactive map', 'wp-nabezky-connector'); ?></li>
            </ol>
        </div>
        
        <div class="card">
            <h3><?php _e('Voucher Types', 'wp-nabezky-connector'); ?></h3>
            <ul>
                <li><strong><?php _e('Seasonal Vouchers:', 'wp-nabezky-connector'); ?></strong> <?php _e('Generated when order value >= Season Pass Price. Valid until end of ski season.', 'wp-nabezky-connector'); ?></li>
                <li><strong><?php _e('3-Day Vouchers:', 'wp-nabezky-connector'); ?></strong> <?php _e('Generated when order value < Season Pass Price. Valid for 3 days from generation.', 'wp-nabezky-connector'); ?></li>
                <li><strong><?php _e('Registered User Access:', 'wp-nabezky-connector'); ?></strong> <?php _e('If customer is registered in Nabezky, they get access to all premium features.', 'wp-nabezky-connector'); ?></li>
            </ul>
        </div>
        
        
        <div class="card">
            <h3><?php _e('Map Access Options', 'wp-nabezky-connector'); ?></h3>
            <p><?php _e('Configure where customers will access the Nabezky map:', 'wp-nabezky-connector'); ?></p>
            <ul>
                <li><strong><?php _e('Direct Access:', 'wp-nabezky-connector'); ?></strong> <?php _e('Link to mapa.nabezky.sk with voucher parameters', 'wp-nabezky-connector'); ?></li>
                <li><strong><?php _e('Embedded Access:', 'wp-nabezky-connector'); ?></strong> <?php _e('Link to your own embedded iframe with proper authentication', 'wp-nabezky-connector'); ?></li>
            </ul>
        </div>
    </div>
</div>
