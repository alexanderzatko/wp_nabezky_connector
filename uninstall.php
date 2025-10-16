<?php
/**
 * Uninstall script for WP Na Bežky Connector
 * 
 * This file is executed when the plugin is uninstalled.
 * It removes all plugin data from the database.
 * 
 * @package WP_Nabezky_Connector
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Only proceed if we're actually uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if we're uninstalling the correct plugin
$plugin_file = WP_UNINSTALL_PLUGIN;
if (strpos($plugin_file, 'wp-nabezky-connector.php') === false) {
    exit;
}

global $wpdb;

/**
 * Clean up plugin data
 */
function wp_nabezky_connector_uninstall_cleanup() {
    global $wpdb;
    
    // Log the uninstall process
    error_log('WP Nabezky Connector: Starting uninstall cleanup process');
    
    // Check if admin wants to preserve order data (default: preserve data)
    $options = get_option('wp_nabezky_connector_options', array());
    $remove_order_data = isset($options['remove_data_on_uninstall']) ? $options['remove_data_on_uninstall'] : false;
    
    if (!$remove_order_data) {
        error_log('WP Nabezky Connector: Data retention enabled - preserving order metadata');
    } else {
        error_log('WP Nabezky Connector: Data removal enabled - will remove all plugin data');
    }
    
    // 1. Drop the custom database table
    $table_name = $wpdb->prefix . 'nabezky_connector';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    
    error_log('WP Nabezky Connector: Dropped database table: ' . $table_name);
    
    // 2. Remove plugin options
    delete_option('wp_nabezky_connector_options');
    delete_option('wp_nabezky_connector_version'); // In case version option exists
    
    error_log('WP Nabezky Connector: Removed plugin options');
    
    // 3. Remove order meta data related to Nabezky (only if admin chose to remove data)
    if ($remove_order_data) {
        $deleted_vouchers = $wpdb->delete(
            $wpdb->postmeta,
            array(
                'meta_key' => '_nabezky_vouchers'
            ),
            array('%s')
        );
        
        $deleted_processed = $wpdb->delete(
            $wpdb->postmeta,
            array(
                'meta_key' => '_nabezky_processed'
            ),
            array('%s')
        );
        
        $deleted_request_ids = $wpdb->delete(
            $wpdb->postmeta,
            array(
                'meta_key' => '_nabezky_request_id'
            ),
            array('%s')
        );
        
        error_log('WP Nabezky Connector: Removed order meta data - Vouchers: ' . $deleted_vouchers . ', Processed: ' . $deleted_processed . ', Request IDs: ' . $deleted_request_ids);
    } else {
        error_log('WP Nabezky Connector: Preserving order meta data as requested by admin');
    }
    
    // 4. Remove any transients related to the plugin
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wp_nabezky_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wp_nabezky_%'");
    
    error_log('WP Nabezky Connector: Removed plugin transients');
    
    // 5. Remove any scheduled cron jobs (if any were set up)
    wp_clear_scheduled_hook('wp_nabezky_connector_cleanup');
    wp_clear_scheduled_hook('wp_nabezky_connector_sync');
    
    error_log('WP Nabezky Connector: Cleared scheduled hooks');
    
    // 6. Clean up any user meta if the plugin stored user-specific data
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key' => 'nabezky_user_preferences'
        ),
        array('%s')
    );
    
    error_log('WP Nabezky Connector: Removed user meta data');
    
    // 7. Remove any custom rewrite rules (flush them)
    flush_rewrite_rules();
    
    error_log('WP Nabezky Connector: Flushed rewrite rules');
    
    // 8. Log completion
    error_log('WP Nabezky Connector: Uninstall cleanup completed successfully');
}

/**
 * Additional cleanup for multisite installations
 */
function wp_nabezky_connector_multisite_cleanup() {
    if (is_multisite()) {
        global $wpdb;
        
        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            wp_nabezky_connector_uninstall_cleanup();
            restore_current_blog();
        }
        
        // Also clean up network-wide options
        delete_site_option('wp_nabezky_connector_network_options');
        
        error_log('WP Nabezky Connector: Completed multisite cleanup');
    }
}

// Execute the cleanup
if (is_multisite()) {
    wp_nabezky_connector_multisite_cleanup();
} else {
    wp_nabezky_connector_uninstall_cleanup();
}

// Final log entry
error_log('WP Nabezky Connector: Plugin uninstalled and all data cleaned up');

// Optional: Send notification to admin about uninstall
$admin_email = get_option('admin_email');
if ($admin_email) {
    wp_mail(
        $admin_email,
        'WP Na Bežky Connector - Plugin Uninstalled',
        'The WP Na Bežky Connector plugin has been uninstalled and all associated data has been cleaned up from the database.',
        array('Content-Type: text/html; charset=UTF-8')
    );
}
