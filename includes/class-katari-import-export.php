<?php
/**
 * Import/Export class
 *
 * @package Katari_User_Role_Editor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Import/Export class
 */
class Katari_Import_Export {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'handle_export' ) );
        add_action( 'admin_init', array( $this, 'handle_import' ) );
    }

    /**
     * Handle role export.
     */
    public function handle_export() {
        if ( ! isset( $_GET['action'] ) || 'katari_export_roles' !== $_GET['action'] ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export roles.', 'katari-user-role-editor' ) );
        }

        check_admin_referer( 'katari_nonce' );

        $roles = wp_roles();
        $export_data = array();

        foreach ( $roles->roles as $role => $data ) {
            $export_data[ $role ] = array(
                'name' => $roles->role_names[ $role ],
                'capabilities' => $data['capabilities'],
            );
        }

        $filename = 'katari-roles-export-' . date( 'Y-m-d' ) . '.json';
        
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        
        echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
        exit;
    }

    /**
     * Handle role import.
     */
    public function handle_import() {
        if ( ! isset( $_POST['katari_import_roles'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to import roles.', 'katari-user-role-editor' ) );
        }

        check_admin_referer( 'katari_import_roles' );

        if ( ! isset( $_FILES['import_file'] ) ) {
            wp_die( esc_html__( 'No file was uploaded.', 'katari-user-role-editor' ) );
        }

        $file = $_FILES['import_file'];

        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            wp_die( esc_html__( 'Error uploading file.', 'katari-user-role-editor' ) );
        }

        $content = file_get_contents( $file['tmp_name'] );
        $import_data = json_decode( $content, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_die( esc_html__( 'Invalid JSON file.', 'katari-user-role-editor' ) );
        }

        foreach ( $import_data as $role => $data ) {
            $role_obj = get_role( $role );
            
            if ( ! $role_obj ) {
                add_role( $role, $data['name'], $data['capabilities'] );
            } else {
                // Update existing role capabilities
                foreach ( $data['capabilities'] as $cap => $grant ) {
                    if ( $grant ) {
                        $role_obj->add_cap( $cap );
                    } else {
                        $role_obj->remove_cap( $cap );
                    }
                }
            }
        }

        do_action( 'katari_roles_imported', $import_data );

        wp_safe_redirect( add_query_arg( 
            array(
                'page' => 'katari-user-role-editor',
                'message' => 'import_success',
            ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Validate import data.
     *
     * @param array $data Import data.
     * @return bool|WP_Error
     */
    public function validate_import_data( $data ) {
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'invalid_format', __( 'Invalid import data format.', 'katari-user-role-editor' ) );
        }

        foreach ( $data as $role => $role_data ) {
            if ( ! isset( $role_data['name'] ) || ! isset( $role_data['capabilities'] ) ) {
                return new WP_Error(
                    'missing_data',
                    sprintf(
                        /* translators: %s: Role name */
                        __( 'Missing required data for role: %s', 'katari-user-role-editor' ),
                        $role
                    )
                );
            }

            if ( ! is_array( $role_data['capabilities'] ) ) {
                return new WP_Error(
                    'invalid_capabilities',
                    sprintf(
                        /* translators: %s: Role name */
                        __( 'Invalid capabilities format for role: %s', 'katari-user-role-editor' ),
                        $role
                    )
                );
            }
        }

        return true;
    }
}
