<?php
/**
 * Logs class
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Logs class
 */
class Katari_Logs {
    /**
     * Log table name.
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'katari_logs';
        
        add_action( 'plugins_loaded', array( $this, 'create_table' ) );
        add_action( 'katari_role_updated', array( $this, 'log_role_update' ), 10, 2 );
        add_action( 'katari_capability_toggled', array( $this, 'log_capability_toggle' ), 10, 3 );
        add_action( 'katari_role_created', array( $this, 'log_role_creation' ), 10, 2 );
        add_action( 'katari_role_deleted', array( $this, 'log_role_deletion' ) );
    }

    /**
     * Create logs table.
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            description text NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Add log entry.
     *
     * @param string $action Action performed.
     * @param string $description Description of the action.
     */
    public function add_log( $action, $description ) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            array(
                'user_id'     => get_current_user_id(),
                'action'      => $action,
                'description' => $description,
            ),
            array(
                '%d',
                '%s',
                '%s',
            )
        );
    }

    /**
     * Log role update.
     *
     * @param string $role_name Role name.
     * @param array  $capabilities Capabilities.
     */
    public function log_role_update( $role_name, $capabilities ) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Role name */
            __( 'User %1$s updated capabilities for role %2$s', 'katari-user-role-editor' ),
            $user->display_name,
            $role_name
        );
        
        $this->add_log( 'role_update', $description );
    }

    /**
     * Log capability toggle.
     *
     * @param string $role_name Role name.
     * @param string $capability Capability name.
     * @param bool   $granted Whether the capability was granted or revoked.
     */
    public function log_capability_toggle( $role_name, $capability, $granted ) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Capability name, 3: Granted/Revoked, 4: Role name */
            __( 'User %1$s %2$s capability %3$s for role %4$s', 'katari-user-role-editor' ),
            $user->display_name,
            $granted ? __( 'granted', 'katari-user-role-editor' ) : __( 'revoked', 'katari-user-role-editor' ),
            $capability,
            $role_name
        );
        
        $this->add_log( 'capability_toggle', $description );
    }

    /**
     * Log role creation.
     *
     * @param string $role_name Role name.
     * @param array  $capabilities Capabilities.
     */
    public function log_role_creation( $role_name, $capabilities ) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Role name */
            __( 'User %1$s created new role %2$s', 'katari-user-role-editor' ),
            $user->display_name,
            $role_name
        );
        
        $this->add_log( 'role_creation', $description );
    }

    /**
     * Log role deletion.
     *
     * @param string $role_name Role name.
     */
    public function log_role_deletion( $role_name ) {
        $user = wp_get_current_user();
        $description = sprintf(
            /* translators: 1: User display name, 2: Role name */
            __( 'User %1$s deleted role %2$s', 'katari-user-role-editor' ),
            $user->display_name,
            $role_name
        );
        
        $this->add_log( 'role_deletion', $description );
    }

    /**
     * Get logs.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_logs( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'number'      => 20,
            'offset'      => 0,
            'orderby'     => 'created_at',
            'order'       => 'DESC',
            'action_type' => '',
            'date_from'   => '',
            'date_to'     => '',
            'search'      => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $sql = "SELECT * FROM {$this->table_name} WHERE 1=1";
        
        // Filter by action type
        if ( ! empty( $args['action_type'] ) ) {
            $sql .= $wpdb->prepare( ' AND action = %s', $args['action_type'] );
        }

        // Filter by date range
        if ( ! empty( $args['date_from'] ) ) {
            $sql .= $wpdb->prepare( ' AND DATE(created_at) >= %s', $args['date_from'] );
        }
        if ( ! empty( $args['date_to'] ) ) {
            $sql .= $wpdb->prepare( ' AND DATE(created_at) <= %s', $args['date_to'] );
        }

        // Search in description
        if ( ! empty( $args['search'] ) ) {
            $sql .= $wpdb->prepare( ' AND description LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' );
        }

        $sql .= " ORDER BY {$args['orderby']} {$args['order']}";
        $sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['number'], $args['offset'] );

        return $wpdb->get_results( $sql );
    }

    /**
     * Get total logs count.
     *
     * @param array $args Query arguments.
     * @return int
     */
    public function get_total_logs( $args = array() ) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table_name} WHERE 1=1";
        
        // Filter by action type
        if ( ! empty( $args['action_type'] ) ) {
            $sql .= $wpdb->prepare( ' AND action = %s', $args['action_type'] );
        }

        // Filter by date range
        if ( ! empty( $args['date_from'] ) ) {
            $sql .= $wpdb->prepare( ' AND DATE(created_at) >= %s', $args['date_from'] );
        }
        if ( ! empty( $args['date_to'] ) ) {
            $sql .= $wpdb->prepare( ' AND DATE(created_at) <= %s', $args['date_to'] );
        }

        // Search in description
        if ( ! empty( $args['search'] ) ) {
            $sql .= $wpdb->prepare( ' AND description LIKE %s', '%' . $wpdb->esc_like( $args['search'] ) . '%' );
        }

        return $wpdb->get_var( $sql );
    }

    /**
     * Get all unique action types.
     *
     * @return array
     */
    public function get_action_types() {
        global $wpdb;
        
        $sql = "SELECT DISTINCT action FROM {$this->table_name} ORDER BY action ASC";
        $results = $wpdb->get_results( $sql );
        
        return wp_list_pluck( $results, 'action' );
    }
}
