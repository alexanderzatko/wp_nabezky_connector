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
     * Test API connection (placeholder for future implementation)
     * 
     * @return array Connection test result
     */
    public function test_connection() {
        // This would be implemented when the actual Nabezky API is available
        return array(
            'success' => true,
            'message' => 'API connection test not yet implemented',
            'response_time' => 0
        );
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

