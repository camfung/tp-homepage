<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin dashboard for Traffic Portal Link Shortener
 */
class Traffic_Portal_Admin {
    
    /**
     * Database handler instance
     */
    private $database;
    
    /**
     * API handler instance
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct(Traffic_Portal_Database $database, Traffic_Portal_API $api) {
        $this->database = $database;
        $this->api = $api;
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('wp_ajax_tpls_admin_delete_link', array($this, 'ajax_delete_link'));
        add_action('wp_ajax_tpls_admin_toggle_status', array($this, 'ajax_toggle_status'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Traffic Portal Links', 'traffic-portal-link-shortener'),
            __('Short Links', 'traffic-portal-link-shortener'),
            'edit_posts',
            'traffic-portal-links',
            array($this, 'render_links_page'),
            'dashicons-admin-links',
            30
        );
        
        add_submenu_page(
            'traffic-portal-links',
            __('All Links', 'traffic-portal-link-shortener'),
            __('All Links', 'traffic-portal-link-shortener'),
            'edit_posts',
            'traffic-portal-links',
            array($this, 'render_links_page')
        );
        
        add_submenu_page(
            'traffic-portal-links',
            __('Settings', 'traffic-portal-link-shortener'),
            __('Settings', 'traffic-portal-link-shortener'),
            'manage_options',
            'traffic-portal-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'traffic-portal-links') {
            return;
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        switch ($action) {
            case 'delete':
                $this->handle_delete_action();
                break;
            case 'toggle_status':
                $this->handle_toggle_status_action();
                break;
        }
    }
    
    /**
     * Render links management page
     */
    public function render_links_page() {
        $user_id = get_current_user_id();
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        $links = $this->database->get_user_links($user_id, $per_page, $offset);
        $total_links = $this->database->get_user_links_count($user_id);
        $total_pages = ceil($total_links / $per_page);
        
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e('Your Short Links', 'traffic-portal-link-shortener'); ?>
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Page with Shortcode', 'traffic-portal-link-shortener'); ?>
                </a>
            </h1>
            
            <?php $this->render_admin_notices(); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <p class="description">
                        <?php 
                        printf(
                            esc_html__('You have created %d short link(s). Use the shortcode %s to display the link creation form on any page.', 'traffic-portal-link-shortener'),
                            $total_links,
                            '<code>[traffic_portal]</code>'
                        );
                        ?>
                    </p>
                </div>
            </div>
            
            <?php if (empty($links)): ?>
                <div class="no-links-message">
                    <h3><?php esc_html_e('No short links yet', 'traffic-portal-link-shortener'); ?></h3>
                    <p><?php esc_html_e('Create your first short link using the shortcode on any page or post.', 'traffic-portal-link-shortener'); ?></p>
                    <p><strong><?php esc_html_e('Shortcode:', 'traffic-portal-link-shortener'); ?></strong> <code>[traffic_portal]</code></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                        <tr>
                            <th scope="col" class="column-primary">
                                <?php esc_html_e('Short Link', 'traffic-portal-link-shortener'); ?>
                            </th>
                            <th scope="col">
                                <?php esc_html_e('Destination', 'traffic-portal-link-shortener'); ?>
                            </th>
                            <th scope="col">
                                <?php esc_html_e('Status', 'traffic-portal-link-shortener'); ?>
                            </th>
                            <th scope="col">
                                <?php esc_html_e('Created', 'traffic-portal-link-shortener'); ?>
                            </th>
                            <th scope="col">
                                <?php esc_html_e('Actions', 'traffic-portal-link-shortener'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td class="column-primary">
                                    <strong>
                                        <a href="https://<?php echo esc_attr($link->domain); ?>/<?php echo esc_attr($link->tpkey); ?>" target="_blank">
                                            <?php echo esc_html($link->domain); ?>/<span class="tpkey"><?php echo esc_html($link->tpkey); ?></span>
                                        </a>
                                    </strong>
                                    <button type="button" class="toggle-row"></button>
                                </td>
                                <td data-colname="<?php esc_attr_e('Destination', 'traffic-portal-link-shortener'); ?>">
                                    <a href="<?php echo esc_url($link->destination); ?>" target="_blank" class="destination-link">
                                        <?php echo esc_html(wp_trim_words($link->destination, 8, '...')); ?>
                                    </a>
                                </td>
                                <td data-colname="<?php esc_attr_e('Status', 'traffic-portal-link-shortener'); ?>">
                                    <span class="status-badge status-<?php echo esc_attr($link->status); ?>">
                                        <?php echo esc_html(ucfirst($link->status)); ?>
                                    </span>
                                </td>
                                <td data-colname="<?php esc_attr_e('Created', 'traffic-portal-link-shortener'); ?>">
                                    <?php 
                                    $created_time = strtotime($link->created_at);
                                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $created_time));
                                    ?>
                                </td>
                                <td data-colname="<?php esc_attr_e('Actions', 'traffic-portal-link-shortener'); ?>">
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="https://<?php echo esc_attr($link->domain); ?>/<?php echo esc_attr($link->tpkey); ?>" target="_blank">
                                                <?php esc_html_e('Visit', 'traffic-portal-link-shortener'); ?>
                                            </a> |
                                        </span>
                                        <span class="copy">
                                            <a href="#" class="copy-link" data-link="https://<?php echo esc_attr($link->domain); ?>/<?php echo esc_attr($link->tpkey); ?>">
                                                <?php esc_html_e('Copy', 'traffic-portal-link-shortener'); ?>
                                            </a> |
                                        </span>
                                        <span class="toggle">
                                            <a href="<?php echo esc_url($this->get_action_url('toggle_status', $link->id)); ?>" class="toggle-status">
                                                <?php echo $link->status === 'active' ? esc_html__('Deactivate', 'traffic-portal-link-shortener') : esc_html__('Activate', 'traffic-portal-link-shortener'); ?>
                                            </a> |
                                        </span>
                                        <span class="delete">
                                            <a href="<?php echo esc_url($this->get_action_url('delete', $link->id)); ?>" 
                                               class="delete-link" 
                                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this link?', 'traffic-portal-link-shortener'); ?>');">
                                                <?php esc_html_e('Delete', 'traffic-portal-link-shortener'); ?>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base'      => add_query_arg('paged', '%#%'),
                                'format'    => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <style>
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .tpkey {
            font-family: monospace;
            font-weight: bold;
        }
        
        .destination-link {
            color: #666;
            text-decoration: none;
        }
        
        .destination-link:hover {
            color: #0073aa;
        }
        
        .no-links-message {
            text-align: center;
            padding: 3rem 1rem;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .copy-link {
            cursor: pointer;
        }
        </style>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'tpls_settings')) {
            $this->handle_settings_save();
        }
        
        $current_user = wp_get_current_user();
        $user_token = get_user_meta($current_user->ID, 'traffic_portal_token', true);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Traffic Portal Settings', 'traffic-portal-link-shortener'); ?></h1>
            
            <?php $this->render_admin_notices(); ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('tpls_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Your Account', 'traffic-portal-link-shortener'); ?>
                        </th>
                        <td>
                            <p><strong><?php echo esc_html($current_user->display_name); ?></strong> (<?php echo esc_html($current_user->user_email); ?>)</p>
                            <p class="description">
                                <?php esc_html_e('Your WordPress account is automatically linked to Traffic Portal.', 'traffic-portal-link-shortener'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Traffic Portal Token', 'traffic-portal-link-shortener'); ?>
                        </th>
                        <td>
                            <input type="text" 
                                   name="traffic_portal_token" 
                                   value="<?php echo esc_attr($user_token); ?>" 
                                   class="regular-text"
                                   readonly>
                            <p class="description">
                                <?php esc_html_e('This token is automatically generated and links your WordPress account to Traffic Portal.', 'traffic-portal-link-shortener'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Default Domain', 'traffic-portal-link-shortener'); ?>
                        </th>
                        <td>
                            <select name="default_domain">
                                <option value="trfc.link" <?php selected(get_user_meta($current_user->ID, 'tpls_default_domain', true), 'trfc.link'); ?>>
                                    trfc.link
                                </option>
                                <option value="trafficportal.dev" <?php selected(get_user_meta($current_user->ID, 'tpls_default_domain', true), 'trafficportal.dev'); ?>>
                                    trafficportal.dev
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Choose the default domain for your short links.', 'traffic-portal-link-shortener'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2><?php esc_html_e('Shortcode Usage', 'traffic-portal-link-shortener'); ?></h2>
            <div class="card">
                <h3><?php esc_html_e('Basic Usage', 'traffic-portal-link-shortener'); ?></h3>
                <p><code>[traffic_portal]</code></p>
                
                <h3><?php esc_html_e('With Custom Domain', 'traffic-portal-link-shortener'); ?></h3>
                <p><code>[traffic_portal domain="trafficportal.dev"]</code></p>
                
                <h3><?php esc_html_e('With Theme', 'traffic-portal-link-shortener'); ?></h3>
                <p><code>[traffic_portal theme="minimal"]</code></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle settings save
     */
    private function handle_settings_save() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $user_id = get_current_user_id();
        $default_domain = isset($_POST['default_domain']) ? sanitize_text_field($_POST['default_domain']) : 'trfc.link';
        
        update_user_meta($user_id, 'tpls_default_domain', $default_domain);
        
        add_settings_error('tpls_settings', 'settings_updated', __('Settings saved successfully.', 'traffic-portal-link-shortener'), 'updated');
    }
    
    /**
     * Handle delete action
     */
    private function handle_delete_action() {
        if (!isset($_GET['link_id']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_link_' . $_GET['link_id'])) {
            wp_die(__('Security check failed.', 'traffic-portal-link-shortener'));
        }
        
        $link_id = intval($_GET['link_id']);
        $user_id = get_current_user_id();
        
        if ($this->database->delete_link($link_id, $user_id)) {
            add_settings_error('tpls_admin', 'link_deleted', __('Link deleted successfully.', 'traffic-portal-link-shortener'), 'updated');
        } else {
            add_settings_error('tpls_admin', 'delete_failed', __('Failed to delete link.', 'traffic-portal-link-shortener'), 'error');
        }
        
        wp_redirect(admin_url('admin.php?page=traffic-portal-links'));
        exit;
    }
    
    /**
     * Handle status toggle action
     */
    private function handle_toggle_status_action() {
        if (!isset($_GET['link_id']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'toggle_status_' . $_GET['link_id'])) {
            wp_die(__('Security check failed.', 'traffic-portal-link-shortener'));
        }
        
        $link_id = intval($_GET['link_id']);
        $user_id = get_current_user_id();
        
        $link = $this->database->get_link($link_id);
        if ($link && $link->user_id == $user_id) {
            $new_status = $link->status === 'active' ? 'inactive' : 'active';
            
            if ($this->database->update_link($link_id, array('status' => $new_status))) {
                add_settings_error('tpls_admin', 'status_updated', __('Link status updated.', 'traffic-portal-link-shortener'), 'updated');
            } else {
                add_settings_error('tpls_admin', 'update_failed', __('Failed to update link status.', 'traffic-portal-link-shortener'), 'error');
            }
        }
        
        wp_redirect(admin_url('admin.php?page=traffic-portal-links'));
        exit;
    }
    
    /**
     * AJAX handler for deleting links
     */
    public function ajax_delete_link() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpls_admin_action')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
        $user_id = get_current_user_id();
        
        if ($this->database->delete_link($link_id, $user_id)) {
            wp_die(json_encode(array('success' => true, 'message' => 'Link deleted successfully')));
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Failed to delete link')));
        }
    }
    
    /**
     * AJAX handler for toggling link status
     */
    public function ajax_toggle_status() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tpls_admin_action')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Security check failed')));
        }
        
        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;
        $user_id = get_current_user_id();
        
        $link = $this->database->get_link($link_id);
        if ($link && $link->user_id == $user_id) {
            $new_status = $link->status === 'active' ? 'inactive' : 'active';
            
            if ($this->database->update_link($link_id, array('status' => $new_status))) {
                wp_die(json_encode(array('success' => true, 'message' => 'Status updated', 'new_status' => $new_status)));
            }
        }
        
        wp_die(json_encode(array('success' => false, 'message' => 'Failed to update status')));
    }
    
    /**
     * Get action URL with nonce
     */
    private function get_action_url(string $action, int $link_id): string {
        return wp_nonce_url(
            admin_url('admin.php?page=traffic-portal-links&action=' . $action . '&link_id=' . $link_id),
            $action . '_link_' . $link_id
        );
    }
    
    /**
     * Render admin notices
     */
    private function render_admin_notices() {
        settings_errors('tpls_admin');
        settings_errors('tpls_settings');
    }
}