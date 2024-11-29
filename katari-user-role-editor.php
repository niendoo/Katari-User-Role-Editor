<?php
/**
 * Plugin Name: Katari User Role Editor
 * Plugin URI: https://zeedstudio.com/katari-user-role-editor
 * Description: A comprehensive WordPress user role and capability management plugin.
 * Version: 1.0.0
 * Author: Abdul Rauf Abdul Rahaman
 * Author URI: https://zeedstudio.com
 * Text Domain: katari-user-role-editor
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KATARI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KATARI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KATARI_PLUGIN_VERSION', '1.0.0');

// Include necessary files
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-main.php';
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-capabilities-manager.php';
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-logs.php';
require_once KATARI_PLUGIN_DIR . 'includes/activate.php';
require_once KATARI_PLUGIN_DIR . 'includes/class-katari-activity-monitor.php';

/**
 * Initialize the plugin
 */
function katari_init() {
    $GLOBALS['katari_capabilities_manager'] = new Katari_Capabilities_Manager();
    $GLOBALS['katari_logs'] = new Katari_Logs();
    $GLOBALS['katari_activity_monitor'] = new Katari_Activity_Monitor();
}
add_action('plugins_loaded', 'katari_init');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Katari_Activator', 'activate'));
register_deactivation_hook(__FILE__, 'katari_deactivate');

/**
 * Plugin deactivation hook
 */
function katari_deactivate() {
    global $wpdb;
    
    // Drop the logs table
    $table_name = $wpdb->prefix . 'katari_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    // Delete plugin options
    delete_option('katari_version');
    delete_option('katari_settings');
    
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('katari_cleanup_logs');
    
    // Clear the permalinks
    flush_rewrite_rules();
}

// Initialize the main plugin class
Katari_Main::get_instance();
