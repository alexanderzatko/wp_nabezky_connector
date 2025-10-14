<?php
/**
 * Nabezky API Integration Class
 * 
 * This class handles communication with the Nabezky system for voucher validation
 * and user access management.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Nabezky_API {
    
    /**
     * API base URL
     */
    private $api_url;
    
    /**
     * Map URL
     */
    private $map_url;
    
    /**
     * Constructor
     */
    public function __construct() {
        $options = get_option('wp_nabezky_connector_options', array());
        $this->api_url = rtrim($options['nabezky_api_url'] ?? 'https://nabezky.sk', '/');
        $this->map_url = rtrim($options['nabezky_map_url'] ?? 'https://mapa.nabezky.sk', '/');
    }
    
    /**
     * Validate voucher number and email combination
     * 
     * @param string $voucher_number The voucher number to validate
     * @param string $email The email address associated with the voucher
     * @return array|false Returns validation result or false on error
     */
    public function validate_voucher($voucher_number, $email) {
        // For now, we'll implement a local validation based on the Nabezky format
        // In a real implementation, you would make an API call to the Nabezky system
        
        $validation_result = array(
            'valid' => false,
            'voucher_info' => null,
            'error' => null
        );
        
        // Parse voucher number to extract information
        $voucher_info = $this->parse_voucher_number($voucher_number);
        
        if (!$voucher_info) {
            $validation_result['error'] = 'Invalid voucher format';
            return $validation_result;
        }
        
        // Validate email format
        if (!is_email($email)) {
            $validation_result['error'] = 'Invalid email format';
            return $validation_result;
        }
        
        // Check if voucher is expired
        if ($voucher_info['expires'] && $voucher_info['expires'] < time()) {
            $validation_result['error'] = 'Voucher has expired';
            return $validation_result;
        }
        
        // For demonstration, we'll consider all properly formatted vouchers as valid
        // In a real implementation, you would check against the Nabezky database
        $validation_result['valid'] = true;
        $validation_result['voucher_info'] = $voucher_info;
        
        return $validation_result;
    }
    
    /**
     * Parse voucher number to extract information
     * 
     * @param string $voucher_number The voucher number to parse
     * @return array|false Returns parsed voucher info or false on error
     */
    public function parse_voucher_number($voucher_number) {
        // Voucher format: Season(1) + Region(2) + Period(1) + Random(8) = 12 digits total
        if (strlen($voucher_number) !== 12) {
            return false;
        }
        
        $season = (int)substr($voucher_number, 0, 1);
        $region = (int)substr($voucher_number, 1, 2);
        $period = (int)substr($voucher_number, 3, 1);
        $random = substr($voucher_number, 4, 8);
        
        // Calculate expiration date based on period
        $expires = null;
        if ($period === 0) {
            // Seasonal voucher - expires at end of ski season
            $expires = $this->get_season_end_date();
        } elseif ($period === 3) {
            // 3-day voucher - expires 3 days from now
            $expires = time() + (3 * 24 * 60 * 60);
        }
        
        return array(
            'season' => $season,
            'region' => $region,
            'period' => $period,
            'random' => $random,
            'expires' => $expires,
            'type' => $period === 0 ? 'seasonal' : '3day'
        );
    }
    
    /**
     * Get season end date
     * 
     * @return int Unix timestamp of season end
     */
    private function get_season_end_date() {
        $current_year = date('Y');
        $current_month = date('n');
        
        // Winter season typically ends in March/April
        if ($current_month >= 10) {
            // Next year's spring
            return mktime(0, 0, 0, 4, 30, $current_year + 1);
        } else {
            // This year's spring
            return mktime(0, 0, 0, 4, 30, $current_year);
        }
    }
    
    /**
     * Generate map access URL with voucher
     * 
     * @param string $voucher_number The voucher number
     * @param string $email The email address
     * @param array $params Additional parameters
     * @return string The complete map URL
     */
    public function get_map_access_url($voucher_number, $email, $params = array()) {
        // Parse the base URL to determine the correct domain
        $parsed_url = parse_url($this->map_url);
        $host = $parsed_url['host'] ?? 'mapa.nabezky.sk';
        
        // Build the correct URL format based on reference code
        if (strpos($host, 'nabezky.sk') !== false) {
            // Use the correct subdomain format
            $site = 'mapa';
        } else {
            $site = 'devmapa';
        }
        
        $url_params = array(
            'voucher' => $voucher_number,
            'email' => $email
        );
        
        // Add any additional parameters
        $url_params = array_merge($url_params, $params);
        
        return 'https://' . $site . '.nabezky.sk?' . http_build_query($url_params);
    }
    
    /**
     * Check if voucher format is valid
     * 
     * @param string $voucher_number The voucher number to check
     * @return bool True if format is valid
     */
    public function is_valid_voucher_format($voucher_number) {
        return strlen($voucher_number) === 12 && is_numeric($voucher_number);
    }
    
    /**
     * Get current season number
     * 
     * @return int Current season number
     */
    public function get_current_season_number() {
        $current_year = date('Y');
        return (($current_year - 2023) % 9) + 1;
    }
    
    /**
     * Generate random voucher number component
     * 
     * @return string 8-digit random number
     */
    public function generate_random_voucher_number() {
        return str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
    
    /**
     * Build complete voucher number
     * 
     * @param int $region_id Region ID (1-99)
     * @param int $period Period (0=seasonal, 3=3days)
     * @return string Complete voucher number
     */
    public function build_voucher_number($region_id, $period = 0) {
        $season = $this->get_current_season_number();
        $region = str_pad($region_id, 2, '0', STR_PAD_LEFT);
        $random = $this->generate_random_voucher_number();
        
        return $season . $region . $period . $random;
    }
    
    /**
     * Test API connection with comprehensive validation
     * 
     * @return array Connection test result
     */
    public function test_connection() {
        $start_time = microtime(true);
        $test_results = array(
            'success' => false,
            'message' => '',
            'response_time' => 0,
            'tests' => array(),
            'errors' => array(),
            'warnings' => array()
        );
        
        try {
            // Test 1: Validate configuration
            $config_test = $this->test_configuration();
            $test_results['tests']['configuration'] = $config_test;
            
            if (!$config_test['success']) {
                $test_results['errors'] = array_merge($test_results['errors'], $config_test['errors']);
                $test_results['message'] = 'Configuration validation failed';
                return $test_results;
            }
            
            // Test 2: Basic connectivity
            $connectivity_test = $this->test_basic_connectivity();
            $test_results['tests']['connectivity'] = $connectivity_test;
            
            if (!$connectivity_test['success']) {
                $test_results['errors'] = array_merge($test_results['errors'], $connectivity_test['errors']);
                $test_results['message'] = 'Basic connectivity test failed';
                return $test_results;
            }
            
            // Test 3: Endpoint availability
            $endpoint_test = $this->test_endpoint_availability();
            $test_results['tests']['endpoint'] = $endpoint_test;
            
            if (!$endpoint_test['success']) {
                $test_results['errors'] = array_merge($test_results['errors'], $endpoint_test['errors']);
                $test_results['message'] = 'Endpoint availability test failed';
                return $test_results;
            }
            
            // Test 4: Authentication validation
            $auth_test = $this->test_authentication();
            $test_results['tests']['authentication'] = $auth_test;
            
            if (!$auth_test['success']) {
                $test_results['warnings'] = array_merge($test_results['warnings'], $auth_test['warnings']);
                $test_results['message'] = 'Authentication test completed with warnings';
            } else {
                $test_results['message'] = 'All connection tests passed successfully';
            }
            
            // Calculate total response time
            $test_results['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
            $test_results['success'] = true;
            
        } catch (Exception $e) {
            $test_results['errors'][] = 'Unexpected error: ' . $e->getMessage();
            $test_results['message'] = 'Connection test failed with unexpected error';
            $test_results['response_time'] = round((microtime(true) - $start_time) * 1000, 2);
        }
        
        return $test_results;
    }
    
    /**
     * Test configuration validity
     * 
     * @return array Configuration test result
     */
    private function test_configuration() {
        $result = array(
            'success' => true,
            'errors' => array(),
            'details' => array()
        );
        
        $options = get_option('wp_nabezky_connector_options', array());
        
        // Check API URL
        if (empty($options['nabezky_api_url'])) {
            $result['errors'][] = 'API URL is not configured';
            $result['success'] = false;
        } elseif (!filter_var($options['nabezky_api_url'], FILTER_VALIDATE_URL)) {
            $result['errors'][] = 'API URL format is invalid';
            $result['success'] = false;
        } else {
            $result['details']['api_url'] = $options['nabezky_api_url'];
        }
        
        // Check Map URL
        if (empty($options['nabezky_map_url'])) {
            $result['errors'][] = 'Map URL is not configured';
            $result['success'] = false;
        } elseif (!filter_var($options['nabezky_map_url'], FILTER_VALIDATE_URL)) {
            $result['errors'][] = 'Map URL format is invalid';
            $result['success'] = false;
        } else {
            $result['details']['map_url'] = $options['nabezky_map_url'];
        }
        
        // Check access token
        if (empty($options['nabezky_access_token'])) {
            $result['errors'][] = 'Access token is not configured';
            $result['success'] = false;
        } else {
            $result['details']['token_configured'] = true;
        }
        
        // Check plugin enabled status
        if (!$options['enabled']) {
            $result['errors'][] = 'Plugin is not enabled';
            $result['success'] = false;
        } else {
            $result['details']['plugin_enabled'] = true;
        }
        
        // Check configured products
        if (empty($options['nabezky_products'])) {
            $result['errors'][] = 'No Nabezky products are configured';
            $result['success'] = false;
        } else {
            $result['details']['products_configured'] = count($options['nabezky_products']);
        }
        
        return $result;
    }
    
    /**
     * Test basic connectivity to API server
     * 
     * @return array Connectivity test result
     */
    private function test_basic_connectivity() {
        $result = array(
            'success' => false,
            'errors' => array(),
            'details' => array()
        );
        
        $options = get_option('wp_nabezky_connector_options', array());
        $api_url = rtrim($options['nabezky_api_url'], '/');
        
        // Test basic server connectivity with a HEAD request
        $args = array(
            'method' => 'HEAD',
            'timeout' => 10,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WP-Nabezky-Connector/' . WP_NABEZKY_CONNECTOR_VERSION
        );
        
        $response = wp_remote_request($api_url, $args);
        
        if (is_wp_error($response)) {
            $result['errors'][] = 'Connection failed: ' . $response->get_error_message();
            return $result;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $result['details']['response_code'] = $response_code;
        $result['details']['server_reachable'] = true;
        
        // Accept various success codes
        if ($response_code >= 200 && $response_code < 400) {
            $result['success'] = true;
            $result['details']['connection_status'] = 'Server is reachable';
        } else {
            $result['errors'][] = "Server responded with status code: $response_code";
        }
        
        return $result;
    }
    
    /**
     * Test endpoint availability
     * 
     * @return array Endpoint test result
     */
    private function test_endpoint_availability() {
        $result = array(
            'success' => false,
            'errors' => array(),
            'details' => array()
        );
        
        $options = get_option('wp_nabezky_connector_options', array());
        $api_url = rtrim($options['nabezky_api_url'], '/');
        $endpoint = $api_url . '/services/woocommerce-voucher-generation';
        
        // Test endpoint with OPTIONS request (safer than POST)
        $args = array(
            'method' => 'OPTIONS',
            'timeout' => 15,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WP-Nabezky-Connector/' . WP_NABEZKY_CONNECTOR_VERSION,
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            )
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            $result['errors'][] = 'Endpoint request failed: ' . $response->get_error_message();
            return $result;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $result['details']['endpoint_url'] = $endpoint;
        $result['details']['response_code'] = $response_code;
        
        // Accept various response codes (some APIs don't support OPTIONS)
        if ($response_code >= 200 && $response_code < 500) {
            $result['success'] = true;
            $result['details']['endpoint_status'] = 'Endpoint is accessible';
            
            // Check if it's a proper API response
            if ($response_code === 405) {
                $result['details']['note'] = 'Endpoint exists but OPTIONS method not supported (this is normal)';
            }
        } else {
            $result['errors'][] = "Endpoint responded with status code: $response_code";
            $result['details']['endpoint_status'] = 'Endpoint may not be available';
        }
        
        return $result;
    }
    
    /**
     * Test authentication without generating vouchers
     * 
     * @return array Authentication test result
     */
    private function test_authentication() {
        $result = array(
            'success' => false,
            'warnings' => array(),
            'details' => array()
        );
        
        $options = get_option('wp_nabezky_connector_options', array());
        $api_url = rtrim($options['nabezky_api_url'], '/');
        $endpoint = $api_url . '/services/woocommerce-voucher-generation';
        
        // Create a minimal test request that won't generate vouchers
        $test_data = array(
            'access_token' => $options['nabezky_access_token'],
            'order_data' => array(
                'test_mode' => true,
                'email' => 'test@example.com',
                'amount' => 0,
                'order_id' => 'TEST-' . time(),
                'region_id' => $options['default_region_id'] ?? 1
            )
        );
        
        $args = array(
            'method' => 'POST',
            'timeout' => 20,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => 'WP-Nabezky-Connector/' . WP_NABEZKY_CONNECTOR_VERSION,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($test_data)
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            $result['warnings'][] = 'Authentication test failed: ' . $response->get_error_message();
            return $result;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $result['details']['response_code'] = $response_code;
        $result['details']['response_size'] = strlen($response_body);
        
        // Analyze response
        if ($response_code === 200) {
            $result['success'] = true;
            $result['details']['auth_status'] = 'Authentication successful';
            
            // Try to parse response
            $response_data = json_decode($response_body, true);
            if ($response_data) {
                $result['details']['response_format'] = 'Valid JSON response';
                if (isset($response_data['status'])) {
                    $result['details']['api_status'] = $response_data['status'];
                }
            } else {
                $result['warnings'][] = 'Response is not valid JSON';
            }
            
        } elseif ($response_code === 401) {
            $result['warnings'][] = 'Authentication failed - Invalid access token';
            $result['details']['auth_status'] = 'Authentication failed';
        } elseif ($response_code === 403) {
            $result['warnings'][] = 'Access forbidden - Token may not have required permissions';
            $result['details']['auth_status'] = 'Access forbidden';
        } elseif ($response_code >= 400 && $response_code < 500) {
            $result['warnings'][] = "Client error (HTTP $response_code) - Check request format";
            $result['details']['auth_status'] = 'Client error';
        } elseif ($response_code >= 500) {
            $result['warnings'][] = "Server error (HTTP $response_code) - API server issue";
            $result['details']['auth_status'] = 'Server error';
        } else {
            $result['warnings'][] = "Unexpected response code: $response_code";
            $result['details']['auth_status'] = 'Unexpected response';
        }
        
        // Log the test request for debugging
        $this->log_activity('authentication_test', array(
            'endpoint' => $endpoint,
            'response_code' => $response_code,
            'response_size' => strlen($response_body)
        ), $result['success'] ? 'success' : 'warning');
        
        return $result;
    }
    
    /**
     * Log API activity
     * 
     * @param string $action The action performed
     * @param array $data Related data
     * @param string $result Result of the action
     */
    private function log_activity($action, $data = array(), $result = 'success') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'WP Nabezky Connector - %s: %s | Data: %s',
                $action,
                $result,
                json_encode($data)
            ));
        }
    }
}

