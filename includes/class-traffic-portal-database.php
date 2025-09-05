<?php

declare(strict_types=1);

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database handler for Traffic Portal Link Shortener
 */
class Traffic_Portal_Database {
    
    /**
     * Table name for storing links
     */
    private $table_name;
    
    /**
     * WordPress database instance
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'traffic_portal_links';
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tpkey varchar(100) NOT NULL,
            destination text NOT NULL,
            domain varchar(100) DEFAULT 'trfc.link',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            clicks int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY tpkey (tpkey),
            KEY status (status),
            UNIQUE KEY unique_user_key (user_id, tpkey)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Save a new link
     *
     * @param int    $user_id     User ID
     * @param string $tpkey       Short key
     * @param string $destination Destination URL
     * @param string $domain      Domain (default: trfc.link)
     * @return int|false Link ID on success, false on failure
     */
    public function save_link(int $user_id, string $tpkey, string $destination, string $domain = 'trfc.link') {
        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'user_id'     => $user_id,
                'tpkey'       => sanitize_text_field($tpkey),
                'destination' => esc_url_raw($destination),
                'domain'      => sanitize_text_field($domain),
                'status'      => 'active'
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    /**
     * Get links for a user
     *
     * @param int $user_id User ID
     * @param int $limit   Number of results to return
     * @param int $offset  Offset for pagination
     * @return array Array of link objects
     */
    public function get_user_links(int $user_id, int $limit = 20, int $offset = 0): array {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id,
            $limit,
            $offset
        );
        
        $results = $this->wpdb->get_results($sql);
        
        return $results ?: array();
    }
    
    /**
     * Get a specific link
     *
     * @param int $id Link ID
     * @return object|null Link object or null if not found
     */
    public function get_link(int $id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Get a link by user and key
     *
     * @param int    $user_id User ID
     * @param string $tpkey   Short key
     * @return object|null Link object or null if not found
     */
    public function get_link_by_key(int $user_id, string $tpkey) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE user_id = %d AND tpkey = %s",
            $user_id,
            sanitize_text_field($tpkey)
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    /**
     * Update a link
     *
     * @param int   $id   Link ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update_link(int $id, array $data): bool {
        $allowed_fields = array('tpkey', 'destination', 'domain', 'status');
        $update_data = array();
        $format = array();
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields, true)) {
                switch ($field) {
                    case 'tpkey':
                    case 'domain':
                    case 'status':
                        $update_data[$field] = sanitize_text_field($value);
                        $format[] = '%s';
                        break;
                    case 'destination':
                        $update_data[$field] = esc_url_raw($value);
                        $format[] = '%s';
                        break;
                }
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a link
     *
     * @param int $id      Link ID
     * @param int $user_id User ID (for security)
     * @return bool Success status
     */
    public function delete_link(int $id, int $user_id): bool {
        $result = $this->wpdb->delete(
            $this->table_name,
            array(
                'id'      => $id,
                'user_id' => $user_id
            ),
            array('%d', '%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get total links count for a user
     *
     * @param int $user_id User ID
     * @return int Total count
     */
    public function get_user_links_count(int $user_id): int {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        );
        
        return (int) $this->wpdb->get_var($sql);
    }
    
    /**
     * Check if a key exists for a user
     *
     * @param int    $user_id User ID
     * @param string $tpkey   Short key
     * @return bool True if exists, false otherwise
     */
    public function key_exists_for_user(int $user_id, string $tpkey): bool {
        $sql = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE user_id = %d AND tpkey = %s",
            $user_id,
            sanitize_text_field($tpkey)
        );
        
        return $this->wpdb->get_var($sql) > 0;
    }
}