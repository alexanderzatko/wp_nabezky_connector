<?php
/**
 * Plugin Name: WP Na bezky! Connector
 * Plugin URI: https://nabezky.sk
 * Description: A WordPress plugin to integrate WooCommerce with Nabezky trail map system
 * Version: 1.0.0
 * Author: Na bezky!
 * Author URI: https://nabezky.sk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-nabezky-connector
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_NABEZKY_CONNECTOR_VERSION', '1.1.0');
define('WP_NABEZKY_CONNECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_NABEZKY_CONNECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_NABEZKY_CONNECTOR_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class WP_Nabezky_Connector {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Include required files
        $this->include_files();
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Include required files
     */
    private function include_files() {
        require_once WP_NABEZKY_CONNECTOR_PLUGIN_DIR . 'includes/class-nabezky-api.php';
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        $this->log_info('Plugin initialization started', 'Version: ' . WP_NABEZKY_CONNECTOR_VERSION);
        
        // Load text domain for translations
        load_plugin_textdomain('wp-nabezky-connector', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        
        // Initialize admin functionality
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize frontend functionality
        $this->init_frontend();
        
        // Initialize REST API endpoints
        $this->init_rest_api();
        
        
        $this->log_info('Plugin initialization completed', 'All systems initialized');
    }
    
    /**
     * Initialize admin functionality
     */
    private function init_admin() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        
        // Add AJAX handlers for connection testing
        add_action('wp_ajax_test_nabezky_connection', array($this, 'ajax_test_connection'));
    }
    
    /**
     * Initialize frontend functionality
     */
    private function init_frontend() {
        // Add frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // Add WooCommerce hooks
        if (class_exists('WooCommerce')) {
            $this->log_info('WooCommerce detected, registering hooks', 'Hook registration starting');
            
            // Register for order completion - use only one hook to prevent double processing
            add_action('woocommerce_order_status_completed', array($this, 'handle_order_completion'));
            add_action('woocommerce_order_status_processing', array($this, 'handle_order_completion'));
            add_action('woocommerce_thankyou', array($this, 'display_voucher_info'), 10, 1);
            
            // Add a general order status change hook for debugging
            add_action('woocommerce_order_status_changed', array($this, 'log_order_status_change'), 10, 3);
            
            $this->log_info('WooCommerce hooks registered successfully', 'Multiple order completion handlers attached');
        } else {
            $this->log_error('WooCommerce not detected', 'Cannot register order completion hooks');
        }
        
        // Add shortcodes
        add_shortcode('nabezky_voucher_info', array($this, 'voucher_info_shortcode'));
        add_shortcode('nabezky_map_link', array($this, 'map_link_shortcode'));
    }
    
    /**
     * Initialize REST API endpoints
     */
    private function init_rest_api() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('WP Na bezky! Connector', 'wp-nabezky-connector'),
            __('Na bezky! Connector', 'wp-nabezky-connector'),
            'manage_options',
            'wp-nabezky-connector',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        include WP_NABEZKY_CONNECTOR_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=wp-nabezky-connector') . '">' . __('Settings', 'wp-nabezky-connector') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_wp-nabezky-connector' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'wp-nabezky-connector-admin',
            WP_NABEZKY_CONNECTOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_NABEZKY_CONNECTOR_VERSION
        );
        
        wp_enqueue_script(
            'wp-nabezky-connector-admin',
            WP_NABEZKY_CONNECTOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WP_NABEZKY_CONNECTOR_VERSION,
            true
        );
        
        // Localize admin script
        wp_localize_script('wp-nabezky-connector-admin', 'nabezky_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('test_nabezky_connection'),
            'i18n' => array(
                'show' => __('Show', 'wp-nabezky-connector'),
                'hide' => __('Hide', 'wp-nabezky-connector'),
                'selectAll' => __('Select All', 'wp-nabezky-connector'),
                'deselectAll' => __('Deselect All', 'wp-nabezky-connector'),
                'testConnection' => __('Test Connection', 'wp-nabezky-connector'),
                'testing' => __('Testing...', 'wp-nabezky-connector'),
                'connectionTestNotImplemented' => __('Connection test not yet implemented. This will test the API endpoint in a future version.', 'wp-nabezky-connector'),
                'accessTokenRequired' => __('Access token is required when plugin is enabled.', 'wp-nabezky-connector'),
                'productSelectionRequired' => __('At least one product must be selected when plugin is enabled.', 'wp-nabezky-connector'),
                'validApiUrlRequired' => __('Valid API URL is required.', 'wp-nabezky-connector'),
                'validMapUrlRequired' => __('Valid Map URL is required.', 'wp-nabezky-connector'),
                'fixErrors' => __('Please fix the following errors:', 'wp-nabezky-connector'),
                'testSuccess' => __('Connection test successful!', 'wp-nabezky-connector'),
                'testFailed' => __('Connection test failed!', 'wp-nabezky-connector'),
                'testError' => __('Error occurred during connection test.', 'wp-nabezky-connector'),
                'testResults' => __('Test Results:', 'wp-nabezky-connector'),
                'responseTime' => __('Response Time:', 'wp-nabezky-connector'),
                'statusCode' => __('Status Code:', 'wp-nabezky-connector'),
                'authentication' => __('Authentication:', 'wp-nabezky-connector'),
                'endpoint' => __('Endpoint:', 'wp-nabezky-connector'),
                'details' => __('Details:', 'wp-nabezky-connector'),
                'close' => __('Close', 'wp-nabezky-connector'),
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'wp-nabezky-connector-frontend',
            WP_NABEZKY_CONNECTOR_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WP_NABEZKY_CONNECTOR_VERSION
        );
        
        wp_enqueue_script(
            'wp-nabezky-connector-frontend',
            WP_NABEZKY_CONNECTOR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            WP_NABEZKY_CONNECTOR_VERSION,
            true
        );
        
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nabezky_connector';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            nabezky_request_id varchar(255),
            status varchar(50) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            data text,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $default_options = array(
            'nabezky_api_url' => 'https://nabezky.sk',
            'nabezky_map_url' => 'https://mapa.nabezky.sk',
            'nabezky_access_token' => '',
            'nabezky_callback_url' => home_url('/wp-json/wp-nabezky-connector/v1/callback'),
            'nabezky_products' => array(),
            'season_pass_price' => 20,
            'default_region_id' => 1,
            'enabled' => false,
        );
        
        add_option('wp_nabezky_connector_options', $default_options);
    }
    
    /**
     * Log order status changes for debugging
     */
    public function log_order_status_change($order_id, $old_status, $new_status) {
        $this->log_info('Order status changed', "Order ID: $order_id, From: $old_status, To: $new_status");
        
        // Check if this order has Nabezky products
        $order = wc_get_order($order_id);
        if ($order) {
            $nabezky_products = $this->get_nabezky_products_from_order($order);
            if (!empty($nabezky_products['items'])) {
                $this->log_info('Order with Nabezky products status changed', "Order ID: $order_id, Status: $new_status, Products found: " . count($nabezky_products['items']));
            }
        }
    }
    
    /**
     * Handle WooCommerce order completion
     */
    public function handle_order_completion($order_id) {
        $this->log_info('Order completion triggered', 'Order ID: ' . $order_id);
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            $this->log_error('Order not found', 'Order ID: ' . $order_id);
            return;
        }
        
        // Check if order has already been processed for Nabezky
        $already_processed = $order->get_meta('_nabezky_processed');
        if ($already_processed) {
            $this->log_info('Order already processed', 'Order ID: ' . $order_id . ' - Skipping to prevent duplicate processing');
            return;
        }
        
        // Mark order as being processed
        $order->update_meta_data('_nabezky_processed', current_time('mysql'));
        $order->save();
        
        // Get plugin options
        $options = get_option('wp_nabezky_connector_options');
        
        $this->log_info('Plugin status check', 'Enabled: ' . ($options['enabled'] ? 'YES' : 'NO'));
        
        // Check if plugin is enabled
        if (!$options['enabled']) {
            $this->log_info('Plugin disabled', 'Skipping Nabezky processing');
            return;
        }
        
        // Check if order contains configured Nabezky products
        $nabezky_products = $this->get_nabezky_products_from_order($order);
        
        $this->log_info('Nabezky products check', 'Found products: ' . (empty($nabezky_products['items']) ? 'NO' : 'YES'));
        
        if (empty($nabezky_products['items'])) {
            $this->log_info('No Nabezky products', 'Skipping Nabezky processing');
            return;
        }
        
        $this->log_info('Processing order for Nabezky', 'Order ID: ' . $order_id);
        
        // Process order for Nabezky integration
        $this->process_order_for_nabezky($order, $nabezky_products);
    }
    
    /**
     * Get Nabezky products from order
     */
    private function get_nabezky_products_from_order($order) {
        $options = get_option('wp_nabezky_connector_options');
        $configured_products = $options['nabezky_products'] ?? array();
        
        $this->log_info('Product configuration check', 'Configured products: ' . json_encode($configured_products));
        
        if (empty($configured_products)) {
            $this->log_info('No configured products', 'No Nabezky products configured');
            return array();
        }
        
        $nabezky_items = array();
        $total_price = 0;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $product_id = $product->get_id();
            
            $this->log_info('Checking product', 'Product ID: ' . $product_id . ', Name: ' . $product->get_name());
            
            if (in_array($product_id, $configured_products)) {
                $nabezky_items[] = array(
                    'product_id' => $product_id,
                    'product_name' => $product->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                );
                $total_price += $item->get_total();
                $this->log_info('Nabezky product found', 'Product ID: ' . $product_id . ', Total: ' . $item->get_total());
            }
        }
        
        $result = array(
            'items' => $nabezky_items,
            'total_price' => $total_price
        );
        
        $this->log_info('Product detection result', 'Found items: ' . count($nabezky_items) . ', Total price: ' . $total_price);
        
        return $result;
    }
    
    /**
     * Process order for Nabezky integration
     */
    private function process_order_for_nabezky($order, $nabezky_products) {
        $options = get_option('wp_nabezky_connector_options');
        
        // Prepare order data for Nabezky API
        $order_data = array(
            'order_id' => $order->get_id(),
            'email' => $order->get_billing_email(),
            'amount' => $nabezky_products['total_price'],
            'products' => $nabezky_products['items'],
            'wp_site_url' => home_url(),
            'callback_url' => home_url('/wp-json/wp-nabezky-connector/v1/callback'),
            'order_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'region_id' => $options['default_region_id'] ?? 1,
        );
        
        // Send request to Nabezky API
        $this->send_nabezky_request($order_data);
        
        // Store request in database for tracking
        $this->store_nabezky_request($order->get_id(), $order_data);
    }
    
    /**
     * Send request to Nabezky API
     */
    private function send_nabezky_request($order_data) {
        $options = get_option('wp_nabezky_connector_options');
        
        // Enhanced logging for debugging
        $this->log_info('Starting Nabezky API request', 'Order ID: ' . $order_data['order_id']);
        $this->log_info('API Configuration', 'URL: ' . $options['nabezky_api_url'] . ', Token: ' . (empty($options['nabezky_access_token']) ? 'EMPTY' : 'SET'));
        
        // Check if plugin is enabled and configured
        if (!$options['enabled']) {
            $this->log_error('Plugin is disabled', 'Cannot send API request');
            return;
        }
        
        if (empty($options['nabezky_access_token'])) {
            $this->log_error('Access token is empty', 'Cannot send API request');
            $this->handle_api_failure($order_data);
            return;
        }
        
        $api_url = rtrim($options['nabezky_api_url'], '/');
        $endpoint = $api_url . '/nabezky/woocommerce-voucher-generation';
        
        $request_data = array(
            'access_token' => $options['nabezky_access_token'],
            'order_data' => $order_data
        );
        
        $this->log_info('API Request Details', 'Endpoint: ' . $endpoint . ', Data: ' . json_encode($request_data));
        
        // Log detailed order data analysis
        $this->log_info('Order Data Analysis', 'Order ID: ' . $order_data['order_id'] . ', Email: ' . $order_data['email'] . ', Amount: ' . $order_data['amount']);
        $this->log_info('Products Analysis', 'Product count: ' . count($order_data['products']) . ', Details: ' . json_encode($order_data['products']));
        $this->log_info('Site Info', 'WP Site URL: ' . $order_data['wp_site_url'] . ', Callback URL: ' . $order_data['callback_url']);
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            $this->log_error('Nabezky API request failed', $response->get_error_message());
            $this->handle_api_failure($order_data);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->log_info('API Response', "Code: $response_code, Body: $response_body");
        
        if ($response_code !== 200) {
            $this->log_error('Nabezky API returned error', "Code: $response_code, Body: $response_body");
            $this->handle_api_failure($order_data);
            return;
        }
        
        // Parse and analyze the response
        $response_data = json_decode($response_body, true);
        if ($response_data) {
            $this->log_info('API Response Analysis', 'JSON format detected - Parsed response: ' . json_encode($response_data));
        } else {
            $this->log_error('Response parsing failed', 'Could not parse response as JSON. Raw response: ' . $response_body);
            return;
        }
        
        if ($response_data && isset($response_data['voucher_data'])) {
            $voucher_data = $response_data['voucher_data'];
            $this->log_info('Voucher Data Analysis', json_encode($voucher_data));
            
            // Check for empty vouchers
            if (isset($voucher_data['vouchers']) && empty($voucher_data['vouchers'])) {
                $this->log_error('Empty Vouchers Warning', 'API returned success but vouchers array is empty!');
                $this->log_error('User Status', 'is_registered_user: ' . ($voucher_data['is_registered_user'] ?? 'unknown') . ', user_uid: ' . ($voucher_data['user_uid'] ?? 'unknown'));
                $this->log_error('Access Status', 'access_granted: ' . ($voucher_data['access_granted'] ?? 'unknown'));
            }
            
            // Log specific voucher details and process immediately
            if (isset($voucher_data['vouchers']) && !empty($voucher_data['vouchers'])) {
                $this->log_info('Vouchers Generated', 'Count: ' . count($voucher_data['vouchers']) . ', Details: ' . json_encode($voucher_data['vouchers']));
                
                // Process vouchers immediately instead of waiting for callback
                $this->log_info('Processing vouchers immediately', 'Skipping callback dependency');
                $this->process_voucher_data_immediately($order_data, $voucher_data);
            }
        } else {
            $this->log_error('No voucher data found', 'Response structure: ' . json_encode($response_data));
        }
        
        $this->log_info('Nabezky API request successful', $response_body);
    }
    
    /**
     * Process voucher data immediately instead of waiting for callback
     */
    private function process_voucher_data_immediately($order_data, $voucher_data) {
        $this->log_info('Processing vouchers immediately', 'Order ID: ' . $order_data['order_id']);
        
        // Update order meta with voucher data
        $order = wc_get_order($order_data['order_id']);
        if ($order) {
            $order->update_meta_data('_nabezky_vouchers', $voucher_data);
            $order->save();
            $this->log_info('Order meta updated', 'Voucher data saved to order meta');
            
            // Send voucher email to customer
            $this->send_voucher_email($order_data, $voucher_data);
            
            // Update request status
            $this->update_nabezky_request_status($order_data['order_id'], 'completed', $voucher_data);
            
            $this->log_info('Voucher processing completed', 'Order ID: ' . $order_data['order_id']);
        } else {
            $this->log_error('Order not found for voucher processing', 'Order ID: ' . $order_data['order_id']);
        }
    }
    
    /**
     * Handle API failure
     */
    private function handle_api_failure($order_data) {
        // Update request status in database
        $this->update_nabezky_request_status($order_data['order_id'], 'failed');
        
        // Send fallback email to customer
        $this->send_fallback_email($order_data);
    }
    
    /**
     * Send fallback email when API fails
     */
    private function send_fallback_email($order_data) {
        $subject = __('Your Nabezky Map Access', 'wp-nabezky-connector');
        
        $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $message .= '<h2>' . __('Thank you for supporting cross-country skiing!', 'wp-nabezky-connector') . '</h2>';
        $message .= '<p>' . __('Your payment has been processed and you will receive map access information shortly.', 'wp-nabezky-connector') . '</p>';
        $message .= '<p>' . __('If you do not receive your map access details within 24 hours, please contact our support team.', 'wp-nabezky-connector') . '</p>';
        $message .= '</div>';
        
        wp_mail($order_data['email'], $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    /**
     * Send voucher email to customer
     */
    private function send_voucher_email($order_data, $voucher_data) {
        $subject = __('Your Nabezky Map Access', 'wp-nabezky-connector');
        
        $message = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        $message .= '<h2>' . __('Thank you for supporting cross-country skiing!', 'wp-nabezky-connector') . '</h2>';
        
        // Prepare dynamic values for the email template
        $order_number = $order_data['order_id'];
        $eshop_url = $order_data['wp_site_url'];
        $voucher_count = isset($voucher_data['vouchers']) ? count($voucher_data['vouchers']) : 0;
        $voucher_message = ($voucher_count > 1) ? __('The vouchers listed below are valid for 3 days from activation on the map page.', 'wp-nabezky-connector') : '';
        
        $message .= '<p>' . sprintf(
            __('This email contains your Na bežky map access details. As a bonus with your purchase, you now have access to the premium map with cross-country trails grooming status and other useful information.', 'wp-nabezky-connector'),
            $order_number,
            $eshop_url,
            $voucher_message
        ) . '</p>';
        
        // Check if user is registered in Nabezky
        if (isset($voucher_data['is_registered_user']) && $voucher_data['is_registered_user']) {
            $message .= '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 15px 0;">';
            $message .= '<strong>' . __('Registered User Bonus!', 'wp-nabezky-connector') . '</strong><br>';
            $message .= __('As a nabezky.sk registered user, you have gained access to all premium features including the Nabezky map.', 'wp-nabezky-connector');
            $message .= '</div>';
        }
        
        if (isset($voucher_data['vouchers']) && is_array($voucher_data['vouchers'])) {
            $message .= '<h3>' . __('Your Voucher Information', 'wp-nabezky-connector') . '</h3>';
            
            foreach ($voucher_data['vouchers'] as $index => $voucher) {
                $message .= '<div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;">';
                $message .= '<p><strong>' . __('Voucher Number:', 'wp-nabezky-connector') . '</strong> <span style="font-family: monospace; background: #f5f5f5; padding: 5px; border-radius: 3px;">' . esc_html($voucher['number']) . '</span></p>';
                $message .= '<p><strong>' . __('Access Type:', 'wp-nabezky-connector') . '</strong> ' . 
                           ($voucher['type'] === 'seasonal' ? __('Seasonal Pass', 'wp-nabezky-connector') : __('3-Day Access', 'wp-nabezky-connector')) . '</p>';
                
                // Handle expiration display - show '3 days after first use' for NULL expiration dates
                $message .= '<p><strong>' . __('Expires:', 'wp-nabezky-connector') . '</strong> ';
                if (isset($voucher['expires']) && $voucher['expires'] !== null) {
                    $message .= $this->format_localized_date($voucher['expires']);
                } else {
                    $message .= __('3 days after first use', 'wp-nabezky-connector');
                }
                $message .= '</p>';
                
                // Add activation link for each voucher
                $options = get_option('wp_nabezky_connector_options');
                $map_url = $this->build_map_access_url($options['nabezky_map_url'], $voucher['number'], $voucher_data['email']);
                $message .= '<p><a href="' . esc_url($map_url) . '" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-top: 10px;">' . 
                           __('Visit map now', 'wp-nabezky-connector') . '</a></p>';
                
                $message .= '</div>';
            }
        }
        
        $message .= '</div>';
        
        wp_mail($order_data['email'], $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
    
    /**
     * Build map access URL with voucher and email
     */
    private function build_map_access_url($base_url, $voucher_number, $email) {
        // Parse the base URL to extract components
        $parsed_url = parse_url($base_url);
        $scheme = $parsed_url['scheme'] ?? 'https';
        $host = $parsed_url['host'] ?? 'mapa.nabezky.sk';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $path = $parsed_url['path'] ?? '';
        
        // Build the URL using the configured base URL
        $url = $scheme . '://' . $host . $port . $path;
        
        // Add voucher and email parameters
        $params = array(
            'voucher' => $voucher_number,
            'email' => $email
        );
        
        // Add existing query parameters if any
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $existing_params);
            $params = array_merge($existing_params, $params);
        }
        
        $url .= '?' . http_build_query($params);
        
        return $url;
    }
    
    
    
    
    /**
     * Store Nabezky request in database
     */
    private function store_nabezky_request($order_id, $order_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nabezky_connector';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'status' => 'pending',
                'data' => json_encode($order_data),
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * Update Nabezky request status
     */
    private function update_nabezky_request_status($order_id, $status, $data = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nabezky_connector';
        
        $update_data = array('status' => $status);
        if ($data !== null) {
            $update_data['data'] = json_encode($data);
        }
        
        $wpdb->update(
            $table_name,
            $update_data,
            array('order_id' => $order_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Display voucher information on thank you page
     */
    public function display_voucher_info($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Check if order contains configured Nabezky products first
        $nabezky_products = $this->get_nabezky_products_from_order($order);
        
        // Only show voucher info if order contains Nabezky products
        if (empty($nabezky_products['items'])) {
            return; // Don't show anything for non-Nabezky orders
        }
        
        $voucher_data = $order->get_meta('_nabezky_vouchers');
        
        if (empty($voucher_data)) {
            // Show processing message only for orders with Nabezky products
            echo '<div class="nabezky-processing" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 5px;">';
            echo '<h3>' . __('Processing Your Map Access', 'wp-nabezky-connector') . '</h3>';
            echo '<p>' . __('We are processing your Nabezky map access. You will receive an email shortly with your voucher details.', 'wp-nabezky-connector') . '</p>';
            echo '<div id="nabezky-status-check" data-order-id="' . esc_attr($order_id) . '">';
            echo '<p><em>' . __('Checking status...', 'wp-nabezky-connector') . '</em></p>';
            echo '</div>';
            echo '</div>';
            
            return;
        }
        
        // Display voucher information
        $this->render_voucher_info($voucher_data);
    }
    
    /**
     * Render voucher information HTML
     */
    private function render_voucher_info($voucher_data) {
        $options = get_option('wp_nabezky_connector_options');
        $map_url = $options['nabezky_map_url'];
        
        echo '<div class="nabezky-voucher-info" style="background: #f0f8ff; border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        echo '<h3>' . __('Your Na bežky! map access information', 'wp-nabezky-connector') . '</h3>';
        echo '<p>' . __('As a bonus with your purchase, you now have access to the premium Na bežky map with cross-country trails grooming status and other useful information. You can access the map immediately using the button below, or save the details from the email we sent you for later use.', 'wp-nabezky-connector') . '</p>';
        
        // Check if user is registered in Nabezky
        if (isset($voucher_data['is_registered_user']) && $voucher_data['is_registered_user']) {
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 15px;">';
            echo '<strong>' . __('Registered User Bonus!', 'wp-nabezky-connector') . '</strong><br>';
            echo __('As a nabezky.sk registered user, you have gained access to all premium features including the Nabezky map.', 'wp-nabezky-connector');
            echo '</div>';
        }
        
        if (isset($voucher_data['vouchers']) && is_array($voucher_data['vouchers'])) {
            foreach ($voucher_data['vouchers'] as $index => $voucher) {
                echo '<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">';
                echo '<strong>' . __('Voucher Number:', 'wp-nabezky-connector') . '</strong> ' . esc_html($voucher['number']) . '<br>';
                echo '<strong>' . __('Access Type:', 'wp-nabezky-connector') . '</strong> ' . 
                     ($voucher['type'] === 'seasonal' ? __('Seasonal Pass', 'wp-nabezky-connector') : __('3-Day Access', 'wp-nabezky-connector')) . '<br>';
                
                // Handle expiration display - show '3 days after first use' for NULL expiration dates
                echo '<strong>' . __('Expires:', 'wp-nabezky-connector') . '</strong> ';
                if (isset($voucher['expires']) && $voucher['expires'] !== null) {
                    echo $this->format_localized_date($voucher['expires']);
                } else {
                    echo __('3 days after first use', 'wp-nabezky-connector');
                }
                echo '<br>';
                
                // Add activation link for each voucher
                $activate_url = $this->build_map_access_url($map_url, $voucher['number'], $voucher_data['email']);
                echo '<br><a href="' . esc_url($activate_url) . '" target="_blank" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin-top: 10px;">' . 
                     __('Visit map now', 'wp-nabezky-connector') . '</a>';
                echo '</div>';
            }
        }
        
        echo '</div>';
    }
    
    /**
     * Shortcode to display voucher information
     */
    public function voucher_info_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_id' => '',
        ), $atts);
        
        if (empty($atts['order_id'])) {
            return '<p>' . __('Order ID is required.', 'wp-nabezky-connector') . '</p>';
        }
        
        $order = wc_get_order($atts['order_id']);
        if (!$order) {
            return '<p>' . __('Order not found.', 'wp-nabezky-connector') . '</p>';
        }
        
        // Check if order contains configured Nabezky products first
        $nabezky_products = $this->get_nabezky_products_from_order($order);
        
        // Only show voucher info if order contains Nabezky products
        if (empty($nabezky_products['items'])) {
            return ''; // Don't show anything for non-Nabezky orders
        }
        
        $voucher_data = $order->get_meta('_nabezky_vouchers');
        if (empty($voucher_data)) {
            return '<p>' . __('No voucher information found for this order.', 'wp-nabezky-connector') . '</p>';
        }
        
        ob_start();
        $this->render_voucher_info($voucher_data);
        return ob_get_clean();
    }
    
    /**
     * Shortcode to display map link
     */
    public function map_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'voucher' => '',
            'email' => '',
            'text' => __('Access Interactive Map', 'wp-nabezky-connector'),
            'target' => '_blank'
        ), $atts);
        
        if (empty($atts['voucher']) || empty($atts['email'])) {
            return '<p>' . __('Voucher number and email are required.', 'wp-nabezky-connector') . '</p>';
        }
        
        $options = get_option('wp_nabezky_connector_options');
        $map_url = $options['nabezky_map_url'];
        $url = $this->build_map_access_url($map_url, $atts['voucher'], $atts['email']);
        
        return sprintf(
            '<a href="%s" target="%s" class="nabezky-map-link" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">%s</a>',
            esc_url($url),
            esc_attr($atts['target']),
            esc_html($atts['text'])
        );
    }
    
    /**
     * Log error message
     */
    private function log_error($message, $data = '') {
        error_log("WP Nabezky Connector ERROR: $message" . ($data ? " - $data" : ''));
    }
    
    /**
     * Log info message
     */
    private function log_info($message, $data = '') {
        error_log("WP Nabezky Connector INFO: $message" . ($data ? " - $data" : ''));
    }
    
    /**
     * AJAX handler for testing Nabezky connection
     */
    public function ajax_test_connection() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'test_nabezky_connection')) {
            wp_die(__('Security check failed', 'wp-nabezky-connector'));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-nabezky-connector'));
        }
        
        // Get current plugin options
        $options = get_option('wp_nabezky_connector_options', array());
        
        // Initialize API class and run test
        $api = new WP_Nabezky_API();
        $test_result = $api->test_connection();
        
        // Log the test attempt
        $this->log_info('API Connection Test', 'Test initiated from admin interface - Result: ' . json_encode($test_result));
        
        // Return JSON response
        wp_send_json($test_result);
    }
    
    /**
     * Format date for display with proper localization
     */
    private function format_localized_date($timestamp) {
        $locale = get_locale();
        
        // Slovak month names
        $sk_months = array(
            1 => __('January', 'wp-nabezky-connector'),
            2 => __('February', 'wp-nabezky-connector'),
            3 => __('March', 'wp-nabezky-connector'),
            4 => __('April', 'wp-nabezky-connector'),
            5 => __('May', 'wp-nabezky-connector'),
            6 => __('June', 'wp-nabezky-connector'),
            7 => __('July', 'wp-nabezky-connector'),
            8 => __('August', 'wp-nabezky-connector'),
            9 => __('September', 'wp-nabezky-connector'),
            10 => __('October', 'wp-nabezky-connector'),
            11 => __('November', 'wp-nabezky-connector'),
            12 => __('December', 'wp-nabezky-connector')
        );
        
        $day = date('j', $timestamp);
        $month = date('n', $timestamp);
        $year = date('Y', $timestamp);
        
        if ($locale === 'sk_SK') {
            return $day . '. ' . $sk_months[$month] . ' ' . $year;
        } elseif ($locale === 'cs_CZ') {
            return $day . '. ' . $sk_months[$month] . ' ' . $year;
        } else {
            // Fallback to simple format for other locales
            return date('j.n.Y', $timestamp);
        }
    }
}

// Initialize the plugin
WP_Nabezky_Connector::get_instance();
