<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API proxy handler for Traffic Portal Link Shortener
 */
class Traffic_Portal_API {
    
    /**
     * API base URL
     */
    private $api_base_url;
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = TPLS_API_BASE_URL;
        $this->api_key = TPLS_API_KEY;
        
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('wp_ajax_tpls_validate_key', array($this, 'ajax_validate_key'));
        add_action('wp_ajax_tpls_create_link', array($this, 'ajax_create_link'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('traffic-portal/v1', '/validate', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'validate_key_endpoint'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args'                => array(
                'tpkey' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_key_format'),
                ),
                'domain' => array(
                    'default'           => 'trfc.link',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        register_rest_route('traffic-portal/v1', '/create', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'create_link_endpoint'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args'                => array(
                'tpkey' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_key_format'),
                ),
                'destination' => array(
                    'required'          => true,
                    'sanitize_callback' => 'esc_url_raw',
                    'validate_callback' => array($this, 'validate_url'),
                ),
                'domain' => array(
                    'default'           => 'trfc.link',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    
    /**
     * Check if user has permission to use API
     */
    public function check_user_permission(): bool {
        return is_user_logged_in();
    }
    
    /**
     * Validate key format
     */
    public function validate_key_format(string $key): bool {
        return preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $key) === 1;
    }
    
    /**
     * Validate URL format
     */
    public function validate_url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * REST API endpoint to validate key
     */
    public function validate_key_endpoint(WP_REST_Request $request): WP_REST_Response {
        $tpkey = $request->get_param('tpkey');
        $domain = $request->get_param('domain') ?: 'trfc.link';
        
        // Check with Traffic Portal API
        $api_response = $this->call_validate_api($tpkey, $domain);
        
        if (is_wp_error($api_response)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to connect to Traffic Portal API',
                'error'   => $api_response->get_error_message(),
            ), 500);
        }
        
        return new WP_REST_Response($api_response, 200);
    }
    
    /**
     * REST API endpoint to create link
     */
    public function create_link_endpoint(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        $tpkey = $request->get_param('tpkey');
        $destination = $request->get_param('destination');
        $domain = $request->get_param('domain') ?: 'trfc.link';
        
        // Get user token (this would need to be implemented based on user meta or options)
        $user_token = $this->get_user_traffic_portal_token($user_id);
        
        if (!$user_token) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'User not authenticated with Traffic Portal. Please register or link your account.',
            ), 401);
        }
        
        // Call Traffic Portal API
        $api_response = $this->call_create_api($user_id, $user_token, $tpkey, $domain, $destination);
        
        if (is_wp_error($api_response)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Failed to create link',
                'error'   => $api_response->get_error_message(),
            ), 500);
        }
        
        // If successful, store in local database
        if (isset($api_response['success']) && $api_response['success']) {
            $database = new Traffic_Portal_Database();
            $local_id = $database->save_link($user_id, $tpkey, $destination, $domain);
            
            if ($local_id) {
                $api_response['local_id'] = $local_id;
            }
        }
        
        return new WP_REST_Response($api_response, 200);
    }
    
    /**
     * AJAX handler for key validation
     */
    public function ajax_validate_key() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpls_shortcode_action')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Please log in first')));
        }
        
        $tpkey = isset($_POST['tpkey']) ? sanitize_text_field($_POST['tpkey']) : '';
        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : 'trfc.link';
        
        if (empty($tpkey) || !$this->validate_key_format($tpkey)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Invalid key format')));
        }
        
        $response = $this->call_validate_api($tpkey, $domain);
        
        if (is_wp_error($response)) {
            wp_die(json_encode(array('success' => false, 'message' => $response->get_error_message())));
        }
        
        wp_die(json_encode($response));
    }
    
    /**
     * AJAX handler for link creation
     */
    public function ajax_create_link() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpls_shortcode_action')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        if (!is_user_logged_in()) {
            wp_die(json_encode(array('success' => false, 'message' => 'Please log in first')));
        }
        
        $user_id = get_current_user_id();
        $tpkey = isset($_POST['tpkey']) ? sanitize_text_field($_POST['tpkey']) : '';
        $destination = isset($_POST['destination']) ? esc_url_raw($_POST['destination']) : '';
        $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : 'trfc.link';
        
        // Validation
        if (empty($tpkey) || !$this->validate_key_format($tpkey)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Invalid key format')));
        }
        
        if (empty($destination) || !$this->validate_url($destination)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Invalid destination URL')));
        }
        
        // Get user token
        $user_token = $this->get_user_traffic_portal_token($user_id);
        
        if (!$user_token) {
            wp_die(json_encode(array('success' => false, 'message' => 'User not authenticated with Traffic Portal')));
        }
        
        // Create link via API
        $response = $this->call_create_api($user_id, $user_token, $tpkey, $domain, $destination);
        
        if (is_wp_error($response)) {
            wp_die(json_encode(array('success' => false, 'message' => $response->get_error_message())));
        }
        
        // Store locally if successful
        if (isset($response['success']) && $response['success']) {
            $database = new Traffic_Portal_Database();
            $local_id = $database->save_link($user_id, $tpkey, $destination, $domain);
            
            if ($local_id) {
                $response['local_id'] = $local_id;
                $response['short_url'] = "https://{$domain}/{$tpkey}";
            }
        }
        
        wp_die(json_encode($response));
    }
    
    /**
     * Call Traffic Portal validate API
     */
    private function call_validate_api(string $tpkey, string $domain) {
        $url = $this->api_base_url . '/items/validate?' . http_build_query(array(
            'tpkey'  => $tpkey,
            'domain' => $domain,
        ));
        
        $headers = array(
            'Accept' => 'application/json',
        );
        
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        $response = wp_remote_get($url, array(
            'timeout'    => 15,
            'user-agent' => 'WordPress-Traffic-Portal-Plugin/' . TPLS_VERSION,
            'headers'    => $headers,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response from API');
        }
        
        return $data;
    }
    
    /**
     * Call Traffic Portal create API
     */
    private function call_create_api(int $user_id, string $user_token, string $tpkey, string $domain, string $destination) {
        $url = $this->api_base_url . '/items';
        
        $body = array(
            'uid'         => $user_id,
            'tpTkn'       => $user_token,
            'tpKey'       => $tpkey,
            'domain'      => $domain,
            'destination' => $destination,
            'status'      => 'active',
        );
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        );
        
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        $response = wp_remote_post($url, array(
            'timeout'    => 30,
            'user-agent' => 'WordPress-Traffic-Portal-Plugin/' . TPLS_VERSION,
            'headers'    => $headers,
            'body'       => wp_json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body_content = wp_remote_retrieve_body($response);
        $data = json_decode($body_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response from API');
        }
        
        if ($response_code !== 200) {
            $error_message = isset($data['message']) ? $data['message'] : 'API request failed';
            return new WP_Error('api_error', $error_message);
        }
        
        return $data;
    }
    
    /**
     * Get user's Traffic Portal token
     * This should be implemented based on how user tokens are stored
     */
    private function get_user_traffic_portal_token(int $user_id): ?string {
        // For now, return a placeholder. This would need to be implemented
        // based on how users authenticate with Traffic Portal
        $token = get_user_meta($user_id, 'traffic_portal_token', true);
        
        // If no token exists, could generate a temporary one or require registration
        if (empty($token)) {
            // For demo purposes, generate a basic token from user data
            $user = get_userdata($user_id);
            if ($user) {
                // This is a simplified token generation - in production,
                // this should integrate with Traffic Portal's auth system
                $token = 'wp_' . $user->user_login . '_' . $user_id;
                update_user_meta($user_id, 'traffic_portal_token', $token);
            }
        }
        
        return $token ?: null;
    }
    
    /**
     * Set user's Traffic Portal token
     */
    public function set_user_traffic_portal_token(int $user_id, string $token): bool {
        return update_user_meta($user_id, 'traffic_portal_token', sanitize_text_field($token)) !== false;
    }
    
    /**
     * Get API key
     */
    public function get_api_key(): string {
        return $this->api_key;
    }
}