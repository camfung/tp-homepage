<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler for Traffic Portal Link Shortener
 */
class Traffic_Portal_Shortcode {
    
    /**
     * Assets manager instance
     */
    private $assets;
    
    /**
     * Constructor
     */
    public function __construct(Traffic_Portal_Assets $assets) {
        $this->assets = $assets;
        
        add_shortcode('traffic_portal', array($this, 'render_shortcode'));
    }
    
    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode content
     */
    public function render_shortcode($atts): string {
        $atts = shortcode_atts(array(
            'theme'  => 'default',
            'domain' => 'trfc.link',
            'width'  => 'auto',
        ), $atts);
        
        // Enqueue assets
        $this->assets->enqueue_shortcode_assets();
        
        ob_start();
        ?>
        <div class="traffic-portal-container" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="traffic-portal-header">
                <h2 class="traffic-portal-title">
                    <i class="fas fa-link" aria-hidden="true"></i>
                    <?php esc_html_e('Link Shortener', 'traffic-portal-link-shortener'); ?>
                </h2>
                <p class="traffic-portal-subtitle">
                    <?php esc_html_e('Choose a short, easy to remember word or generate a random combination of letters. Provide the destination. No registration needed!', 'traffic-portal-link-shortener'); ?>
                </p>
            </div>
                <form id="traffic-portal-form" class="traffic-portal-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-field-group">
                                <label for="tpkey">
                                    <?php esc_html_e('Custom Short Code', 'traffic-portal-link-shortener'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-refresh" aria-hidden="true"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        id="tpkey" 
                                        name="tpkey" 
                                        class="form-control form-field-tpkey" 
                                        placeholder="<?php esc_attr_e('e.g., ruj41u', 'traffic-portal-link-shortener'); ?>"
                                        pattern="[a-zA-Z0-9_-]{3,20}"
                                        maxlength="20"
                                        required
                                    >
                                    <button type="button" class="btn btn-outline-secondary" id="generate-key">
                                        <i class="fas fa-dice" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('3-20 characters: letters, numbers, underscore, dash', 'traffic-portal-link-shortener'); ?>
                                </small>
                                <div class="validation-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-field-group">
                                <label for="destination">
                                    <?php esc_html_e('Destination URL', 'traffic-portal-link-shortener'); ?>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-globe" aria-hidden="true"></i>
                                    </span>
                                    <input 
                                        type="url" 
                                        id="destination" 
                                        name="destination" 
                                        class="form-control form-field-destination" 
                                        placeholder="<?php esc_attr_e('https://example.com/your-page', 'traffic-portal-link-shortener'); ?>"
                                        required
                                    >
                                </div>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Enter the full URL where this short link should redirect', 'traffic-portal-link-shortener'); ?>
                                </small>
                                <div class="validation-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="form-field-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-save btn-primary w-100" id="save-link">
                                    <i class="fas fa-save" aria-hidden="true"></i>
                                    <span class="btn-text"><?php esc_html_e('Save', 'traffic-portal-link-shortener'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="domain" value="<?php echo esc_attr($atts['domain']); ?>">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('tpls_shortcode_action')); ?>">
                </form>
                
                <div id="traffic-portal-result" class="traffic-portal-result">
                    <div class="result-content"></div>
                    <div class="short-link-display" style="display: none;">
                        <div class="row">
                            <div class="col-md-8">
                                <strong><?php esc_html_e('Your short link:', 'traffic-portal-link-shortener'); ?></strong><br>
                                <a href="#" target="_blank" class="short-link-url"></a>
                                <button type="button" class="btn btn-sm btn-outline-secondary copy-link" data-clipboard-target=".short-link-url">
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                    <?php esc_html_e('Copy', 'traffic-portal-link-shortener'); ?>
                                </button>
                            </div>
                            <div class="col-md-4">
                                <div class="qr-code-container text-center">
                                    <small class="text-muted"><?php esc_html_e('Scan QR Code:', 'traffic-portal-link-shortener'); ?></small>
                                    <div id="qr-code-display" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    
    /**
     * Generate a random key
     *
     * @param int $length Key length
     * @return string Random key
     */
    public static function generate_random_key(int $length = 6): string {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $key = '';
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[wp_rand(0, strlen($characters) - 1)];
        }
        
        return $key;
    }
    
    /**
     * Validate shortcode attributes
     */
    private function validate_attributes(array $atts): array {
        // Validate theme
        $allowed_themes = array('default', 'minimal', 'branded');
        if (!in_array($atts['theme'], $allowed_themes, true)) {
            $atts['theme'] = 'default';
        }
        
        // Validate domain
        $allowed_domains = array('trfc.link', 'trafficportal.dev');
        if (!in_array($atts['domain'], $allowed_domains, true)) {
            $atts['domain'] = 'trfc.link';
        }
        
        // Validate width
        if ($atts['width'] !== 'auto' && !preg_match('/^\d+(%|px)$/', $atts['width'])) {
            $atts['width'] = 'auto';
        }
        
        return $atts;
    }
}
