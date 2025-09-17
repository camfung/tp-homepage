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
            width: 85% !important;
        }
        ';
    }
}
