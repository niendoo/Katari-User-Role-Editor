<?php
/**
 * Handles plugin activation tasks
 *
 * @package Katari_User_Role_Editor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class Katari_Activator {
    /**
     * Handle all activation tasks
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_default_roles();
        
        // Update version
        update_option('katari_version', KATARI_PLUGIN_VERSION);
        
        // Clear the permalinks
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private static function create_tables() {
        // Initialize logs to create table
        $logs = new Katari_Logs();
        $logs->create_table();
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $default_settings = array(
            'enable_logging' => true,
            'log_retention_days' => 30,
            'show_admin_bar' => true
        );

        // Only add if not exists
        if (!get_option('katari_settings')) {
            add_option('katari_settings', $default_settings);
        }
    }

    /**
     * Create default custom roles if needed
     */
    private static function create_default_roles() {
        // Example: Create a custom editor role with specific capabilities
        if (!get_role('katari_content_manager')) {
            add_role(
                'katari_content_manager',
                __('Content Manager', 'katari-user-role-editor'),
                array(
                    'read' => true,
                    'edit_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'edit_pages' => true,
                    'edit_published_pages' => true,
                    'publish_pages' => true,
                    'upload_files' => true,
                    'manage_categories' => true
                )
            );
        }
    }
}
