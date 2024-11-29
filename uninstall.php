<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Katari_User_Role_Editor
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}katari_logs");

// Delete options
delete_option('katari_version');
delete_option('katari_settings');

// Clear scheduled hooks
wp_clear_scheduled_hook('katari_cleanup_logs');

// Clear transients
delete_transient('katari_role_editor_capabilities');

// Clear the permalinks
flush_rewrite_rules();
