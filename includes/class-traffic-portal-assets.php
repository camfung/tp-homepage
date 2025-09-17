<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets manager for Traffic Portal Link Shortener
 */
class Traffic_Portal_Assets {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
    }
    
    /**
     * Register frontend assets
     */
    public function register_assets() {
        // Register Bootstrap CSS
        wp_register_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css',
            array(),
            '5.0.2'
        );
        
        // Register Bootstrap JS
        wp_register_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js',
            array(),
            '5.0.2',
            true
        );
        
        // Register custom CSS
        wp_register_style(
            'traffic-portal-frontend',
            TPLS_PLUGIN_URL . 'assets/css/frontend.css',
            array('bootstrap'),
            TPLS_VERSION
        );
        
        // Register custom JS
        wp_register_script(
            'traffic-portal-frontend',
            TPLS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'bootstrap'),
            TPLS_VERSION,
            true
        );
    }
    
    /**
     * Register admin assets
     */
    public function register_admin_assets() {
        // Register admin CSS
        wp_register_style(
            'traffic-portal-admin',
            TPLS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TPLS_VERSION
        );
        
        // Register admin JS
        wp_register_script(
            'traffic-portal-admin',
            TPLS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TPLS_VERSION,
            true
        );
    }
    
    /**
     * Enqueue frontend assets for shortcode
     */
    public function enqueue_shortcode_assets() {
        wp_enqueue_style('bootstrap');
        wp_enqueue_style('traffic-portal-frontend');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('bootstrap');
        wp_enqueue_script('traffic-portal-frontend');
        
        // Localize script for AJAX
        wp_localize_script('traffic-portal-frontend', 'tpls_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('traffic-portal/v1/'),
            'nonce'    => wp_create_nonce('tpls_shortcode_action'),
            'messages' => array(
                'validating'     => __('Validating key...', 'traffic-portal-link-shortener'),
                'creating'       => __('Creating short link...', 'traffic-portal-link-shortener'),
                'success'        => __('Link created successfully!', 'traffic-portal-link-shortener'),
                'error_generic'  => __('An error occurred. Please try again.', 'traffic-portal-link-shortener'),
                'error_key_used' => __('This key is already in use. Please choose another.', 'traffic-portal-link-shortener'),
                'error_invalid'  => __('Please enter a valid URL.', 'traffic-portal-link-shortener'),
                'login_required' => __('Please log in to create short links.', 'traffic-portal-link-shortener'),
            )
        ));
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets(string $hook) {
        // Only load on our admin pages
        if (strpos($hook, 'traffic-portal') === false) {
            return;
        }
        
        wp_enqueue_style('traffic-portal-admin');
        wp_enqueue_script('traffic-portal-admin');
        
        // Localize admin script
        wp_localize_script('traffic-portal-admin', 'tpls_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('traffic-portal/v1/'),
            'nonce'    => wp_create_nonce('tpls_admin_action'),
            'messages' => array(
                'confirm_delete' => __('Are you sure you want to delete this link?', 'traffic-portal-link-shortener'),
                'deleting'       => __('Deleting link...', 'traffic-portal-link-shortener'),
                'updating'       => __('Updating link...', 'traffic-portal-link-shortener'),
                'success'        => __('Action completed successfully!', 'traffic-portal-link-shortener'),
                'error'          => __('An error occurred. Please try again.', 'traffic-portal-link-shortener'),
            )
        ));
    }
    
    /**
     * Get inline CSS for Traffic Portal styling
     */
    public function get_inline_css(): string {
        return '
        .traffic-portal-container {
            width: 85%;
            margin: 2rem auto;
            padding: 2rem;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .traffic-portal-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .traffic-portal-title {
            color: #1976d2;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .traffic-portal-subtitle {
            color: #666;
            font-size: 0.95rem;
        }
        
        .traffic-portal-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-field-group {
            margin-bottom: 1.5rem;
        }
        
        .form-field-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-field-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-field-group input:focus {
            outline: none;
            border-color: #2196f3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        
        .btn-save {
            background: #4caf50;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        
        .btn-save:hover {
            background: #45a049;
        }
        
        .btn-save:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .traffic-portal-result {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 6px;
            display: none;
        }
        
        .traffic-portal-result.success {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            color: #2e7d32;
        }
        
        .traffic-portal-result.error {
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
        }
        
        .traffic-portal-result.info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            color: #1565c0;
        }
        
        .short-link-display {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 1.1rem;
            word-break: break-all;
        }
        
        @media (max-width: 768px) {
            .traffic-portal-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .traffic-portal-form {
                padding: 1rem;
            }
        }
        ';
    }
}