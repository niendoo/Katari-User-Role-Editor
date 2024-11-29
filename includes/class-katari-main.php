<?php
/**
 * Main plugin class
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main plugin class
 */
class Katari_Main {
    /**
     * Plugin instance.
     *
     * @var Katari_Main
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @return Katari_Main
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_init', array( $this, 'handle_role_actions' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Load dependencies.
     */
    private function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'class-katari-capabilities-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-katari-logs.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-katari-import-export.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-katari-analytics.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-katari-frontend.php';
    }

    /**
     * Add menu pages.
     */
    public function admin_menu() {
        add_menu_page(
            __( 'Katari Role Editor', 'katari-user-role-editor' ),
            __( 'Role Editor', 'katari-user-role-editor' ),
            'manage_options',
            'katari-role-editor',
            array( $this, 'render_admin_page' ),
            'dashicons-groups',
            70
        );

        add_submenu_page(
            'katari-role-editor',
            __( 'Roles & Capabilities', 'katari-user-role-editor' ),
            __( 'Roles & Capabilities', 'katari-user-role-editor' ),
            'manage_options',
            'katari-role-editor',
            array( $this, 'render_admin_page' )
        );

        add_submenu_page(
            'katari-role-editor',
            __( 'Analytics', 'katari-user-role-editor' ),
            __( 'Analytics', 'katari-user-role-editor' ),
            'manage_options',
            'katari-analytics',
            array( $this, 'render_analytics_page' )
        );

        add_submenu_page(
            'katari-role-editor',
            __( 'Activity Log', 'katari-user-role-editor' ),
            __( 'Activity Log', 'katari-user-role-editor' ),
            'manage_options',
            'katari-activity-log',
            array( $this, 'render_activity_log_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        $screen = get_current_screen();
        if (!$screen || !isset($screen->id)) {
            return;
        }

        // Only load on our plugin pages
        if (strpos($screen->id, 'katari') === false) {
            return;
        }

        wp_enqueue_style(
            'katari-admin-style',
            KATARI_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            KATARI_PLUGIN_VERSION
        );

        // Enqueue Chart.js before our admin script
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            array(),
            '3.9.1',
            true
        );

        wp_enqueue_script(
            'katari-admin-script',
            KATARI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'chartjs'),
            KATARI_PLUGIN_VERSION,
            true
        );

        wp_localize_script('katari-admin-script', 'katariAdmin', array(
            'nonce' => wp_create_nonce('katari_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    /**
     * Load textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'katari-user-role-editor',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    /**
     * Render analytics page.
     */
    public function render_analytics_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/analytics-page.php';
    }

    /**
     * Render activity log page.
     */
    public function render_activity_log_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/activity-log-page.php';
    }

    /**
     * Display admin notices.
     */
    public function admin_notices() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'katari-role-editor' ) {
            return;
        }

        if ( isset( $_GET['error'] ) ) {
            $error = sanitize_text_field( $_GET['error'] );
            $message = '';

            switch ( $error ) {
                case 'role_exists':
                    $message = __( 'Error: This role already exists. Please choose a different name.', 'katari-user-role-editor' );
                    break;
            }

            if ( $message ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            }
        }

        if ( isset( $_GET['action'] ) && isset( $_GET['updated'] ) ) {
            $action = sanitize_text_field( $_GET['action'] );
            $message = '';

            switch ( $action ) {
                case 'deleted':
                    $message = __( 'Role deleted successfully.', 'katari-user-role-editor' );
                    break;
                case 'updated':
                    $message = __( 'Role capabilities updated successfully.', 'katari-user-role-editor' );
                    break;
                case 'created':
                    $message = __( 'New role created successfully.', 'katari-user-role-editor' );
                    break;
            }

            if ( $message ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
            }
        }
    }

    /**
     * Handle role management actions.
     */
    public function handle_role_actions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check for katari_add_role action
        if ( isset( $_POST['katari_add_role'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'katari_add_role' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'katari-user-role-editor' ) );
            }

            if ( ! isset( $_POST['role_name'], $_POST['role_display_name'] ) ) {
                return;
            }

            $role_name = sanitize_text_field( $_POST['role_name'] );
            $role_display_name = sanitize_text_field( $_POST['role_display_name'] );

            // Check if role already exists
            $role_slug = sanitize_title( $role_name );
            $roles = wp_roles();
            if ( isset( $roles->roles[$role_slug] ) ) {
                wp_safe_redirect( add_query_arg( array(
                    'error' => 'role_exists',
                    'page' => 'katari-role-editor'
                ), admin_url( 'admin.php' ) ) );
                exit;
            }

            $this->create_role( $role_display_name, array() );
            wp_safe_redirect( add_query_arg( array(
                'action' => 'created',
                'updated' => '1',
                'page' => 'katari-role-editor'
            ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Check for katari_update_role action
        if ( isset( $_POST['katari_update_role'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'katari_update_role' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'katari-user-role-editor' ) );
            }

            if ( ! isset( $_POST['role_name'], $_POST['capabilities'] ) ) {
                return;
            }

            $role = sanitize_text_field( $_POST['role_name'] );
            $capabilities = array_map( 'sanitize_text_field', $_POST['capabilities'] );
            $this->update_role( $role, $capabilities );
            wp_safe_redirect( add_query_arg( array(
                'action' => 'updated',
                'updated' => '1',
                'page' => 'katari-role-editor'
            ), admin_url( 'admin.php' ) ) );
            exit;
        }

        // Check for katari_delete_role action
        if ( isset( $_POST['katari_delete_role'] ) ) {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'katari_delete_role' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'katari-user-role-editor' ) );
            }

            if ( ! isset( $_POST['role_name'] ) ) {
                return;
            }

            $role = sanitize_text_field( $_POST['role_name'] );
            $this->delete_role( $role );
            wp_safe_redirect( add_query_arg( array(
                'action' => 'deleted',
                'updated' => '1',
                'page' => 'katari-role-editor'
            ), admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    /**
     * Update role capabilities.
     *
     * @param string $role Role name.
     * @param array  $capabilities Array of capabilities.
     */
    private function update_role( $role, $capabilities ) {
        $wp_role = get_role( $role );
        if ( ! $wp_role ) {
            return;
        }

        // Store old capabilities for logging
        $old_caps = array_keys(array_filter($wp_role->capabilities));

        // Remove all existing capabilities
        foreach ( $old_caps as $cap ) {
            $wp_role->remove_cap( $cap );
        }

        // Add new capabilities
        foreach ( $capabilities as $cap ) {
            $wp_role->add_cap( $cap );
        }

        // Trigger logging action with both old and new capabilities
        do_action( 'katari_role_updated', $role, $capabilities, $old_caps );
    }

    /**
     * Create a new role.
     *
     * @param string $role_name Role name.
     * @param array  $capabilities Array of capabilities.
     */
    private function create_role( $role_name, $capabilities = array() ) {
        $role = add_role( sanitize_title( $role_name ), $role_name, array() );
        
        if ( ! is_null( $role ) ) {
            // Add capabilities one by one for proper tracking
            foreach ( $capabilities as $cap ) {
                $role->add_cap( $cap );
            }
            
            // Trigger logging action with initial capabilities
            do_action( 'katari_role_created', $role_name, $capabilities );
        }
    }

    /**
     * Delete a role.
     *
     * @param string $role Role name.
     */
    private function delete_role( $role ) {
        // Get role capabilities before deletion for logging
        $role_obj = get_role( $role );
        $capabilities = $role_obj ? array_keys(array_filter($role_obj->capabilities)) : array();
        
        // Trigger logging action with the role's capabilities
        do_action( 'katari_role_deleted', $role, $capabilities );
        
        remove_role( $role );
    }
}
